<?php
/**
 * Search engine basics without an SEO plugin:
 * - per page title and description, edited in the block editor sidebar
 *   ("Search results" panel), in each language separately;
 * - description and social preview tags (Open Graph);
 * - structured data: the business on the home page, questions and answers
 *   wherever the "Questions and answers" block is used;
 * - the account and checkout pages are kept out of search.
 * Breadcrumb markup is printed by kipora_theme_breadcrumbs().
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		foreach ( [ 'kipora_seo_title', 'kipora_seo_description' ] as $key ) {
			register_post_meta(
				'page',
				$key,
				[
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static fn(): bool => current_user_can( 'edit_pages' ),
				]
			);
		}
	}
);

/** Page ids (all languages) of KIPORA service pages that must not be in search. */
function kipora_seo_private_ids(): array {
	$map = (array) get_option( 'kipora_setup_pages', [] );
	$ids = [];
	foreach ( [ 'checkout', 'account' ] as $key ) {
		$ids = array_merge( $ids, array_map( 'intval', array_values( (array) ( $map[ $key ] ?? [] ) ) ) );
	}
	return array_filter( $ids );
}

/** Description of the current page: own field, page excerpt, or the site tagline. */
function kipora_seo_description(): string {
	if ( is_singular() ) {
		$own = trim( (string) get_post_meta( get_the_ID(), 'kipora_seo_description', true ) );
		if ( '' !== $own ) {
			return $own;
		}
		if ( has_excerpt() ) {
			return wp_strip_all_tags( get_the_excerpt() );
		}
	}
	return (string) get_bloginfo( 'description' );
}

add_filter(
	'pre_get_document_title',
	static function ( string $title ): string {
		if ( is_singular() ) {
			$own = trim( (string) get_post_meta( get_the_ID(), 'kipora_seo_title', true ) );
			if ( '' !== $own ) {
				return $own;
			}
		}
		return $title;
	}
);

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( is_page( kipora_seo_private_ids() ) ) {
			$robots['noindex'] = true;
			if ( empty( $robots['nofollow'] ) ) {
				$robots['follow'] = true;
			}
		}
		return $robots;
	}
);

add_filter(
	'wp_sitemaps_posts_query_args',
	static function ( array $args, string $post_type ): array {
		if ( 'page' === $post_type ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? [] ), kipora_seo_private_ids() );
		}
		return $args;
	},
	10,
	2
);

add_action(
	'wp_head',
	static function (): void {
		$description = kipora_seo_description();
		$title       = wp_get_document_title();
		$url         = is_singular() ? (string) get_permalink() : home_url( '/' );
		$image       = has_post_thumbnail() ? (string) get_the_post_thumbnail_url( null, 'large' ) : kipora_theme_image( 'hero-lantern' )[0];
		$locale      = str_replace( '-', '_', get_bloginfo( 'language' ) );

		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}
		foreach (
			[
				'og:type'        => is_front_page() ? 'website' : 'article',
				'og:site_name'   => 'KIPORA',
				'og:title'       => $title,
				'og:description' => $description,
				'og:url'         => $url,
				'og:image'       => $image,
				'og:locale'      => $locale,
			] as $property => $content
		) {
			if ( '' !== $content ) {
				printf( '<meta property="%1$s" content="%2$s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
			}
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

		foreach ( kipora_seo_schema() as $data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
		}
	},
	5
);

/** Structured data for the current page. */
function kipora_seo_schema(): array {
	$out = [];

	if ( is_front_page() ) {
		$business = [
			'@context'    => 'https://schema.org',
			'@type'       => 'LocalBusiness',
			'name'        => 'KIPORA',
			'url'         => home_url( '/' ),
			'description' => (string) get_bloginfo( 'description' ),
			'image'       => kipora_theme_image( 'hero-lantern' )[0],
			'areaServed'  => [ 'Tallinn', 'Harjumaa' ],
		];
		$contacts = class_exists( 'Kipora\\Settings' ) ? Kipora\Settings::contacts() : [];
		if ( isset( $contacts['email'] ) ) {
			$business['email'] = $contacts['email']['value'];
		}
		if ( isset( $contacts['phone'] ) ) {
			$business['telephone'] = $contacts['phone']['value'];
		}
		if ( isset( $contacts['address'] ) ) {
			$business['address'] = $contacts['address']['value'];
		}
		$out[] = $business;
	}

	if ( is_singular() ) {
		$questions = [];
		$walk      = static function ( array $blocks ) use ( &$walk, &$questions ): void {
			foreach ( $blocks as $block ) {
				if ( 'kipora/faq-item' === $block['blockName'] && ! empty( $block['attrs']['question'] ) && ! empty( $block['attrs']['answer'] ) ) {
					$questions[] = [
						'@type'          => 'Question',
						'name'           => wp_strip_all_tags( $block['attrs']['question'] ),
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text'  => wp_strip_all_tags( $block['attrs']['answer'] ),
						],
					];
				}
				$walk( $block['innerBlocks'] ?? [] );
			}
		};
		$walk( parse_blocks( (string) get_post_field( 'post_content', get_the_ID() ) ) );
		if ( $questions ) {
			$out[] = [
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $questions,
			];
		}
	}

	return $out;
}
