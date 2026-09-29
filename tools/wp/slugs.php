<?php
/**
 * Latin slugs for RU / EN pages (free Polylang needs unique slugs) and a
 * Polylang cache reset so each language knows its front page.
 */

defined( 'ABSPATH' ) || exit;

$slugs = [
	'calculator' => [ 'ru' => 'kalkulyator', 'en' => 'calculator' ],
	'checkout'   => [ 'ru' => 'zakaz', 'en' => 'order' ],
	'account'    => [ 'ru' => 'kabinet', 'en' => 'account' ],
	'about'      => [ 'ru' => 'o-servise', 'en' => 'about' ],
	'contact'    => [ 'ru' => 'kontakty', 'en' => 'contact' ],
	'terms'      => [ 'ru' => 'usloviya-prodazhi', 'en' => 'terms' ],
	'privacy'    => [ 'ru' => 'politika-konfidencialnosti', 'en' => 'privacy' ],
	'home'       => [ 'ru' => 'glavnaya', 'en' => 'home' ],
];
$map = (array) get_option( 'kipora_setup_pages', [] );
foreach ( $slugs as $key => $langs ) {
	foreach ( $langs as $lang => $slug ) {
		$id = (int) ( $map[ $key ][ $lang ] ?? 0 );
		if ( $id && get_post_field( 'post_name', $id ) !== $slug ) {
			wp_update_post( [ 'ID' => $id, 'post_name' => $slug ] );
			echo "{$key}/{$lang} → {$slug}\n";
		}
	}
}

if ( function_exists( 'PLL' ) && PLL() && PLL()->model ) {
	PLL()->model->clean_languages_cache();
}
delete_transient( 'pll_languages_list' );
// Rebuilt on the next normal request, when Polylang adds language prefixes.
delete_option( 'rewrite_rules' );
echo "ok\n";
