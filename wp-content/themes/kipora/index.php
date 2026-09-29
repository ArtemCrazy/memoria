<?php
defined( 'ABSPATH' ) || exit;
get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'entry-wrap' ); ?>>
			<header class="page-head">
				<h1 class="page-head__title"><?php the_title(); ?></h1>
			</header>
			<div class="entry"><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
else :
	?>
	<header class="page-head">
		<h1 class="page-head__title"><?php esc_html_e( 'Page not found', 'kipora' ); ?></h1>
	</header>
	<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the home page', 'kipora' ); ?></a></p>
	<?php
endif;
get_footer();
