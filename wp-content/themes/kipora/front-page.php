<?php
/**
 * Home page. The hero headline is the page title (words between *asterisks*
 * are set in italic), the lead is the page excerpt. The rest of the page is
 * block content the client edits in each language.
 */
defined( 'ABSPATH' ) || exit;

$calculator = kipora_theme_page( 'calculator' );
$img        = static fn( string $name ): string => add_query_arg( 'v', (string) filemtime( get_theme_file_path( "assets/img/{$name}" ) ), get_theme_file_uri( "assets/img/{$name}" ) );
$leaves     = [
	[ 'blade' => 'M19 76C13 68 7 59 5 48 2 33 7 16 24 4c8 11 12 25 11 38-1 14-8 27-16 34Z', 'vein' => 'M19 74c-3-19-1-40 5-63', 'branches' => 'M17 57c-5-3-8-7-10-13m11 1c7-4 12-10 16-17M19 32c-4-3-7-7-9-12' ],
	[ 'blade' => 'M18 77C10 66 7 53 9 41c2-15 10-28 21-37 3 16 2 35-4 49-3 10-6 17-8 24Z', 'vein' => 'M18 75c-2-20 3-43 11-64', 'branches' => 'M17 58c-4-4-7-10-8-16m11 2c5-4 8-9 11-15M23 30c-3-3-4-7-5-11' ],
	[ 'blade' => 'M18 77C10 67 5 57 4 44 3 28 13 12 31 3c6 16 8 32 4 45-4 13-10 22-17 29Z', 'vein' => 'M18 75c0-23 6-44 14-65', 'branches' => 'M17 58c-6-3-9-8-12-14m15 1c7-4 12-10 15-17M24 30c-5-3-8-7-10-12' ],
	[ 'blade' => 'M19 77C11 65 7 50 9 36c2-14 9-24 18-32 7 15 8 29 4 43-3 12-7 22-12 30Z', 'vein' => 'M19 75c-2-22 2-43 8-64', 'branches' => 'M17 58c-4-4-7-9-8-15m10 1c6-4 10-9 13-15M22 31c-3-3-5-7-6-11' ],
];

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="hero" aria-labelledby="hero-title">
		<div class="hero__head">
			<p class="hero__features"><?php esc_html_e( 'Tallinn • Harju County • Photos before and after', 'kipora' ); ?></p>
			<svg class="hero__divider" viewBox="0 0 69 6" aria-hidden="true" focusable="false"><path d="M1 3c5.7-3 11.3 3 17 0s11.3-3 17 0 11.3 3 17 0 11.3-3 16 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
			<h1 class="hero__title" id="hero-title"><?php echo kipora_theme_emphasis( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput — escaped inside ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<a class="kp-button kp-button--primary kp-button--large hero__cta" href="<?php echo esc_url( $calculator ); ?>"><?php esc_html_e( 'Calculate the price', 'kipora' ); ?></a>
		</div>

		<div class="hero__stage">
			<svg class="hero__line" viewBox="0 0 1920 800" fill="none" aria-hidden="true" focusable="false"><path class="hero__line-path" pathLength="1" d="M-20 520c170-110 250-270 420-190s110 300 290 250 190-300 330-280 150 250 280 190 220-280 360-230 170 260 280 170" stroke="currentColor" stroke-width="18" stroke-linecap="round"/></svg>
			<div class="hero__figure">
				<picture class="hero__photo">
					<source srcset="<?php echo esc_url( $img( 'hero-lantern.webp' ) ); ?>" type="image/webp">
					<img src="<?php echo esc_url( $img( 'hero-lantern.jpg' ) ); ?>" width="760" height="950" alt="<?php esc_attr_e( 'Grave lantern with a burning candle', 'kipora' ); ?>" fetchpriority="high">
				</picture>
				<?php foreach ( $leaves as $index => $leaf ) : ?>
					<svg class="hero__leaf hero__leaf--<?php echo (int) ( $index + 1 ); ?>" viewBox="0 0 40 80" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $leaf['blade'] ); ?>" fill="currentColor"/><path d="<?php echo esc_attr( $leaf['vein'] ); ?>" fill="none" stroke="#fff" stroke-opacity=".58" stroke-width="1.3" stroke-linecap="round"/><path d="<?php echo esc_attr( $leaf['branches'] ); ?>" fill="none" stroke="#fff" stroke-opacity=".38" stroke-width=".8" stroke-linecap="round"/></svg>
				<?php endforeach; ?>
			</div>

			<div class="hero__cards">
				<a class="hero-card hero-card--grave" href="<?php echo esc_url( add_query_arg( 'sel', kipora_theme_selection( 'grave' ), $calculator ) ); ?>">
					<picture class="hero-card__photo">
						<source srcset="<?php echo esc_url( $img( 'card-grave.webp' ) ); ?>" type="image/webp">
						<img src="<?php echo esc_url( $img( 'card-grave.jpg' ) ); ?>" width="560" height="560" alt="" loading="lazy">
					</picture>
					<span class="hero-card__body">
						<span class="hero-card__title"><?php esc_html_e( 'Grave care', 'kipora' ); ?></span>
						<span class="hero-card__text"><?php esc_html_e( 'Once or for the whole season', 'kipora' ); ?></span>
					</span>
				</a>
				<a class="hero-card hero-card--pet" href="<?php echo esc_url( add_query_arg( 'sel', kipora_theme_selection( 'pet' ), $calculator ) ); ?>">
					<picture class="hero-card__photo">
						<source srcset="<?php echo esc_url( $img( 'card-pet.webp' ) ); ?>" type="image/webp">
						<img src="<?php echo esc_url( $img( 'card-pet.jpg' ) ); ?>" width="560" height="560" alt="" loading="lazy">
					</picture>
					<span class="hero-card__body">
						<span class="hero-card__title"><?php esc_html_e( 'Pets', 'kipora' ); ?></span>
						<span class="hero-card__text"><?php esc_html_e( 'Cremation, urn and memorial page', 'kipora' ); ?></span>
					</span>
				</a>
			</div>
		</div>
	</section>

	<?php get_template_part( 'parts/home-sections' ); ?>

	<?php if ( trim( get_the_content() ) ) : ?>
		<div class="entry">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
