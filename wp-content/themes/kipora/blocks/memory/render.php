<?php
/**
 * Heading, text and a numbered list; a photo with a small card pinned on it.
 *
 * @var array  $attributes
 * @var string $content    Rendered list items.
 */
defined( 'ABSPATH' ) || exit;

$title_id = kipora_block_id( 'memory-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'memory kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="memory__content">
		<?php echo kipora_block_heading( $attributes['title'], $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $attributes['lead'] ) : ?>
			<p class="memory__lead" data-reveal="fade-up"><?php echo kipora_block_text( $attributes['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php endif; ?>
		<ol class="numbered numbered--large" role="list" data-reveal="stagger"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></ol>
	</div>
	<div class="memory__media" data-reveal="fade-up">
		<?php echo kipora_block_image( (array) $attributes['image'], $attributes['fallback'], 'memory__photo', 500, 550 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $attributes['cardText'] ) : ?>
			<div class="memory__card" data-reveal="pop" style="--reveal-delay:.35s">
				<?php echo kipora_block_image( (array) $attributes['cardImage'], $attributes['cardFallback'], 'memory__card-photo', 320, 320 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="memory__card-text"><?php echo kipora_block_text( $attributes['cardText'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
