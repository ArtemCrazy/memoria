<?php
/**
 * Contacts in a row (small label, large value), only those filled in
 * KIPORA → Settings. Nothing is shown while all three are empty.
 */
defined( 'ABSPATH' ) || exit;

$contacts = class_exists( 'Kipora\Settings' ) ? Kipora\Settings::contacts() : [];
if ( ! $contacts ) {
	return;
}
?>
<dl <?php echo get_block_wrapper_attributes( [ 'class' => 'contact-hero__list kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> data-reveal="stagger">
	<?php foreach ( $contacts as $item ) : ?>
		<div class="contact-hero__item">
			<dt class="contact-hero__label"><?php echo esc_html( $item['label'] ); ?></dt>
			<dd class="contact-hero__value">
				<?php if ( $item['href'] ) : ?>
					<a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo esc_html( $item['value'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $item['value'] ); ?>
				<?php endif; ?>
			</dd>
		</div>
	<?php endforeach; ?>
</dl>
