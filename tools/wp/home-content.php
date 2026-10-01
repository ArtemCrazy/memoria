<?php
/**
 * Home page sections are now part of the theme (parts/home-sections.php):
 * clear the old draft blocks so they do not repeat below them, and register
 * contact/about as functional pages for language-aware links.
 */

defined( 'ABSPATH' ) || exit;

$map = (array) get_option( 'kipora_setup_pages', [] );
foreach ( (array) ( $map['home'] ?? [] ) as $lang => $id ) {
	wp_update_post( [ 'ID' => (int) $id, 'post_content' => '' ] );
	echo "home/{$lang} cleared\n";
}
$pages            = (array) get_option( 'kipora_pages', [] );
$pages['contact'] = (int) ( $map['contact']['et'] ?? 0 );
$pages['about']   = (int) ( $map['about']['et'] ?? 0 );
update_option( 'kipora_pages', $pages );
echo json_encode( $pages ), "\n";
