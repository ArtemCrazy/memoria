<?php
/**
 * Comparison. Rows are stored as child blocks; on the site they are laid out
 * by column (labels, "yourself", KIPORA), so this template reads their fields
 * instead of printing them one by one.
 *
 * @var array    $attributes
 * @var WP_Block $block
 */
defined( 'ABSPATH' ) || exit;

$rows = [];
foreach ( $block->inner_blocks as $inner ) {
	$rows[] = [
		kipora_block_text( (string) ( $inner->attributes['label'] ?? '' ) ),
		kipora_block_text( (string) ( $inner->attributes['base'] ?? '' ) ),
		kipora_block_text( (string) ( $inner->attributes['brand'] ?? '' ) ),
	];
}
$title_id = kipora_block_id( 'compare-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'compare kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<?php echo kipora_block_heading( $attributes['title'], $title_id, 'section-head--center' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<div class="compare__grid" data-reveal="stagger">
		<div class="compare__col compare__col--labels" aria-hidden="true">
			<p class="compare__head">&nbsp;</p>
			<?php foreach ( $rows as $row ) : ?>
				<p class="compare__cell"><?php echo $row[0]; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endforeach; ?>
		</div>
		<?php foreach ( [ 1 => $attributes['baseTitle'], 2 => $attributes['brandTitle'] ] as $col => $head ) : ?>
			<div class="compare__col <?php echo 2 === $col ? 'compare__col--brand' : 'compare__col--base'; ?>">
				<h3 class="compare__head"><?php echo kipora_block_text( $head ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
				<dl class="compare__list">
					<?php foreach ( $rows as $row ) : ?>
						<div class="compare__cell">
							<dt class="compare__label"><?php echo $row[0]; // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
							<dd class="compare__value"><?php echo $row[ $col ]; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $attributes['quote'] || $attributes['buttonText'] ) : ?>
		<div class="compare__cta" data-reveal="fade-up">
			<?php if ( $attributes['quote'] ) : ?>
				<p class="quote"><?php echo kipora_block_text( $attributes['quote'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<?php if ( $attributes['buttonText'] ) : ?>
				<a class="kp-button kp-button--primary" href="<?php echo esc_url( kipora_block_url( $attributes['buttonUrl'], 'calculator' ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['buttonText'] ) ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
