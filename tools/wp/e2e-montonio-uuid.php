<?php
/**
 * E2E helper: prints the Montonio order id of the newest order, so the test
 * can send a payment notice the site accepts (it checks this id).
 */

defined( 'ABSPATH' ) || exit;

$latest = get_posts(
	[
		'post_type'      => Kipora\Orders::TYPE,
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'fields'         => 'ids',
	]
);
echo $latest ? (string) get_post_meta( (int) $latest[0], 'kp_montonio_uuid', true ) : '';
