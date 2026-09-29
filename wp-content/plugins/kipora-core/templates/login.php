<?php
/**
 * Login with Smart-ID or Mobile-ID. assets/js/login.js drives it.
 *
 * @var bool   $demo  SK DEMO environment is active.
 * @var string $intro checkout | account | ''
 */
defined( 'ABSPATH' ) || exit;
$uid = wp_unique_id( 'kp-login-' );
?>
<section class="kp-login" data-kp-login aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
	<h2 class="kp-login__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php esc_html_e( 'Log in', 'kipora' ); ?></h2>
	<p class="kp-login__lead">
		<?php
		echo esc_html(
			'checkout' === $intro
				? __( 'The order is linked to your memorial cards, so we ask you to log in. No password or registration is needed.', 'kipora' )
				: __( 'Your memorial cards, orders and photo reports are here. No password or registration is needed.', 'kipora' )
		);
		?>
	</p>

	<div class="kp-login__tabs" role="tablist">
		<button type="button" class="kp-login__tab kp-login__tab--active" role="tab" aria-selected="true" data-kp-method="smartid">Smart-ID</button>
		<button type="button" class="kp-login__tab" role="tab" aria-selected="false" data-kp-method="mobileid">Mobiil-ID</button>
	</div>

	<form class="kp-login__form" novalidate>
		<div class="kp-field">
			<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-idcode"><?php esc_html_e( 'Personal identification code', 'kipora' ); ?></label>
			<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-idcode" name="idcode" inputmode="numeric" autocomplete="off" maxlength="11" pattern="\d{11}" required>
		</div>
		<div class="kp-field" data-kp-phone hidden>
			<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-phone"><?php esc_html_e( 'Mobile number', 'kipora' ); ?></label>
			<div class="kp-field__prefixed">
				<span class="kp-field__prefix">+372</span>
				<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel-national" maxlength="12">
			</div>
		</div>
		<button type="submit" class="kp-button kp-button--primary kp-login__submit"><?php esc_html_e( 'Log in', 'kipora' ); ?></button>
		<p class="kp-login__message" data-kp-message role="status" aria-live="polite"></p>
	</form>

	<div class="kp-login__pending" data-kp-pending hidden>
		<p class="kp-login__code-label"><?php esc_html_e( 'Control code', 'kipora' ); ?></p>
		<p class="kp-login__code" data-kp-code></p>
		<p class="kp-login__hint"><?php esc_html_e( 'Check your phone. Enter PIN1 if the control code matches.', 'kipora' ); ?></p>
		<button type="button" class="kp-button kp-button--quiet" data-kp-cancel><?php esc_html_e( 'Cancel', 'kipora' ); ?></button>
	</div>

	<?php if ( $demo ) : ?>
		<details class="kp-login__demo">
			<summary><?php esc_html_e( 'Test environment: SK DEMO accounts', 'kipora' ); ?></summary>
			<p><?php esc_html_e( 'Real Smart-ID and Mobile-ID do not work here yet. Use the test data, the login is confirmed automatically.', 'kipora' ); ?></p>
			<ul>
				<li>Smart-ID: <code>40504040001</code>, <code>39901012239</code></li>
				<li>Mobiil-ID: <code>60001017869</code> + <code>68000769</code></li>
			</ul>
		</details>
	<?php endif; ?>
</section>
