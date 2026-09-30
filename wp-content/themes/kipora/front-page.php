<?php
/**
 * Home page. The hero headline is the page title (words between *asterisks*
 * are set in italic), the lead is the page excerpt. The rest of the page is
 * block content the client edits in each language.
 */
defined( 'ABSPATH' ) || exit;

$calculator = kipora_theme_page( 'calculator' );
$img        = static fn( string $name ): string => add_query_arg( 'v', (string) filemtime( get_theme_file_path( "assets/img/{$name}" ) ), get_theme_file_uri( "assets/img/{$name}" ) );

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
				<?php foreach ( [ 1, 2, 3, 4 ] as $n ) : ?>
					<svg class="hero__leaf hero__leaf--<?php echo (int) $n; ?>" viewBox="0 0 40 80" aria-hidden="true" focusable="false"><path d="M20 2C34 18 38 44 20 78 2 44 6 18 20 2Z" fill="currentColor"/><path d="M20 10v62" stroke="#fff" stroke-opacity=".45" stroke-width="1.5"/></svg>
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

	<div class="entry">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
