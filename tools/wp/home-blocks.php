<?php
/**
 * Moves the home page from the fixed template into blocks: every language gets
 * the same sections with the texts it had before (taken from the interface
 * translations), so the client edits them in the block editor from now on.
 *
 * Run once: python tools/deploy.py --wp home-blocks
 * Pages that already contain KIPORA blocks are left alone; add --force to rebuild them.
 */

defined( 'ABSPATH' ) || exit;

$force = ! empty( $_GET['force'] ); // phpcs:ignore WordPress.Security.NonceVerification

/** "*word*" from the old strings → <em>word</em> (italic accents of headings). */
$em = static fn( string $s ): string => preg_replace( '/\*([^*]+)\*/u', '<em>$1</em>', esc_html( $s ) );

/** Serialized block; $inner are child blocks of the same shape. */
$block = static function ( string $name, array $attrs, array $inner = [] ): array {
	return [
		'blockName'    => $name,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => '',
		'innerContent' => array_fill( 0, count( $inner ), null ),
	];
};

$build = static function ( WP_Post $page ) use ( $em, $block ): string {
	$point = static fn( string $text ): array => $block( 'kipora/point', [ 'text' => esc_html( $text ) ] );
	$title = str_contains( $page->post_title, '*' ) ? $page->post_title : __( 'We look after the *resting place* when you *can’t be there* yourself', 'kipora' );

	$blocks = [
		$block(
			'kipora/hero',
			[
				'features'   => esc_html__( 'Tallinn • Harju County', 'kipora' ),
				'title'      => $em( $title ),
				'lead'       => esc_html( $page->post_excerpt ),
				'buttonText' => esc_html__( 'Calculate the price', 'kipora' ),
				'image'      => [ 'alt' => __( 'Grave lantern with a burning candle', 'kipora' ) ],
			],
			[
				$block( 'kipora/hero-card', [ 'title' => esc_html__( 'Grave care', 'kipora' ), 'text' => esc_html__( 'Once or for the whole season', 'kipora' ), 'direction' => 'grave' ] ),
				$block( 'kipora/hero-card', [ 'title' => esc_html__( 'Pets', 'kipora' ), 'text' => esc_html__( 'Cremation, urn and memorial page', 'kipora' ), 'direction' => 'pet' ] ),
			]
		),
		$block(
			'kipora/problem',
			[
				'title' => $em( __( 'Usually it goes *like this*', 'kipora' ) ),
				'image' => [ 'alt' => __( 'A grave plot overgrown with leaves and weeds', 'kipora' ) ],
				'quote' => $em( __( '*Memory does not depend on distance.* The plot can be cared for even when you are on the other side of the world.', 'kipora' ) ),
			],
			[
				$point( __( 'A trip to the cemetery once a season, if it works out', 'kipora' ) ),
				$point( __( 'Calling relatives who live closer', 'kipora' ) ),
				$point( __( 'Weeds and leaves grow faster than you can come', 'kipora' ) ),
				$point( __( 'No way to know whether the candle is burning', 'kipora' ) ),
				$point( __( 'A heavy feeling on memorial days', 'kipora' ) ),
			]
		),
		$block(
			'kipora/steps',
			[
				'title' => $em( __( 'How it *works*', 'kipora' ) ),
				'label' => esc_html__( 'Four steps', 'kipora' ),
			],
			[
				$block( 'kipora/step', [ 'title' => esc_html__( 'Calculate the price', 'kipora' ), 'text' => esc_html__( 'Choose the service, plot size and cemetery. The price appears right away.', 'kipora' ), 'fallback' => 'step-1', 'image' => [ 'alt' => __( 'Person choosing a service on a phone', 'kipora' ) ] ] ),
				$block( 'kipora/step', [ 'title' => esc_html__( 'Log in and pay', 'kipora' ), 'text' => esc_html__( 'Smart-ID or Mobiil-ID, then a bank link or a card.', 'kipora' ), 'fallback' => 'step-2', 'image' => [ 'alt' => __( 'Paying on a phone', 'kipora' ) ] ] ),
				$block( 'kipora/step', [ 'title' => esc_html__( 'We do the work', 'kipora' ), 'text' => esc_html__( 'We clean the plot, care for the plants, bring flowers and a candle.', 'kipora' ), 'fallback' => 'step-3', 'image' => [ 'alt' => __( 'Cleaning a grave plot', 'kipora' ) ] ] ),
				$block( 'kipora/step', [ 'title' => esc_html__( 'See the photos', 'kipora' ), 'text' => esc_html__( 'Photos before and after appear in your account.', 'kipora' ), 'fallback' => 'step-4', 'image' => [ 'alt' => __( 'A tidy grave with flowers and a candle', 'kipora' ) ] ] ),
			]
		),
		$block(
			'kipora/compare',
			[
				'title'      => $em( __( 'An honest *comparison*', 'kipora' ) ),
				'baseTitle'  => esc_html__( 'Going yourself', 'kipora' ),
				'brandTitle' => 'KIPORA',
				'quote'      => $em( __( 'You pay only for what you choose. *The price is fixed before payment.*', 'kipora' ) ),
				'buttonText' => esc_html__( 'Calculate the price', 'kipora' ),
			],
			array_map(
				static fn( array $row ): array => $block( 'kipora/compare-row', [ 'label' => esc_html( $row[0] ), 'base' => esc_html( $row[1] ), 'brand' => esc_html( $row[2] ) ] ),
				[
					[ __( 'Time', 'kipora' ), __( 'Half a day or more', 'kipora' ), __( 'A few minutes to order', 'kipora' ) ],
					[ __( 'The trip', 'kipora' ), __( 'You drive yourself', 'kipora' ), __( 'We go', 'kipora' ) ],
					[ __( 'Tools and materials', 'kipora' ), __( 'Bring your own', 'kipora' ), __( 'We bring them', 'kipora' ) ],
					[ __( 'Checking the result', 'kipora' ), __( 'Only on site', 'kipora' ), __( 'Photos before and after', 'kipora' ) ],
					[ __( 'Over the season', 'kipora' ), __( 'Every visit from scratch', 'kipora' ), __( 'Seasonal care in one payment', 'kipora' ) ],
				]
			)
		),
		$block(
			'kipora/memory',
			[
				'title'    => $em( __( 'A *memorial card* for each loved one', 'kipora' ) ),
				'lead'     => esc_html__( 'Every order is linked to a memorial card. It keeps everything you want to preserve:', 'kipora' ),
				'image'    => [ 'alt' => __( 'Old family photographs in a box', 'kipora' ) ],
				'cardText' => esc_html__( 'Only you can see the card. You log in with Smart-ID or Mobiil-ID.', 'kipora' ),
			],
			[
				$point( __( 'Name and years of life', 'kipora' ) ),
				$point( __( 'A story and photos', 'kipora' ) ),
				$point( __( 'Documents', 'kipora' ) ),
				$point( __( 'The exact place: sector and plot', 'kipora' ) ),
				$point( __( 'Every photo report', 'kipora' ) ),
			]
		),
		$block(
			'kipora/faq',
			[
				'title'    => $em( __( 'Frequently asked *questions*', 'kipora' ) ),
				'moreText' => esc_html__( 'Did not find an answer?', 'kipora' ),
				'linkText' => esc_html__( 'Write to us', 'kipora' ),
			],
			array_map(
				static fn( array $qa ): array => $block( 'kipora/faq-item', [ 'question' => esc_html( $qa[0] ), 'answer' => esc_html( $qa[1] ) ] ),
				[
					[ __( 'How do I know the work is done?', 'kipora' ), __( 'After every visit we upload photos before and after to your account and send you an email.', 'kipora' ) ],
					[ __( 'Do I need to be in Estonia?', 'kipora' ), __( 'No. Ordering, payment and photos are online. To log in you need Smart-ID or Mobiil-ID.', 'kipora' ) ],
					[ __( 'How do I pay?', 'kipora' ), __( 'Through Montonio, by bank link or card. Seasonal care is paid once for the whole season.', 'kipora' ) ],
					[ __( 'My cemetery is not in the calculator', 'kipora' ), __( 'Write to us with the name of the cemetery and, if you can, a photo of the plot. We will calculate the price.', 'kipora' ) ],
					[ __( 'Do you also help with pets?', 'kipora' ), __( 'Yes: cremation, transport, an urn, engraving and a digital memorial page.', 'kipora' ) ],
					[ __( 'Who can see the memorial card?', 'kipora' ), __( 'Only you. Photos and documents in the archive are not public and are not shown to search engines.', 'kipora' ) ],
				]
			)
		),
	];
	return implode( "\n\n", array_map( 'serialize_block', $blocks ) );
};

$names = [
	'et' => 'Avaleht',
	'ru' => 'Главная',
	'en' => 'Home',
];
$map = (array) get_option( 'kipora_setup_pages', [] );
foreach ( (array) ( $map['home'] ?? [] ) as $lang => $id ) {
	$page = get_post( (int) $id );
	if ( ! $page ) {
		continue;
	}
	if ( ! $force && str_contains( $page->post_content, '<!-- wp:kipora/' ) ) {
		echo "{$lang}: already in blocks, skipped\n";
		continue;
	}
	$locale = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $page->ID, 'locale' ) : get_locale();
	switch_to_locale( $locale );
	$content = $build( $page );
	restore_previous_locale();
	wp_update_post(
		[
			'ID'           => $page->ID,
			'post_content' => wp_slash( $content ),
			'post_title'   => $names[ $lang ] ?? $page->post_title,
		]
	);
	preg_match( '/"title":"([^"]+)"/', $content, $m );
	echo "{$lang} ({$locale}): page {$page->ID}, " . strlen( $content ) . ' bytes, hero: ' . json_decode( '"' . ( $m[1] ?? '' ) . '"' ) . "\n";
}
