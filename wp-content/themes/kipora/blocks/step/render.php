<?php
/**
 * Step card. The step number is a CSS counter in the order of the cards.
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;
?>
<li <?php echo get_block_wrapper_attributes( [ 'class' => 'step' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php echo kipora_block_image( (array) $attributes['image'], $attributes['fallback'] ?: 'step-1', 'step__photo', 560, 400 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<p class="step__label">
		<?php
		/* translators: %s: step number, filled in by the page in the order of the cards */
		$label = explode( '%s', __( 'Step %s', 'kipora' ), 2 );
		echo esc_html( $label[0] ) . '<span class="step__number"></span>' . esc_html( $label[1] ?? '' );
		?>
	</p>
	<h3 class="step__title"><?php echo kipora_block_text( $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
	<?php if ( $attributes['text'] ) : ?>
		<p class="step__text"><?php echo kipora_block_text( $attributes['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	<?php endif; ?>
</li>
