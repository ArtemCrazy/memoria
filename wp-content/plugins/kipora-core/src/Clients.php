<?php
/**
 * Customer accounts. A customer is identified by the personal code from
 * Smart-ID / Mobile-ID; only its HMAC is stored.
 */

namespace Kipora;

final class Clients {

	public const ROLE = 'kp_client';

	public static function register(): void {
		add_action( 'admin_init', [ self::class, 'keep_out_of_admin' ] );
		add_filter( 'show_admin_bar', [ self::class, 'admin_bar' ] );
	}

	public static function is_client( ?\WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();
		return $user && $user->exists() && in_array( self::ROLE, (array) $user->roles, true );
	}

	public static function keep_out_of_admin(): void {
		if ( wp_doing_ajax() || ! self::is_client() ) {
			return;
		}
		// admin-post.php handles our front-end forms and must stay reachable.
		if ( str_ends_with( (string) parse_url( $_SERVER['PHP_SELF'] ?? '', PHP_URL_PATH ), 'admin-post.php' ) ) {
			return;
		}
		wp_safe_redirect( Lang::page_url( 'account' ) );
		exit;
	}

	public static function admin_bar( bool $show ): bool {
		return self::is_client() ? false : $show;
	}

	/**
	 * Finds or creates the account for a verified identity and logs in.
	 *
	 * @param array{country:string, code:string, first_name:string, last_name:string} $identity
	 */
	public static function login( array $identity, string $method, string $lang, string $phone = '' ): int {
		$hash  = IdCode::hash( $identity['country'], $identity['code'], Settings::id_secret() );
		$users = get_users(
			[
				'meta_key'   => 'kp_id_hash',
				'meta_value' => $hash,
				'number'     => 1,
				'fields'     => 'ID',
			]
		);
		$user_id = (int) ( $users[0] ?? 0 );

		$name = trim( $identity['first_name'] . ' ' . $identity['last_name'] );
		if ( ! $user_id ) {
			$user_id = wp_insert_user(
				[
					'user_login'   => 'ee' . substr( $hash, 0, 16 ),
					'user_pass'    => wp_generate_password( 32, true, true ),
					'role'         => self::ROLE,
					'first_name'   => $identity['first_name'],
					'last_name'    => $identity['last_name'],
					'display_name' => $name,
				]
			);
			if ( is_wp_error( $user_id ) ) {
				throw new \RuntimeException( 'Account creation failed: ' . $user_id->get_error_message() );
			}
			update_user_meta( $user_id, 'kp_id_hash', $hash );
			update_user_meta( $user_id, 'kp_id_masked', IdCode::mask( $identity['code'] ) );
		} else {
			// Names can change (marriage); the certificate is the source of truth.
			wp_update_user(
				[
					'ID'           => $user_id,
					'first_name'   => $identity['first_name'],
					'last_name'    => $identity['last_name'],
					'display_name' => $name,
				]
			);
		}

		if ( $phone && ! get_user_meta( $user_id, 'kp_phone', true ) ) {
			update_user_meta( $user_id, 'kp_phone', $phone );
		}
		update_user_meta( $user_id, 'kp_lang', $lang );
		update_user_meta( $user_id, 'kp_last_login', [ 'method' => $method, 'time' => time() ] );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, false, is_ssl() );
		return (int) $user_id;
	}

	public static function contact( int $user_id ): array {
		$user = get_userdata( $user_id );
		return [
			'name'  => $user ? $user->display_name : '',
			'email' => $user ? (string) $user->user_email : '',
			'phone' => (string) get_user_meta( $user_id, 'kp_phone', true ),
		];
	}

	/** @return true|\WP_Error */
	public static function save_contact( int $user_id, string $email, string $phone ) {
		$email = sanitize_email( $email );
		if ( '' !== $email && ! is_email( $email ) ) {
			return new \WP_Error( 'email', __( 'Check the email address.', 'kipora' ) );
		}
		$owner = $email ? email_exists( $email ) : false;
		if ( $owner && (int) $owner !== $user_id ) {
			return new \WP_Error( 'email', __( 'This email is already used by another account.', 'kipora' ) );
		}
		$result = wp_update_user( [ 'ID' => $user_id, 'user_email' => $email ] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		update_user_meta( $user_id, 'kp_phone', mb_substr( sanitize_text_field( $phone ), 0, 30 ) );
		return true;
	}
}
