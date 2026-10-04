<?php
/**
 * Price list read from the calculator tariffs (KIPORA → Hinnad in the admin),
 * so a price changed there changes here too. Groups:
 * grave (care by plot size), flowers (packages without a size), extras,
 * pet (cremation, transport, options), urns, memory (memorial page).
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Kipora\\TariffStore' ) ) {
	return;
}

$tariffs = Kipora\TariffStore::get();
$lang    = Kipora\Lang::current();
$label   = static fn( array $item ): string => Kipora\Pricing::label( $item, $lang );
$money   = static fn( $cents ): string => Kipora\Pricing::format( (int) round( (float) $cents ) );
$grave   = (array) ( $tariffs['grave'] ?? [] );
$pet     = (array) ( $tariffs['pet'] ?? [] );
$sizes   = (array) ( $grave['sizes'] ?? [] );
$group   = (string) $attributes['group'];
$preset  = [ 'direction' => 'pet' ];
$rows    = [];
$table   = [];

switch ( $group ) {
	case 'grave':
		$preset = [ 'direction' => 'grave' ];
		foreach ( (array) ( $grave['packages'] ?? [] ) as $package ) {
			if ( empty( $package['per_size'] ) ) {
				continue;
			}
			$table[] = [
				'label'  => $label( $package ),
				'prices' => array_map( static fn( array $size ): string => $money( (int) $package['price'] * (float) $size['coef'] ), $sizes ),
			];
		}
		break;
	case 'flowers':
		$preset = [ 'direction' => 'grave' ];
		foreach ( (array) ( $grave['packages'] ?? [] ) as $package ) {
			if ( empty( $package['per_size'] ) ) {
				$rows[]           = [ $label( $package ), $money( $package['price'] ) ];
				$preset['package'] = $package['id'];
			}
		}
		break;
	case 'extras':
		$preset = [ 'direction' => 'grave' ];
		foreach ( (array) ( $grave['extras'] ?? [] ) as $extra ) {
			/* translators: %s: price */
			$rows[] = [ $label( $extra ), empty( $extra['per_size'] ) ? $money( $extra['price'] ) : sprintf( __( 'from %s', 'kipora' ), $money( $extra['price'] ) ) ];
		}
		if ( ! empty( $grave['km_price'] ) ) {
			/* translators: %s: price per kilometre */
			$rows[] = [ __( 'Travel to cemeteries outside Tallinn', 'kipora' ), sprintf( __( '%s per km', 'kipora' ), $money( $grave['km_price'] ) ) ];
		}
		break;
	case 'pet':
		foreach ( array_merge( (array) ( $pet['services'] ?? [] ), (array) ( $pet['options'] ?? [] ) ) as $item ) {
			$rows[] = [ $label( $item ), $money( $item['price'] ) ];
		}
		break;
	case 'urns':
		foreach ( (array) ( $pet['urns'] ?? [] ) as $item ) {
			if ( (int) $item['price'] > 0 ) {
				$rows[] = [ $label( $item ), $money( $item['price'] ) ];
			}
		}
		foreach ( (array) ( $pet['options'] ?? [] ) as $item ) {
			if ( 'engraving' === $item['id'] ) {
				$rows[] = [ $label( $item ), $money( $item['price'] ) ];
			}
		}
		break;
	case 'memory':
		foreach ( (array) ( $pet['options'] ?? [] ) as $item ) {
			if ( 'memory_page' === $item['id'] ) {
				$rows[] = [ $label( $item ), $money( $item['price'] ) ];
			}
		}
		break;
}

if ( ! $rows && ! $table ) {
	return;
}
$title_id = kipora_block_id( 'prices-title' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'price-list kp-block' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<?php echo kipora_block_heading( $attributes['title'] ?: esc_html__( 'Prices', 'kipora' ), $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php if ( $table ) : ?>
		<div class="price-list__scroll" data-reveal="fade-up">
			<table class="price-list__table">
				<thead>
					<tr>
						<th scope="col"><span class="kp-visually-hidden"><?php esc_html_e( 'Service', 'kipora' ); ?></span></th>
						<?php foreach ( $sizes as $size ) : ?>
							<th scope="col"><?php echo esc_html( $label( $size ) ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $table as $row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
							<?php foreach ( $row['prices'] as $price ) : ?>
								<td><?php echo esc_html( $price ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php if ( $rows ) : ?>
		<dl class="price-list__rows" data-reveal="stagger">
			<?php foreach ( $rows as [ $name, $price ] ) : ?>
				<div class="price-list__row">
					<dt><?php echo esc_html( $name ); ?></dt>
					<dd><?php echo esc_html( $price ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
	<?php if ( $attributes['note'] || $attributes['buttonText'] ) : ?>
		<div class="price-list__foot" data-reveal="fade-up">
			<?php if ( $attributes['note'] ) : ?>
				<p class="price-list__note"><?php echo kipora_block_text( $attributes['note'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<?php if ( $attributes['buttonText'] ) : ?>
				<a class="kp-button kp-button--primary" href="<?php echo esc_url( kipora_theme_calculator_url( $preset ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['buttonText'] ) ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
