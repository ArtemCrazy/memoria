<?php
/**
 * Browser side of the login: start a Smart-ID / Mobile-ID session, poll it,
 * log the person in. Session state stays on the server (transient), the
 * browser only holds a random key and a binding cookie.
 */

namespace Kipora\Auth;

use Kipora\Clients;
use Kipora\IdCode;
use Kipora\Lang;
use Kipora\Settings;

final class Flow {

	private const TTL         = 300;
	private const BIND_COOKIE = 'kp_auth_bind';

	/** @return array{key:string, code:string} */
	public static function start( string $method, string $idcode, string $phone, string $lang ): array {
		$idcode = preg_replace( '/\D/', '', $idcode );
		if ( ! IdCode::is_valid( $idcode ) ) {
			throw new AuthException( 'idcode' );
		}
		self::throttle( 'ip:' . self::ip(), 10 );
		self::throttle( 'id:' . $idcode, 5 );

		$service = Settings::get( 'service_name' );
		if ( 'mobileid' === $method ) {
			$phone = MobileId::normalize_phone( $phone );
			if ( '' === $phone ) {
				throw new AuthException( 'phone_format' );
			}
			$state = ( new MobileId( Settings::mobile_id() ) )->start( $phone, $idcode, $lang, $service );
		} else {
			$method = 'smartid';
			$state  = ( new SmartId( Settings::smart_id() ) )->start( $idcode, $lang, $service );
		}

		$bind = bin2hex( random_bytes( 16 ) );
		$key  = bin2hex( random_bytes( 16 ) );
		set_transient(
			'kp_auth_' . $key,
			[
				'method'  => $method,
				'state'   => $state,
				'phone'   => $phone,
				'lang'    => $lang,
				'bind'    => hash( 'sha256', $bind ),
				'started' => time(),
			],
			self::TTL
		);
		setcookie( self::BIND_COOKIE, $bind, [ 'expires' => time() + self::TTL, 'path' => COOKIEPATH ?: '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ] );

		return [ 'key' => $key, 'code' => $state['code'] ];
	}

	/**
	 * @return array{state:string, reason?:string}
	 */
	public static function status( string $key ): array {
		$key  = preg_replace( '/[^a-f0-9]/', '', $key );
		$data = get_transient( 'kp_auth_' . $key );
		if ( ! is_array( $data ) ) {
			throw new AuthException( 'expired' );
		}
		$bind = (string) ( $_COOKIE[ self::BIND_COOKIE ] ?? '' );
		if ( ! hash_equals( $data['bind'], hash( 'sha256', $bind ) ) ) {
			throw new AuthException( 'expired', 'Binding cookie mismatch' );
		}

		try {
			$identity = 'mobileid' === $data['method']
				? ( new MobileId( Settings::mobile_id() ) )->poll( $data['state'], 1500 )
				: ( new SmartId( Settings::smart_id() ) )->poll( $data['state'], 1500 );
		} catch ( AuthException $e ) {
			delete_transient( 'kp_auth_' . $key );
			throw $e;
		}

		if ( null === $identity ) {
			return [ 'state' => 'pending' ];
		}

		delete_transient( 'kp_auth_' . $key );
		setcookie( self::BIND_COOKIE, '', [ 'expires' => time() - 3600, 'path' => COOKIEPATH ?: '/' ] );
		Clients::login( $identity, $data['method'], Lang::normalize( $data['lang'] ), $data['phone'] );
		return [ 'state' => 'ok' ];
	}

	/** Messages a person sees. Technical details only go to the log. */
	public static function message( string $reason ): string {
		return match ( $reason ) {
			'idcode' => __( 'Check the personal identification code: it has 11 digits.', 'kipora' ),
			'phone_format' => __( 'Enter an Estonian mobile number, for example 5123 4567.', 'kipora' ),
			'not_found' => __( 'No active account found for this code. Check the code or choose another login method.', 'kipora' ),
			'refused' => __( 'Login was cancelled on the phone.', 'kipora' ),
			'timeout' => __( 'The phone did not answer in time. Start again.', 'kipora' ),
			'wrong_code' => __( 'A different control code was chosen on the phone. Start again and compare the codes.', 'kipora' ),
			'unusable' => __( 'Smart-ID cannot be used on this device. Open the Smart-ID app to check.', 'kipora' ),
			'phone' => __( 'The phone could not be reached. Check that it is on and try again.', 'kipora' ),
			'throttled' => __( 'Too many attempts. Wait a few minutes and try again.', 'kipora' ),
			'expired' => __( 'The login session expired. Start again.', 'kipora' ),
			'input' => __( 'Check the phone number and personal code: they must belong to the same Mobile-ID.', 'kipora' ),
			default => __( 'Login is temporarily unavailable. Try again in a few minutes.', 'kipora' ),
		};
	}

	private static function throttle( string $bucket, int $limit ): void {
		$key   = 'kp_thr_' . md5( $bucket );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			throw new AuthException( 'throttled' );
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
	}

	private static function ip(): string {
		return (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
	}
}
