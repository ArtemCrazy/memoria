<?php
/**
 * Top of an inner page: the page headline (h1), a lead, the main button and
 * an optional second link, a photo on the right on wide screens. The button
 * opens the calculator with the preset choice unless it has a link of its own.
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;

$title_id = kipora_block_id( 'page-title' );
$url      = trim( $attributes['buttonUrl'] ) ?: kipora_theme_calculator_url( (array) $attributes['preset'] );
$image    = kipora_block_image( (array) $attributes['image'], $attributes['fallback'], 'page-intro__photo', 560, 600, false );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'page-intro kp-block' . ( $image ? ' page-intro--photo' : '' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="page-intro__text">
		<?php echo kipora_block_heading( $attributes['title'], $title_id, 'page-intro__head', 'h1' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $attributes['lead'] ) : ?>
			<p class="page-intro__lead" data-reveal="fade-up" style="--reveal-delay:.2s"><?php echo kipora_block_text( $attributes['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php endif; ?>
		<?php if ( $attributes['buttonText'] || $attributes['secondText'] ) : ?>
			<div class="page-intro__actions" data-reveal="fade-up" style="--reveal-delay:.3s">
				<?php if ( $attributes['buttonText'] ) : ?>
					<a class="kp-button kp-button--primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['buttonText'] ) ); ?></a>
				<?php endif; ?>
				<?php if ( $attributes['secondText'] && $attributes['secondUrl'] ) : ?>
					<a class="kp-button kp-button--quiet" href="<?php echo esc_url( $attributes['secondUrl'] ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['secondText'] ) ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $image ) : ?>
		<div class="page-intro__media" data-reveal="pop" style="--reveal-delay:.3s"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php endif; ?>
</section>
