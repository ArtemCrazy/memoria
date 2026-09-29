<?php
/** @var string $calculator */
defined( 'ABSPATH' ) || exit;
?>
<div class="kp-empty">
	<p><?php esc_html_e( 'Choose a service in the calculator first: the order is made from it.', 'kipora' ); ?></p>
	<a class="kp-button kp-button--primary" href="<?php echo esc_url( $calculator ); ?>"><?php esc_html_e( 'Open the calculator', 'kipora' ); ?></a>
</div>
