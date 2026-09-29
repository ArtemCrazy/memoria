<?php
/** @var array $notice {type, text} */
defined( 'ABSPATH' ) || exit;
if ( empty( $notice['text'] ) ) {
	return;
}
?>
<div class="kp-notice kp-notice--<?php echo esc_attr( $notice['type'] ); ?>" role="<?php echo 'error' === $notice['type'] ? 'alert' : 'status'; ?>">
	<?php echo esc_html( $notice['text'] ); ?>
</div>
