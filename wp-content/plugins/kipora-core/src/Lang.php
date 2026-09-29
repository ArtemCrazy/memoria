<?php
/**
 * Current site language (et / ru / en) and language-aware page links.
 * Works with Polylang when it is active and falls back to the WP locale.
 */

namespace Kipora;

final class Lang {

	public const SUPPORTED = [ 'et', 'ru', 'en' ];

	public static function current(): string {
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = (string) pll_current_language( 'slug' );
			if ( in_array( $lang, self::SUPPORTED, true ) ) {
				return $lang;
			}
		}
		return self::from_locale( determine_locale() );
	}

	public static function from_locale( string $locale ): string {
		$short = strtolower( substr( $locale, 0, 2 ) );
		return in_array( $short, self::SUPPORTED, true ) ? $short : 'et';
	}

	public static function normalize( $lang ): string {
		$lang = strtolower( (string) $lang );
		return in_array( $lang, self::SUPPORTED, true ) ? $lang : 'et';
	}

	/** Locale for Montonio and SK dialogs. */
	public static function montonio_locale( string $lang ): string {
		return [ 'et' => 'et', 'ru' => 'ru', 'en' => 'en' ][ $lang ] ?? 'et';
	}

	/**
	 * URL of a functional page (calculator, checkout, account, terms, privacy)
	 * in the given language.
	 */
	public static function page_url( string $key, ?string $lang = null ): string {
		$id = self::page_id( $key, $lang );
		return $id ? (string) get_permalink( $id ) : home_url( '/' );
	}

	public static function page_id( string $key, ?string $lang = null ): int {
		$pages = (array) get_option( 'kipora_pages', [] );
		$id    = (int) ( $pages[ $key ] ?? 0 );
		if ( $id && function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $id, $lang ?? self::current() );
			if ( $translated ) {
				$id = (int) $translated;
			}
		}
		return $id;
	}
}
