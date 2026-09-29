<?php
/**
 * Connection settings: Montonio keys, Smart-ID / Mobile-ID relying party,
 * notification email. Secrets are never printed back into the form.
 */

namespace Kipora\Admin;

use Kipora\Settings;

final class SettingsPage {

	public const SLUG = 'kipora-settings';

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 21 );
		add_action( 'admin_post_kipora_settings', [ self::class, 'save' ] );
	}

	public static function menu(): void {
		add_submenu_page( 'kipora', __( 'Settings', 'kipora' ), __( 'Settings', 'kipora' ), 'manage_options', self::SLUG, [ self::class, 'render' ] );
	}

	public static function render(): void {
		$field = static function ( string $key, string $label, string $help = '' ): void {
			$locked = Settings::from_constant( $key );
			$secret = in_array( $key, Settings::SECRETS, true );
			$value  = $secret ? '' : Settings::get( $key );
			printf( '<tr><th scope="row"><label for="kp-%1$s">%2$s</label></th><td>', esc_attr( $key ), esc_html( $label ) );
			printf(
				'<input type="%1$s" id="kp-%2$s" name="settings[%2$s]" value="%3$s" class="regular-text" %4$s autocomplete="off" placeholder="%5$s">',
				$secret ? 'password' : 'text',
				esc_attr( $key ),
				esc_attr( $value ),
				$locked ? 'disabled' : '',
				$secret && '' !== Settings::get( $key ) ? esc_attr__( 'saved; leave empty to keep', 'kipora' ) : ''
			);
			if ( $locked ) {
				echo '<p class="description">' . esc_html__( 'Set in wp-config.php.', 'kipora' ) . '</p>';
			} elseif ( $help ) {
				echo '<p class="description">' . esc_html( $help ) . '</p>';
			}
			echo '</td></tr>';
		};
		$env = static function ( string $key, array $options ): void {
			echo '<select name="settings[' . esc_attr( $key ) . ']">';
			foreach ( $options as $value => $label ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( Settings::get( $key ), $value, false ), esc_html( $label ) );
			}
			echo '</select>';
		};
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'KIPORA settings', 'kipora' ); ?></h1>
			<?php if ( ! empty( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'kipora' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kipora_settings">
				<?php wp_nonce_field( 'kipora_settings' ); ?>

				<h2><?php esc_html_e( 'General', 'kipora' ); ?></h2>
				<table class="form-table">
					<?php $field( 'service_name', __( 'Service name', 'kipora' ), __( 'Shown on the phone during login and in emails.', 'kipora' ) ); ?>
					<?php $field( 'notify_email', __( 'Email for new orders', 'kipora' ) ); ?>
				</table>

				<h2>Montonio</h2>
				<table class="form-table">
					<tr><th scope="row"><?php esc_html_e( 'Environment', 'kipora' ); ?></th><td><?php $env( 'montonio_env', [ 'sandbox' => 'Sandbox', 'live' => 'Live' ] ); ?></td></tr>
					<?php $field( 'montonio_access_key', 'Access key' ); ?>
					<?php $field( 'montonio_secret_key', 'Secret key' ); ?>
				</table>

				<h2>Smart-ID / Mobiil-ID</h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Environment', 'kipora' ); ?></th>
						<td>
							<?php $env( 'sk_env', [ 'demo' => 'DEMO', 'live' => 'Live' ] ); ?>
							<p class="description"><?php esc_html_e( 'DEMO uses public SK test accounts. Live needs the agreement with SK ID Solutions and the values below.', 'kipora' ); ?></p>
						</td>
					</tr>
					<?php $field( 'sid_rp_uuid', 'Smart-ID relyingPartyUUID' ); ?>
					<?php $field( 'sid_rp_name', 'Smart-ID relyingPartyName' ); ?>
					<?php $field( 'mid_rp_uuid', 'Mobiil-ID relyingPartyUUID' ); ?>
					<?php $field( 'mid_rp_name', 'Mobiil-ID relyingPartyName' ); ?>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function save(): void {
		check_admin_referer( 'kipora_settings' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '', 403 );
		}
		Settings::save( (array) wp_unslash( $_POST['settings'] ?? [] ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&saved=1' ) );
		exit;
	}
}
