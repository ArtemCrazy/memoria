<?php
/**
 * Cemetery card: facts about the cemetery, a map link, the grave search in the
 * national cemetery portal (kalmistud.ee) and an order button that opens the
 * calculator with this cemetery already chosen.
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;

$facts = array_filter(
	[
		__( 'Address', 'kipora' )    => $attributes['address'],
		__( 'District', 'kipora' )   => $attributes['district'],
		__( 'Area', 'kipora' )       => $attributes['area'],
		__( 'Managed by', 'kipora' ) => $attributes['manager'],
	]
);
$lat     = (float) $attributes['lat'];
$lon     = (float) $attributes['lon'];
$map     = $lat && $lon ? sprintf( 'https://www.openstreetmap.org/?mlat=%1$.6F&mlon=%2$.6F#map=16/%1$.6F/%2$.6F', $lat, $lon ) : '';
$route   = $lat && $lon ? sprintf( 'https://www.google.com/maps/dir/?api=1&destination=%1$.6F,%2$.6F', $lat, $lon ) : '';
$portal  = preg_match( '/^\d+$/', (string) $attributes['portal'] ) ? 'https://www.kalmistud.ee/cemetery/' . $attributes['portal'] : '';
$preset  = array_filter( [ 'direction' => 'grave', 'cemetery' => (string) $attributes['cemetery'] ] );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'cemetery-card kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( $facts ) : ?>
		<dl class="cemetery-card__facts" data-reveal="stagger">
			<?php foreach ( $facts as $name => $value ) : ?>
				<div class="cemetery-card__fact">
					<dt><?php echo esc_html( $name ); ?></dt>
					<dd><?php echo kipora_block_text( $value ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
	<div class="cemetery-card__actions" data-reveal="fade-up">
		<?php if ( $attributes['buttonText'] ) : ?>
			<a class="kp-button kp-button--primary" href="<?php echo esc_url( kipora_theme_calculator_url( $preset ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['buttonText'] ) ); ?></a>
		<?php endif; ?>
		<?php if ( $portal ) : ?>
			<a class="kp-button kp-button--quiet" href="<?php echo esc_url( $portal ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Find a grave in the cemetery portal', 'kipora' ); ?></a>
		<?php endif; ?>
		<?php if ( $map ) : ?>
			<a class="kp-link" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'On the map', 'kipora' ); ?></a>
			<a class="kp-link" href="<?php echo esc_url( $route ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Directions', 'kipora' ); ?></a>
		<?php endif; ?>
	</div>
</section>
