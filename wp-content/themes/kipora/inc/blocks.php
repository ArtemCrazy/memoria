<?php
/**
 * KIPORA page blocks for the WordPress block editor (Gutenberg).
 *
 * Each block lives in blocks/<name>/: block.json describes its fields,
 * render.php prints it on the site. The editor side of all blocks is one
 * plain script, assets/js/blocks.js (no build step). Blocks are dynamic:
 * the page stores only their fields, so markup can change in render.php
 * without breaking saved pages.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		foreach ( glob( get_theme_file_path( 'blocks/*/block.json' ) ) as $file ) {
			register_block_type( dirname( $file ) );
		}
	}
);

add_filter(
	'block_categories_all',
	static function ( array $categories ): array {
		array_unshift(
			$categories,
			[
				'slug'  => 'kipora',
				'title' => 'KIPORA',
			]
		);
		return $categories;
	}
);

add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		$path = get_theme_file_path( 'assets/js/blocks.js' );
		wp_enqueue_script(
			'kipora-blocks',
			get_theme_file_uri( 'assets/js/blocks.js' ),
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render' ],
			(string) filemtime( $path ),
			true
		);
		wp_set_script_translations( 'kipora-blocks', 'kipora', get_theme_file_path( 'languages' ) );
		// Choices for the blocks that link to the calculator: its cemeteries and packages.
		$tariffs = class_exists( 'Kipora\TariffStore' ) ? Kipora\TariffStore::get() : [];
		$pick    = static fn( array $rows, string $key ): array => array_map(
			static fn( array $row ): array => [
				'value' => (string) $row['id'],
				'label' => is_array( $row[ $key ] ?? null ) ? (string) ( $row[ $key ]['et'] ?? '' ) : (string) ( $row[ $key ] ?? '' ),
			],
			$rows
		);
		wp_add_inline_script(
			'kipora-blocks',
			'window.kiporaBlocks = ' . wp_json_encode(
				[
					'img'        => get_theme_file_uri( 'assets/img/' ),
					'video'      => kipora_theme_asset( 'video/hero-lantern.mp4' ),
					'cemeteries' => $pick( (array) ( $tariffs['grave']['cemeteries'] ?? [] ), 'name' ),
					'packages'   => $pick( (array) ( $tariffs['grave']['packages'] ?? [] ), 'label' ),
				]
			) . ';',
			'before'
		);
	}
);

// The editor shows blocks with the site's own styles, so a page looks almost as it will on the site.
add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'editor-styles' );
		// Buttons and form parts are styled by the KIPORA plugin.
		$plugin = defined( 'KIPORA_URL' ) ? [ KIPORA_URL . 'assets/css/kipora.css' ] : [];
		add_editor_style( array_merge( [ 'assets/css/brand.css' ], $plugin, [ 'assets/css/theme.css', 'assets/css/editor.css' ] ) );
	}
);

/**
 * Blocks the client can insert: KIPORA blocks plus the basic WordPress ones for
 * text pages. Layout-heavy core blocks are left out so a page cannot drift away
 * from the design. A developer extends the list here.
 */
add_filter(
	'allowed_block_types_all',
	static function ( $allowed ) {
		$kipora = array_filter(
			array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() ),
			static fn( string $name ): bool => str_starts_with( $name, 'kipora/' )
		);
		return array_merge(
			array_values( $kipora ),
			[
				'core/paragraph',
				'core/heading',
				'core/list',
				'core/list-item',
				'core/quote',
				'core/image',
				'core/gallery',
				'core/video',
				'core/embed',
				'core/table',
				'core/buttons',
				'core/button',
				'core/columns',
				'core/column',
				'core/group',
				'core/separator',
				'core/details',
				'core/shortcode',
			]
		);
	}
);

/**
 * Block previews in the editor come from the REST API, which runs in the
 * admin's language. Render them in the language of the page being edited,
 * so a Russian page shows Russian labels and prices.
 */
add_filter(
	'rest_request_before_callbacks',
	static function ( $response, $handler, WP_REST_Request $request ) {
		$page = (int) $request->get_param( 'post_id' );
		if ( ! $page || ! str_starts_with( $request->get_route(), '/wp/v2/block-renderer/' ) || ! function_exists( 'pll_get_post_language' ) ) {
			return $response;
		}
		$locale = (string) pll_get_post_language( $page, 'locale' );
		if ( $locale ) {
			add_filter( 'pre_determine_locale', static fn(): string => $locale );
			switch_to_locale( $locale );
			// The KIPORA strings are already loaded in the admin's language: reload them.
			if ( defined( 'KIPORA_FILE' ) ) {
				unload_textdomain( 'kipora', true );
				load_plugin_textdomain( 'kipora', false, dirname( plugin_basename( KIPORA_FILE ) ) . '/languages' );
			}
		}
		return $response;
	},
	10,
	3
);

/**
 * Rich text from the editor: only italic accents and line breaks survive.
 */
function kipora_block_text( string $html ): string {
	return wp_kses(
		$html,
		[
			'em' => [],
			'br' => [],
		]
	);
}

/**
 * Section heading: wavy line over a display heading with italic accents.
 */
function kipora_block_heading( string $html, string $id, string $modifier = '', string $tag = 'h2' ): string {
	return sprintf(
		'<header class="section-head %1$s"><span data-reveal="fade-in">%2$s</span><%3$s class="section-head__title" id="%4$s" data-reveal="heading">%5$s</%3$s></header>',
		esc_attr( $modifier ),
		kipora_theme_divider(),
		tag_escape( $tag ),
		esc_attr( $id ),
		kipora_block_text( $html )
	);
}

/**
 * Photo of a block: the image chosen in the media library, or the theme's
 * built-in photo named in the block until the client replaces it.
 *
 * @param array $image    ['id' => int, 'alt' => string] from the block.
 * @param string $fallback Theme image name (assets/img/<name>.jpg|webp).
 */
function kipora_block_image( array $image, string $fallback, string $class, int $w, int $h, bool $lazy = true ): string {
	$alt = (string) ( $image['alt'] ?? '' );
	$id  = (int) ( $image['id'] ?? 0 );
	if ( $id && wp_attachment_is_image( $id ) ) {
		return sprintf(
			'<picture class="%1$s">%2$s</picture>',
			esc_attr( $class ),
			wp_get_attachment_image(
				$id,
				'large',
				false,
				[
					'alt'     => $alt,
					'loading' => $lazy ? 'lazy' : false,
				]
			)
		);
	}
	if ( ! $fallback ) {
		return '';
	}
	[ $jpg, $webp ] = kipora_theme_image( $fallback );
	return sprintf(
		'<picture class="%1$s"><source srcset="%2$s" type="image/webp"><img src="%3$s" width="%4$d" height="%5$d" alt="%6$s"%7$s></picture>',
		esc_attr( $class ),
		esc_url( $webp ),
		esc_url( $jpg ),
		$w,
		$h,
		esc_attr( $alt ),
		$lazy ? ' loading="lazy"' : ' fetchpriority="high"'
	);
}

/**
 * Link of a block button. Empty means the KIPORA page of the current language
 * (calculator, contact…), so one block works in all three languages.
 */
function kipora_block_url( string $url, string $page ): string {
	return '' !== trim( $url ) ? $url : kipora_theme_page( $page );
}

/**
 * Unique id for a heading, so two copies of a block on one page stay valid.
 */
function kipora_block_id( string $base ): string {
	static $used = [];
	$used[ $base ] = ( $used[ $base ] ?? 0 ) + 1;
	return 1 === $used[ $base ] ? $base : $base . '-' . $used[ $base ];
}
