<?php
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	// A page built from blocks brings its own headline (the "Page heading" block).
	$own_head = has_block( 'kipora/page-intro' );
	?>
	<article <?php post_class( 'entry-wrap' ); ?>>
		<?php kipora_theme_breadcrumbs(); ?>
		<?php if ( ! $own_head ) : ?>
			<header class="page-head">
				<h1 class="page-head__title"><?php the_title(); ?></h1>
			</header>
		<?php endif; ?>
		<div class="entry<?php echo $own_head ? ' entry--blocks' : ''; ?>">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
