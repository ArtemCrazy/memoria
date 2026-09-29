<?php
/**
 * Minimal HS256 JWT — the only algorithm Montonio uses.
 * Pure logic, no WordPress.
 */

namespace Kipora;

final class Jwt {

	public static function encode( array $payload, string $secret ): string {
		$header = [ 'alg' => 'HS256', 'typ' => 'JWT' ];
		$parts  = [
			self::b64( (string) json_encode( $header ) ),
			self::b64( (string) json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
		];
		$parts[] = self::b64( hash_hmac( 'sha256', implode( '.', $parts ), $secret, true ) );
		return implode( '.', $parts );
	}

	/**
	 * Verifies signature and expiry. Returns the payload or null.
	 *
	 * @param int $leeway Allowed clock skew in seconds.
	 */
	public static function decode( string $token, string $secret, int $leeway = 60 ): ?array {
		$parts = explode( '.', $token );
		if ( 3 !== count( $parts ) ) {
			return null;
		}
		[ $h, $p, $s ] = $parts;

		$header = json_decode( self::unb64( $h ), true );
		if ( ! is_array( $header ) || ( $header['alg'] ?? '' ) !== 'HS256' ) {
			return null;
		}

		$expected = self::b64( hash_hmac( 'sha256', $h . '.' . $p, $secret, true ) );
		if ( ! hash_equals( $expected, $s ) ) {
			return null;
		}

		$payload = json_decode( self::unb64( $p ), true );
		if ( ! is_array( $payload ) ) {
			return null;
		}
		if ( isset( $payload['exp'] ) && time() - $leeway > (int) $payload['exp'] ) {
			return null;
		}
		return $payload;
	}

	private static function b64( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private static function unb64( string $data ): string {
		$pad = strlen( $data ) % 4;
		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}
		return (string) base64_decode( strtr( $data, '-_', '+/' ), true );
	}
}
