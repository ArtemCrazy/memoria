<?php
/**
 * Authentication certificate checks shared by Smart-ID and Mobile-ID:
 * validity period, issuer from our local trust list, identity from subject.
 */

namespace Kipora\Auth;

final class Certificate {

	public static function pem_from_der_b64( string $der_b64 ): string {
		return "-----BEGIN CERTIFICATE-----\n" . chunk_split( preg_replace( '/\s+/', '', $der_b64 ), 64, "\n" ) . "-----END CERTIFICATE-----\n";
	}

	/**
	 * @param string   $pem      User certificate.
	 * @param string[] $trusted  PEM certificates of issuing CAs we accept.
	 *
	 * @return array{country:string, code:string, first_name:string, last_name:string}
	 */
	public static function verify( string $pem, array $trusted ): array {
		$cert = openssl_x509_read( $pem );
		if ( ! $cert ) {
			throw new AuthException( 'certificate', 'Unreadable certificate' );
		}
		$info = openssl_x509_parse( $cert );
		$now  = time();
		if ( $now < (int) $info['validFrom_time_t'] || $now > (int) $info['validTo_time_t'] ) {
			throw new AuthException( 'certificate', 'Certificate expired or not yet valid' );
		}

		$issued_by_trusted = false;
		foreach ( $trusted as $ca_pem ) {
			if ( 1 === openssl_x509_verify( $cert, $ca_pem ) ) {
				$issued_by_trusted = true;
				break;
			}
		}
		if ( ! $issued_by_trusted ) {
			throw new AuthException( 'certificate', 'Issuer not trusted: ' . ( $info['issuer']['CN'] ?? '?' ) );
		}

		return self::identity( $info['subject'] ?? [] );
	}

	/**
	 * serialNumber is "PNOEE-39901012239" in current certificates and a bare
	 * code in older ESTEID ones.
	 */
	public static function identity( array $subject ): array {
		$serial = (string) ( $subject['serialNumber'] ?? '' );
		if ( preg_match( '/^PNO([A-Z]{2})-(\d{11})$/', $serial, $m ) ) {
			[ , $country, $code ] = $m;
		} elseif ( preg_match( '/^\d{11}$/', $serial ) ) {
			$country = (string) ( $subject['C'] ?? 'EE' );
			$code    = $serial;
		} else {
			throw new AuthException( 'certificate', 'No personal code in certificate' );
		}

		$first = $subject['GN'] ?? $subject['givenName'] ?? '';
		$last  = $subject['SN'] ?? $subject['surname'] ?? '';
		return [
			'country'    => strtoupper( $country ),
			'code'       => $code,
			'first_name' => self::title_case( is_array( $first ) ? implode( ' ', $first ) : (string) $first ),
			'last_name'  => self::title_case( is_array( $last ) ? implode( ' ', $last ) : (string) $last ),
		];
	}

	/** Certificates carry names in capitals: "MARY ÄNN" → "Mary Änn". */
	private static function title_case( string $name ): string {
		return mb_convert_case( mb_strtolower( $name, 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' );
	}

	/** @return string[] PEM certificates from a directory. */
	public static function load_dir( string $dir ): array {
		$out = [];
		foreach ( glob( rtrim( $dir, '/' ) . '/*.{pem,crt,cer}', GLOB_BRACE ) ?: [] as $file ) {
			$data = (string) file_get_contents( $file );
			if ( ! str_contains( $data, 'BEGIN CERTIFICATE' ) ) {
				$data = self::pem_from_der_b64( base64_encode( $data ) );
			}
			$out[] = $data;
		}
		return $out;
	}
}
