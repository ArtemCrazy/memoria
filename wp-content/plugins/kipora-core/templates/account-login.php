<?php
/** @var string $login */
defined( 'ABSPATH' ) || exit;
?>
<div class="kp-account kp-account--guest">
	<?php echo $login; // phpcs:ignore WordPress.Security.EscapeOutput — rendered template ?>
</div>
