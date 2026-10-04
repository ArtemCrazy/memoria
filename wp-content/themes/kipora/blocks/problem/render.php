<?php
/**
 * Photo with two paper leaves, a heading with a numbered list, a quote below.
 *
 * @var array  $attributes
 * @var string $content    Rendered list items.
 */
defined( 'ABSPATH' ) || exit;

$title_id = kipora_block_id( 'problem-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'problem kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="problem__media" data-reveal="fade-up">
		<?php echo kipora_block_image( (array) $attributes['image'], $attributes['fallback'], 'problem__photo', 560, 600 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php foreach ( [ 1, 2 ] as $n ) : ?>
			<svg class="problem__leaf problem__leaf--<?php echo (int) $n; ?>" viewBox="0 0 40 80" aria-hidden="true" focusable="false"><path d="M20 2C34 18 38 44 20 78 2 44 6 18 20 2Z" fill="currentColor"/></svg>
		<?php endforeach; ?>
	</div>
	<div class="problem__content">
		<?php echo kipora_block_heading( $attributes['title'], $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<ol class="numbered" role="list" data-reveal="stagger"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></ol>
	</div>
	<?php if ( $attributes['quote'] ) : ?>
		<p class="quote problem__quote" data-reveal="fade-up"><?php echo kipora_block_text( $attributes['quote'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	<?php endif; ?>
</section>
