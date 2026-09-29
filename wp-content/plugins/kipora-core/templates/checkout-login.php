<?php
/**
 * @var array  $quote
 * @var string $calculator
 * @var string $cemetery
 * @var string $login
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="kp-checkout">
	<aside class="kp-checkout__aside">
		<h2 class="kp-checkout__heading"><?php esc_html_e( 'Your order', 'kipora' ); ?></h2>
		<?php if ( $cemetery ) : ?>
			<p class="kp-checkout__place"><?php echo esc_html( $cemetery ); ?></p>
		<?php endif; ?>
		<?php
		$lines = $quote['lines'];
		$total = $quote['total'];
		include __DIR__ . '/parts/summary.php';
		?>
		<a class="kp-link" href="<?php echo esc_url( $calculator ); ?>"><?php esc_html_e( 'Change the order', 'kipora' ); ?></a>
	</aside>
	<div class="kp-checkout__main">
		<?php echo $login; // phpcs:ignore WordPress.Security.EscapeOutput — rendered template ?>
	</div>
</div>
