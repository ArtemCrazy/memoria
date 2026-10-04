<?php
/**
 * Cards linking to subpages: the cemeteries under "Cemeteries", the services
 * under "Pets". New subpages appear here by themselves, in menu order.
 *
 * @var array    $attributes
 * @var WP_Block $block
 */
defined( 'ABSPATH' ) || exit;

$parent = (int) $attributes['parent'] ?: (int) ( $block->context['postId'] ?? get_the_ID() );
$pages  = $parent ? get_pages(
	[
		'parent'      => $parent,
		'sort_column' => 'menu_order,post_title',
		'post_status' => 'publish',
	]
) : [];
if ( ! $pages ) {
	return;
}
$title_id = kipora_block_id( 'subpages-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'page-cards kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $attributes['title'] ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>
	<?php if ( $attributes['title'] ) : ?>
		<?php echo kipora_block_heading( $attributes['title'], $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php endif; ?>
	<ul class="page-cards__list" role="list" data-reveal="stagger">
		<?php foreach ( $pages as $page ) : ?>
			<li class="page-cards__item">
				<a class="page-cards__link" href="<?php echo esc_url( get_permalink( $page ) ); ?>">
					<span class="page-cards__title"><?php echo esc_html( str_replace( '*', '', get_the_title( $page ) ) ); ?></span>
					<?php if ( has_excerpt( $page ) ) : ?>
						<span class="page-cards__text"><?php echo esc_html( get_the_excerpt( $page ) ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
