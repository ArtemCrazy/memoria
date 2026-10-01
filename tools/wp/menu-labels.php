<?php
/**
 * Short menu labels where the page title is too long for the header.
 * The page itself keeps its full title.
 */

defined( 'ABSPATH' ) || exit;

$map    = (array) get_option( 'kipora_setup_pages', [] );
$labels = [ 'ru' => [ 'calculator' => 'Калькулятор' ] ];

foreach ( $labels as $lang => $items ) {
	$menu = wp_get_nav_menu_object( "KIPORA primary {$lang}" );
	if ( ! $menu ) {
		continue;
	}
	foreach ( wp_get_nav_menu_items( $menu->term_id ) as $item ) {
		foreach ( $items as $key => $label ) {
			if ( (int) $item->object_id === (int) ( $map[ $key ][ $lang ] ?? 0 ) ) {
				update_post_meta( $item->ID, '_menu_item_title', $label );
				wp_update_post( [ 'ID' => $item->ID, 'post_title' => $label ] );
				echo "{$lang}/{$key}: {$label}\n";
			}
		}
	}
}
