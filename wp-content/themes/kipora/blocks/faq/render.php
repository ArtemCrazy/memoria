<?php
/**
 * Questions that open on click, and a link for the questions not listed.
 *
 * @var array  $attributes
 * @var string $content    Rendered questions.
 */
defined( 'ABSPATH' ) || exit;

$title_id = kipora_block_id( 'faq-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'faq kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<?php echo kipora_block_heading( $attributes['title'], $title_id, 'section-head--center' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<div class="faq__list" data-reveal="stagger"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php if ( $attributes['moreText'] || $attributes['linkText'] ) : ?>
		<p class="faq__more" data-reveal="fade-up">
			<?php echo kipora_block_text( $attributes['moreText'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $attributes['linkText'] ) : ?>
				<a class="kp-link" href="<?php echo esc_url( kipora_block_url( $attributes['linkUrl'], 'contact' ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['linkText'] ) ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</section>
