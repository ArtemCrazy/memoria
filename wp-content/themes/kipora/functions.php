<?php
/**
 * KIPORA theme setup.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script' ] );
		add_post_type_support( 'page', 'excerpt' );
		register_nav_menus(
			[
				'primary' => __( 'Main menu', 'kipora' ),
				'footer'  => __( 'Footer menu', 'kipora' ),
			]
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$css = get_theme_file_path( 'assets/css/theme.css' );
		wp_enqueue_style( 'kipora-theme', get_theme_file_uri( 'assets/css/theme.css' ), [], is_file( $css ) ? (string) filemtime( $css ) : '0.1.0' );
	}
);

add_action(
	'wp_head',
	static function (): void {
		foreach ( [ 'onest-latin', 'alegreya-latin' ] as $font ) {
			echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( "assets/fonts/{$font}.woff2" ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		}
	},
	2
);

// The block library styles are not used: content is styled by the theme.
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	},
	100
);

/**
 * URL of a KIPORA functional page, or home when the plugin is off.
 */
function kipora_theme_page( string $key ): string {
	return class_exists( 'Kipora\\Lang' ) ? Kipora\Lang::page_url( $key ) : home_url( '/' );
}

/** Language links ET / RU / EN (Polylang). */
function kipora_theme_languages(): void {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return;
	}
	$items = pll_the_languages(
		[
			'raw'           => 1,
			'hide_if_empty' => 0,
		]
	);
	if ( ! $items ) {
		return;
	}
	echo '<ul class="lang-switch">';
	foreach ( $items as $item ) {
		printf(
			'<li class="%1$s"><a href="%2$s" hreflang="%3$s" lang="%3$s" %4$s>%5$s</a></li>',
			$item['current_lang'] ? 'current-lang' : '',
			esc_url( $item['url'] ),
			esc_attr( $item['locale'] ? str_replace( '_', '-', $item['locale'] ) : $item['slug'] ),
			$item['current_lang'] ? 'aria-current="true"' : '',
			esc_html( $item['slug'] )
		);
	}
	echo '</ul>';
}
