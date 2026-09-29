<?php
/**
 * Home page. Title and excerpt of the page feed the hero; the rest of the
 * page is regular block content the client edits in each language.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="hero">
		<div class="hero__text">
			<h1 class="hero__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<div class="hero__actions">
				<a class="kp-button kp-button--primary" href="<?php echo esc_url( kipora_theme_page( 'calculator' ) ); ?>"><?php esc_html_e( 'Calculate the price', 'kipora' ); ?></a>
				<a class="kp-button kp-button--quiet" href="<?php echo esc_url( kipora_theme_page( 'account' ) ); ?>"><?php esc_html_e( 'My memorial cards', 'kipora' ); ?></a>
			</div>
		</div>
		<div class="hero__map">
			<?php get_template_part( 'parts/plot-map' ); ?>
		</div>
	</section>
	<div class="entry">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
