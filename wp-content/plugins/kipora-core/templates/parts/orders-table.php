<?php
/**
 * @var array    $orders
 * @var callable $date
 */
defined( 'ABSPATH' ) || exit;

use Kipora\Files;
use Kipora\Orders;
use Kipora\Pricing;
?>
<ul class="kp-orders">
	<?php foreach ( $orders as $order ) : ?>
		<?php $reports = array_filter( Files::for_order( $order['id'] ), static fn( $f ) => Files::KIND_ARCHIVE !== $f['kind'] ); ?>
		<li class="kp-orders__item">
			<div class="kp-orders__main">
				<span class="kp-orders__ref"><?php echo esc_html( $order['reference'] ); ?></span>
				<span class="kp-orders__date"><?php echo esc_html( $date( $order['created'] ) ); ?></span>
				<span class="kp-orders__what"><?php echo esc_html( Orders::summary( $order ) ); ?></span>
			</div>
			<div class="kp-orders__side">
				<span class="kp-orders__total"><?php echo esc_html( Pricing::format( $order['total'] ) ); ?></span>
				<span class="kp-status kp-status--<?php echo esc_attr( $order['status'] ); ?>"><?php echo esc_html( Orders::status_label( $order['status'] ) ); ?></span>
			</div>
			<?php if ( $reports ) : ?>
				<div class="kp-report">
					<?php foreach ( [ Files::KIND_BEFORE => __( 'Before', 'kipora' ), Files::KIND_AFTER => __( 'After', 'kipora' ) ] as $kind => $label ) : ?>
						<?php $set = array_filter( $reports, static fn( $f ) => $f['kind'] === $kind ); ?>
						<?php if ( $set ) : ?>
							<figure class="kp-report__col">
								<figcaption class="kp-report__label"><?php echo esc_html( $label ); ?></figcaption>
								<div class="kp-report__images">
									<?php foreach ( $set as $file ) : ?>
										<a href="<?php echo esc_url( Files::url( (int) $file['id'] ) ); ?>" target="_blank" rel="noopener">
											<img src="<?php echo esc_url( Files::url( (int) $file['id'], true ) ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy">
										</a>
									<?php endforeach; ?>
								</div>
							</figure>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
