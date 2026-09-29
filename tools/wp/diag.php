<?php
defined( 'ABSPATH' ) || exit;
echo 'stylesheet: ', get_stylesheet(), "\n";
echo 'mods: ', json_encode( get_theme_mod( 'nav_menu_locations' ) ), "\n";
$o = get_option( 'polylang' );
echo 'pll nav: ', json_encode( $o['nav_menus'] ?? null ), "\n";
echo 'menus: ', json_encode( wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' ) ), "\n";
echo 'items in primary et: ', count( (array) wp_get_nav_menu_items( 'KIPORA primary et' ) ), "\n";
