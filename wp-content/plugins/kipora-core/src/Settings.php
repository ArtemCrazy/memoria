<?php
/**
 * Plugin settings. Secrets may be defined as constants in wp-config.php
 * (KIPORA_MONTONIO_SECRET_KEY etc.) — a constant always wins over the
 * value saved in the admin.
 */

namespace Kipora;

final class Settings {

	public const OPTION = 'kipora_settings';

	public const DEFAULTS = [
		'service_name'        => 'KIPORA',
		'notify_email'        => '',
		'montonio_env'        => 'sandbox',
		'montonio_access_key' => '',
		'montonio_secret_key' => '',
		'sk_env'              => 'demo',
		'sid_rp_uuid'         => '',
		'sid_rp_name'         => '',
		'mid_rp_uuid'         => '',
		'mid_rp_name'         => '',
	];

	public const SECRETS = [ 'montonio_secret_key' ];

	public static function get( string $key ): string {
		$constant = 'KIPORA_' . strtoupper( $key );
		if ( defined( $constant ) ) {
			return (string) constant( $constant );
		}
		$saved = (array) get_option( self::OPTION, [] );
		$value = (string) ( $saved[ $key ] ?? self::DEFAULTS[ $key ] ?? '' );
		if ( 'notify_email' === $key && '' === $value ) {
			$value = (string) get_option( 'admin_email' );
		}
		return $value;
	}

	public static function from_constant( string $key ): bool {
		return defined( 'KIPORA_' . strtoupper( $key ) );
	}

	public static function save( array $input ): void {
		$saved = (array) get_option( self::OPTION, [] );
		foreach ( self::DEFAULTS as $key => $default ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$value = trim( sanitize_text_field( (string) $input[ $key ] ) );
			// Empty secret field means "keep the current one": we never print secrets back.
			if ( in_array( $key, self::SECRETS, true ) && '' === $value ) {
				continue;
			}
			$saved[ $key ] = $value;
		}
		$saved['montonio_env'] = 'live' === ( $saved['montonio_env'] ?? '' ) ? 'live' : 'sandbox';
		$saved['sk_env']       = 'live' === ( $saved['sk_env'] ?? '' ) ? 'live' : 'demo';
		update_option( self::OPTION, $saved, false );
	}

	/** Connection settings for Smart-ID. DEMO values are public test credentials. */
	public static function smart_id(): array {
		$live = 'live' === self::get( 'sk_env' );
		return [
			'base_url' => $live ? 'https://rp-api.smart-id.com/v3' : 'https://sid.demo.sk.ee/smart-id-rp/v3',
			'rp_uuid'  => $live ? self::get( 'sid_rp_uuid' ) : '00000000-0000-4000-8000-000000000000',
			'rp_name'  => $live ? self::get( 'sid_rp_name' ) : 'DEMO',
			'trusted'  => Auth\Certificate::load_dir( KIPORA_DIR . 'certs/' . ( $live ? 'live' : 'demo' ) ),
		];
	}

	public static function mobile_id(): array {
		$live = 'live' === self::get( 'sk_env' );
		return [
			'base_url' => $live ? 'https://mid.sk.ee/mid-api' : 'https://tsp.demo.sk.ee/mid-api',
			'rp_uuid'  => $live ? self::get( 'mid_rp_uuid' ) : '00000000-0000-0000-0000-000000000000',
			'rp_name'  => $live ? self::get( 'mid_rp_name' ) : 'DEMO',
			'trusted'  => Auth\Certificate::load_dir( KIPORA_DIR . 'certs/' . ( $live ? 'live' : 'demo' ) ),
		];
	}

	public static function is_demo_auth(): bool {
		return 'live' !== self::get( 'sk_env' );
	}

	/** Secret for hashing personal codes. Generated once, never shown. */
	public static function id_secret(): string {
		if ( defined( 'KIPORA_ID_SECRET' ) ) {
			return (string) KIPORA_ID_SECRET;
		}
		$secret = (string) get_option( 'kipora_id_secret' );
		if ( '' === $secret ) {
			$secret = bin2hex( random_bytes( 32 ) );
			add_option( 'kipora_id_secret', $secret, '', false );
		}
		return $secret;
	}
}
