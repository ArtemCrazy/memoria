<?php
/**
 * Order lines and total.
 *
 * @var array $lines  [{label, amount}]
 * @var int   $total  cents
 */
defined( 'ABSPATH' ) || exit;
use Kipora\Pricing;
?>
<dl class="kp-summary">
	<?php foreach ( $lines as $line ) : ?>
		<div class="kp-summary__row">
			<dt class="kp-summary__label"><?php echo esc_html( $line['label'] ); ?></dt>
			<dd class="kp-summary__amount"><?php echo esc_html( Pricing::format( (int) $line['amount'] ) ); ?></dd>
		</div>
	<?php endforeach; ?>
	<div class="kp-summary__row kp-summary__row--total">
		<dt class="kp-summary__label"><?php esc_html_e( 'Total', 'kipora' ); ?></dt>
		<dd class="kp-summary__amount"><?php echo esc_html( Pricing::format( (int) $total ) ); ?></dd>
	</div>
</dl>
