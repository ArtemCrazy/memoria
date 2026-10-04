<?php
/**
 * Service card on the first screen. Links to the calculator with the direction
 * already chosen, unless a link of its own is set.
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;

$direction = 'pet' === $attributes['direction'] ? 'pet' : 'grave';
$url       = trim( $attributes['url'] ) ?: add_query_arg( 'sel', kipora_theme_selection( $direction ), kipora_theme_page( 'calculator' ) );
?>
<a <?php echo get_block_wrapper_attributes( [ 'class' => 'hero-card hero-card--' . $direction ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> href="<?php echo esc_url( $url ); ?>">
	<?php echo kipora_block_image( (array) $attributes['image'], $attributes['fallback'] ?: 'card-' . $direction, 'hero-card__photo', 560, 560 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="hero-card__body">
		<span class="hero-card__title"><?php echo kipora_block_text( $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php if ( $attributes['text'] ) : ?>
			<span class="hero-card__text"><?php echo kipora_block_text( $attributes['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php endif; ?>
	</span>
</a>
