<?php
/**
 * Estonian personal identification code (isikukood).
 * Pure logic, no WordPress.
 */

namespace Kipora;

final class IdCode {

	/** Format and checksum validation of an 11-digit Estonian code. */
	public static function is_valid( string $code ): bool {
		if ( ! preg_match( '/^[1-6]\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{4}$/', $code ) ) {
			return false;
		}
		return self::checksum( $code ) === (int) $code[10];
	}

	public static function checksum( string $code ): int {
		$weights_1 = [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 1 ];
		$weights_2 = [ 3, 4, 5, 6, 7, 8, 9, 1, 2, 3 ];

		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$sum += (int) $code[ $i ] * $weights_1[ $i ];
		}
		$rest = $sum % 11;
		if ( 10 !== $rest ) {
			return $rest;
		}

		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$sum += (int) $code[ $i ] * $weights_2[ $i ];
		}
		$rest = $sum % 11;
		return 10 === $rest ? 0 : $rest;
	}

	/**
	 * Stable lookup key for a person. The code itself is never stored:
	 * the account is found by an HMAC, so a database leak does not expose it.
	 */
	public static function hash( string $country, string $code, string $secret ): string {
		return hash_hmac( 'sha256', strtoupper( $country ) . ':' . $code, $secret );
	}

	/** 3920101xxxx — enough for a person to recognise their own profile. */
	public static function mask( string $code ): string {
		return substr( $code, 0, 7 ) . 'xxxx';
	}
}
