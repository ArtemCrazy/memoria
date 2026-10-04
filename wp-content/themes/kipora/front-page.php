<?php
/**
 * Home page: built from blocks in the editor (first screen, steps, questions…),
 * separately in each language.
 */
defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="entry entry--blocks">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
