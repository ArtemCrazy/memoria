<?php
/**
 * Four-digit control codes shown on the site and on the phone.
 * If they match, the person knows the request came from us.
 */

namespace Kipora\Auth;

final class VerificationCode {

	/** Smart-ID: last two bytes of SHA-256 over the signed hash, mod 10000. */
	public static function smart_id( string $raw_hash ): string {
		$digest = hash( 'sha256', $raw_hash, true );
		$value  = unpack( 'n', substr( $digest, -2 ) )[1] % 10000;
		return str_pad( (string) $value, 4, '0', STR_PAD_LEFT );
	}

	/** Mobile-ID: 6 high bits of the first byte and 7 low bits of the last byte. */
	public static function mobile_id( string $raw_hash ): string {
		$first = ord( $raw_hash[0] );
		$last  = ord( $raw_hash[ strlen( $raw_hash ) - 1 ] );
		$value = ( ( $first & 0xFC ) << 5 ) | ( $last & 0x7F );
		return str_pad( (string) $value, 4, '0', STR_PAD_LEFT );
	}
}
