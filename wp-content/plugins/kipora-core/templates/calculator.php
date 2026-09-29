<?php
/**
 * Calculator mount point. assets/js/calculator.js renders the steps from
 * window.kiporaCalculator; without JavaScript the person sees a contact hint.
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="kp-calc" data-kp-calculator>
	<noscript>
		<p class="kp-calc__noscript"><?php esc_html_e( 'The price calculator needs JavaScript. Turn it on in the browser or contact us for a price.', 'kipora' ); ?></p>
	</noscript>
</div>
