<?php
/**
 * Steps on a light band: heading, a label between two lines, step cards.
 *
 * @var array  $attributes
 * @var string $content    Rendered step cards.
 */
defined( 'ABSPATH' ) || exit;

$title_id = kipora_block_id( 'steps-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'steps kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<svg class="steps__blob" viewBox="0 0 200 200" aria-hidden="true" focusable="false"><path d="M200 0v160c-18 22-52 28-70 10-20 20-58 14-66-12-30 4-52-22-40-50C-2 96 2 58 30 50 26 22 52 0 80 0Z" fill="currentColor"/></svg>
	<div class="steps__top">
		<?php echo kipora_block_heading( $attributes['title'], $title_id, 'section-head--left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php if ( $attributes['label'] ) : ?>
		<p class="rule-label" data-reveal="fade-in"><span class="rule-label__text"><?php echo kipora_block_text( $attributes['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></p>
	<?php endif; ?>
	<ol class="steps__list" role="list" data-reveal="stagger"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></ol>
</section>
