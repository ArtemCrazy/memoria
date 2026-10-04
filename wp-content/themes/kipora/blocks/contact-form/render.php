<?php
/**
 * The message form of the KIPORA plugin with a heading and a hint beside it.
 * The form inside has the id "kp-contact", so a button can link to #kp-contact.
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;

$title_id = kipora_block_id( 'contact-form-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'contact-form kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="contact-form__intro">
		<?php echo kipora_block_heading( $attributes['title'] ?: esc_html__( 'Write to us', 'kipora' ), $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $attributes['hint'] ) : ?>
			<p class="contact-form__hint" data-reveal="fade-up"><?php echo kipora_block_text( $attributes['hint'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php endif; ?>
	</div>
	<div class="contact-form__body" data-reveal="fade-up" style="--reveal-delay:.15s">
		<?php echo do_shortcode( '[kipora_contact]' ); // phpcs:ignore WordPress.Security.EscapeOutput — rendered form ?>
	</div>
</section>
