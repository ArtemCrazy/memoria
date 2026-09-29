<?php
/**
 * Page after Montonio: paid, still waiting, or failed.
 *
 * @var array  $order
 * @var string $account
 * @var array  $notice
 */
defined( 'ABSPATH' ) || exit;

use Kipora\Orders;

$status = $order['status'];
?>
<div class="kp-result kp-result--<?php echo esc_attr( $status ); ?>">
	<?php include __DIR__ . '/parts/notice.php'; ?>

	<?php if ( in_array( $status, [ Orders::PAID, Orders::WORKING, Orders::DONE ], true ) ) : ?>
		<h2 class="kp-result__title"><?php esc_html_e( 'Thank you, the order is paid', 'kipora' ); ?></h2>
		<p class="kp-result__text">
			<?php
			/* translators: %s: order number */
			printf( esc_html__( 'Order %s is confirmed. We have sent a confirmation to your email. When the work is done, the photo report will appear in your account.', 'kipora' ), '<strong>' . esc_html( $order['reference'] ) . '</strong>' );
			?>
		</p>
	<?php elseif ( Orders::AWAITING === $status ) : ?>
		<h2 class="kp-result__title"><?php esc_html_e( 'Waiting for payment confirmation', 'kipora' ); ?></h2>
		<p class="kp-result__text"><?php esc_html_e( 'The bank usually confirms the payment within a minute. This page updates by itself.', 'kipora' ); ?></p>
		<?php if ( empty( $notice['text'] ) ) : ?>
			<meta http-equiv="refresh" content="8">
		<?php endif; ?>
	<?php else : ?>
		<h2 class="kp-result__title"><?php esc_html_e( 'The payment did not go through', 'kipora' ); ?></h2>
		<p class="kp-result__text"><?php esc_html_e( 'No money was charged. You can try again or choose another payment method.', 'kipora' ); ?></p>
	<?php endif; ?>

	<?php
	$lines = $order['lines'];
	$total = $order['total'];
	include __DIR__ . '/parts/summary.php';
	?>

	<div class="kp-result__actions">
		<?php if ( in_array( $status, [ Orders::AWAITING, Orders::FAILED ], true ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kipora_pay_again">
				<input type="hidden" name="order" value="<?php echo esc_attr( $order['id'] ); ?>">
				<input type="hidden" name="lang" value="<?php echo esc_attr( $order['lang'] ); ?>">
				<?php wp_nonce_field( 'kipora_pay_again' ); ?>
				<button type="submit" class="kp-button kp-button--primary"><?php esc_html_e( 'Pay again', 'kipora' ); ?></button>
			</form>
		<?php endif; ?>
		<a class="kp-button kp-button--quiet" href="<?php echo esc_url( add_query_arg( 'view', 'orders', $account ) ); ?>"><?php esc_html_e( 'Go to my orders', 'kipora' ); ?></a>
	</div>
</div>
