<?php
/**
 * First screen. Without a chosen photo it shows the theme's lantern with the
 * looped candle video; leaves and the drawn line are decoration of the design.
 *
 * @var array    $attributes
 * @var string   $content    Rendered service cards.
 */
defined( 'ABSPATH' ) || exit;

$leaves = [
	[ 'blade' => 'M19 76C13 68 7 59 5 48 2 33 7 16 24 4c8 11 12 25 11 38-1 14-8 27-16 34Z', 'vein' => 'M19 74c-3-19-1-40 5-63', 'branches' => 'M17 57c-5-3-8-7-10-13m11 1c7-4 12-10 16-17M19 32c-4-3-7-7-9-12' ],
	[ 'blade' => 'M18 77C10 66 7 53 9 41c2-15 10-28 21-37 3 16 2 35-4 49-3 10-6 17-8 24Z', 'vein' => 'M18 75c-2-20 3-43 11-64', 'branches' => 'M17 58c-4-4-7-10-8-16m11 2c5-4 8-9 11-15M23 30c-3-3-4-7-5-11' ],
	[ 'blade' => 'M18 77C10 67 5 57 4 44 3 28 13 12 31 3c6 16 8 32 4 45-4 13-10 22-17 29Z', 'vein' => 'M18 75c0-23 6-44 14-65', 'branches' => 'M17 58c-6-3-9-8-12-14m15 1c7-4 12-10 15-17M24 30c-5-3-8-7-10-12' ],
	[ 'blade' => 'M19 77C11 65 7 50 9 36c2-14 9-24 18-32 7 15 8 29 4 43-3 12-7 22-12 30Z', 'vein' => 'M19 75c-2-22 2-43 8-64', 'branches' => 'M17 58c-4-4-7-9-8-15m10 1c6-4 10-9 13-15M22 31c-3-3-5-7-6-11' ],
];

$image    = (array) $attributes['image'];
$video    = (array) $attributes['video'];
$custom   = ! empty( $image['id'] );
$video_id = (int) ( $video['id'] ?? 0 );
$video_url = $video_id ? (string) wp_get_attachment_url( $video_id ) : '';
$title_id = kipora_block_id( 'hero-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'hero kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="hero__head">
		<?php if ( $attributes['features'] ) : ?>
			<p class="hero__features" data-reveal="fade-up"><?php echo kipora_block_text( $attributes['features'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<svg data-reveal="fade-in" style="--reveal-delay:.1s" class="hero__divider" viewBox="0 0 69 6" aria-hidden="true" focusable="false"><path d="M1 3c5.7-3 11.3 3 17 0s11.3-3 17 0 11.3 3 17 0 11.3-3 16 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
		<?php endif; ?>
		<h1 class="hero__title" id="<?php echo esc_attr( $title_id ); ?>" data-reveal="heading" style="--reveal-delay:.15s"><?php echo kipora_block_text( $attributes['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
		<?php if ( $attributes['lead'] ) : ?>
			<p class="hero__lead" data-reveal="fade-up" style="--reveal-delay:.45s"><?php echo kipora_block_text( $attributes['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php endif; ?>
		<?php if ( $attributes['buttonText'] ) : ?>
			<a data-reveal="fade-up" style="--reveal-delay:.55s" class="kp-button kp-button--primary kp-button--large hero__cta" href="<?php echo esc_url( kipora_block_url( $attributes['buttonUrl'], 'calculator' ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['buttonText'] ) ); ?></a>
		<?php endif; ?>
	</div>

	<div class="hero__stage">
		<svg class="hero__line" viewBox="0 0 1920 800" fill="none" aria-hidden="true" focusable="false"><path class="hero__line-path" pathLength="1" d="M-20 520c170-110 250-270 420-190s110 300 290 250 190-300 330-280 150 250 280 190 220-280 360-230 170 260 280 170" stroke="currentColor" stroke-width="18" stroke-linecap="round"/></svg>
		<div class="hero__figure">
			<div class="hero__photo" data-reveal="pop" style="--reveal-delay:.6s">
				<?php echo kipora_block_image( $image, $attributes['fallback'], 'hero__still', 760, 950, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( $video_url ) : ?>
					<video class="hero__video" autoplay muted loop playsinline preload="auto" aria-hidden="true">
						<source src="<?php echo esc_url( $video_url ); ?>" type="<?php echo esc_attr( (string) get_post_mime_type( $video_id ) ); ?>">
					</video>
				<?php elseif ( ! $custom && 'hero-lantern' === $attributes['fallback'] ) : ?>
					<video class="hero__video" autoplay muted loop playsinline preload="auto" aria-hidden="true" poster="<?php echo esc_url( kipora_theme_image( 'hero-lantern' )[0] ); ?>">
						<source src="<?php echo esc_url( kipora_theme_asset( 'video/hero-lantern.webm' ) ); ?>" type="video/webm">
						<source src="<?php echo esc_url( kipora_theme_asset( 'video/hero-lantern.mp4' ) ); ?>" type="video/mp4">
					</video>
				<?php endif; ?>
			</div>
			<?php foreach ( $leaves as $index => $leaf ) : ?>
				<svg data-reveal="fade-in" style="--reveal-delay:<?php echo esc_attr( 1 + $index * 0.1 ); ?>s" class="hero__leaf hero__leaf--<?php echo (int) ( $index + 1 ); ?>" viewBox="0 0 40 80" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $leaf['blade'] ); ?>" fill="currentColor"/><path d="<?php echo esc_attr( $leaf['vein'] ); ?>" fill="none" stroke="#fff" stroke-opacity=".58" stroke-width="1.3" stroke-linecap="round"/><path d="<?php echo esc_attr( $leaf['branches'] ); ?>" fill="none" stroke="#fff" stroke-opacity=".38" stroke-width=".8" stroke-linecap="round"/></svg>
			<?php endforeach; ?>
		</div>

		<?php if ( trim( $content ) ) : ?>
			<div class="hero__cards" data-reveal="stagger" style="--reveal-delay:1s"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput — rendered inner blocks ?></div>
		<?php endif; ?>
	</div>
</section>
