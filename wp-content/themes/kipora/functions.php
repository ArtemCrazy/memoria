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
		// Brand colours live in a plain CSS file the client can edit without a build.
		$brand = get_theme_file_path( 'assets/css/brand.css' );
		wp_enqueue_style( 'kipora-brand', get_theme_file_uri( 'assets/css/brand.css' ), [], is_file( $brand ) ? (string) filemtime( $brand ) : '0.1.0' );
		$css = get_theme_file_path( 'assets/css/theme.css' );
		wp_enqueue_style( 'kipora-theme', get_theme_file_uri( 'assets/css/theme.css' ), [ 'kipora-brand' ], is_file( $css ) ? (string) filemtime( $css ) : '0.1.0' );
		wp_enqueue_script( 'kipora-theme', get_theme_file_uri( 'assets/js/theme.js' ), [], (string) filemtime( get_theme_file_path( 'assets/js/theme.js' ) ), [ 'in_footer' => true, 'strategy' => 'defer' ] );
	}
);

add_action(
	'wp_head',
	static function (): void {
		$fonts = str_starts_with( determine_locale(), 'ru' ) ? [ 'oranienbaum-cyrillic', 'onest-cyrillic' ] : [ 'instrument-serif-latin', 'instrument-sans-latin' ];
		foreach ( $fonts as $font ) {
			echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( "assets/fonts/{$font}.woff2" ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		}
	},
	2
);

// Entrance animations hide blocks only when JS runs and motion is welcome.
// If theme.js never arrives, everything is shown after 3 seconds.
add_action(
	'wp_head',
	static function (): void {
		echo "<script>(function(d){if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;d.documentElement.classList.add('has-reveal');setTimeout(function(){if(!window.kpRevealReady)d.documentElement.classList.remove('has-reveal');},3000);})(document);</script>
";
	},
	1
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

/**
 * "Hoolitseme *puhkepaiga* eest" → escaped text with <em> around starred words.
 * Lets the client set the italic accents of the hero headline from the page title.
 */
function kipora_theme_emphasis( string $title ): string {
	return preg_replace( '/\*([^*]+)\*/u', '<em>$1</em>', esc_html( wp_strip_all_tags( $title ) ) );
}

// Asterisks are markup for the hero only: keep them out of the browser tab and menus.
add_filter(
	'document_title_parts',
	static function ( array $parts ): array {
		$parts['title'] = str_replace( '*', '', (string) ( $parts['title'] ?? '' ) );
		return $parts;
	}
);
add_filter( 'nav_menu_item_title', static fn( string $title ): string => str_replace( '*', '', $title ) );

/**
 * [jpg, webp] URLs of a theme image, versioned by file time so a replaced
 * photo is not served from cache. Falls back to another image while the
 * requested one has not been made yet.
 */
function kipora_theme_image( string $name, string $fallback = '' ): array {
	if ( ! is_file( get_theme_file_path( "assets/img/{$name}.jpg" ) ) && $fallback ) {
		$name = $fallback;
	}
	$url = static function ( string $file ): string {
		$path = get_theme_file_path( "assets/img/{$file}" );
		return add_query_arg( 'v', is_file( $path ) ? (string) filemtime( $path ) : '0', get_theme_file_uri( "assets/img/{$file}" ) );
	};
	return [ $url( "{$name}.jpg" ), $url( "{$name}.webp" ) ];
}

/** Short wavy line above section headings. */
function kipora_theme_divider(): string {
	return '<svg class="section-head__divider" viewBox="0 0 69 6" aria-hidden="true" focusable="false"><path d="M1 3c5.7-3 11.3 3 17 0s11.3-3 17 0 11.3 3 17 0 11.3-3 16 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
}

/** URL of any theme asset, versioned by file time. */
function kipora_theme_asset( string $rel ): string {
	$path = get_theme_file_path( "assets/{$rel}" );
	return add_query_arg( 'v', is_file( $path ) ? (string) filemtime( $path ) : '0', get_theme_file_uri( "assets/{$rel}" ) );
}

/** Calculator link with a direction already chosen (same format the calculator writes). */
function kipora_theme_selection( string $direction ): string {
	return rtrim( strtr( base64_encode( (string) wp_json_encode( [ 'direction' => $direction ] ) ), '+/', '-_' ), '=' );
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
