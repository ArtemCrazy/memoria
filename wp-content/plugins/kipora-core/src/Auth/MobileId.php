<?php
/**
 * Mobile-ID REST authentication. We send sha256(R) and verify the returned
 * signature against R, which works for both RSA and EC keys.
 */

namespace Kipora\Auth;

final class MobileId {

	private const LANGUAGE = [ 'et' => 'EST', 'ru' => 'RUS', 'en' => 'ENG' ];

	/**
	 * @param array{base_url:string, rp_uuid:string, rp_name:string, pins?:string[], trusted?:string[]} $config
	 */
	public function __construct( private array $config ) {}

	/** "5123 4567" → "+37251234567". Returns '' when not an Estonian mobile number. */
	public static function normalize_phone( string $phone ): string {
		$digits = preg_replace( '/[^\d+]/', '', $phone );
		if ( str_starts_with( $digits, '00' ) ) {
			$digits = '+' . substr( $digits, 2 );
		}
		if ( ! str_starts_with( $digits, '+' ) ) {
			$digits = '+372' . $digits;
		}
		return preg_match( '/^\+372\d{7,8}$/', $digits ) ? $digits : '';
	}

	/** @return array{session:string, code:string, random:string, idcode:string} */
	public function start( string $phone, string $idcode, string $lang, string $service_name ): array {
		$random = random_bytes( 32 );
		$hash   = hash( 'sha256', $random, true );

		$response = $this->client()->post(
			'authentication',
			[
				'relyingPartyUUID'       => $this->config['rp_uuid'],
				'relyingPartyName'       => $this->config['rp_name'],
				'phoneNumber'            => $phone,
				'nationalIdentityNumber' => $idcode,
				'hash'                   => base64_encode( $hash ),
				'hashType'               => 'SHA256',
				'language'               => self::LANGUAGE[ $lang ] ?? 'EST',
				// GSM-7 has no Cyrillic, so the brand name only.
				'displayText'            => mb_substr( $service_name, 0, 40 ),
				'displayTextFormat'      => 'GSM-7',
			]
		);

		if ( 200 !== $response['status'] || empty( $response['body']['sessionID'] ) ) {
			throw new AuthException( 400 === $response['status'] ? 'input' : 'service_unavailable', 'Mobile-ID start HTTP ' . $response['status'] . ' ' . json_encode( $response['body'] ) );
		}

		return [
			'session' => (string) $response['body']['sessionID'],
			'code'    => VerificationCode::mobile_id( $hash ),
			'random'  => base64_encode( $random ),
			'idcode'  => $idcode,
		];
	}

	public function poll( array $state, int $wait_ms = 1500 ): ?array {
		$response = $this->client()->get( 'authentication/session/' . rawurlencode( $state['session'] ) . '?timeoutMs=' . max( 1000, $wait_ms ), (int) ceil( $wait_ms / 1000 ) + 10 );
		if ( 200 !== $response['status'] ) {
			throw new AuthException( 404 === $response['status'] ? 'expired' : 'service_unavailable', 'Mobile-ID session HTTP ' . $response['status'] );
		}

		$body = $response['body'];
		if ( 'RUNNING' === ( $body['state'] ?? '' ) ) {
			return null;
		}

		$result = (string) ( $body['result'] ?? '' );
		if ( 'OK' !== $result ) {
			throw new AuthException( self::reason( $result ), 'Mobile-ID result ' . $result );
		}

		$pem      = Certificate::pem_from_der_b64( (string) ( $body['cert'] ?? '' ) );
		$identity = Certificate::verify( $pem, $this->config['trusted'] ?? [] );
		if ( $identity['code'] !== $state['idcode'] ) {
			throw new AuthException( 'signature', 'Certificate belongs to another person' );
		}

		$algorithm = (string) ( $body['signature']['algorithm'] ?? '' );
		$signature = base64_decode( (string) ( $body['signature']['value'] ?? '' ) );
		if ( str_contains( $algorithm, 'EC' ) ) {
			$signature = self::ec_raw_to_der( $signature );
		}
		if ( 1 !== openssl_verify( base64_decode( $state['random'] ), $signature, $pem, OPENSSL_ALGO_SHA256 ) ) {
			throw new AuthException( 'signature', 'Mobile-ID signature invalid (' . $algorithm . ')' );
		}
		return $identity;
	}

	/** EC signatures arrive as r‖s; OpenSSL wants an ASN.1 SEQUENCE of two INTEGERs. */
	public static function ec_raw_to_der( string $raw ): string {
		$half = intdiv( strlen( $raw ), 2 );
		$int  = static function ( string $bytes ): string {
			$bytes = ltrim( $bytes, "\x00" );
			if ( '' === $bytes || ord( $bytes[0] ) > 0x7F ) {
				$bytes = "\x00" . $bytes;
			}
			return "\x02" . self::der_length( strlen( $bytes ) ) . $bytes;
		};
		$seq = $int( substr( $raw, 0, $half ) ) . $int( substr( $raw, $half ) );
		return "\x30" . self::der_length( strlen( $seq ) ) . $seq;
	}

	private static function der_length( int $len ): string {
		if ( $len < 0x80 ) {
			return chr( $len );
		}
		$bytes = ltrim( pack( 'N', $len ), "\x00" );
		return chr( 0x80 | strlen( $bytes ) ) . $bytes;
	}

	private static function reason( string $result ): string {
		return match ( $result ) {
			'USER_CANCELLED' => 'refused',
			'TIMEOUT' => 'timeout',
			'NOT_MID_CLIENT' => 'not_found',
			'PHONE_ABSENT', 'DELIVERY_ERROR', 'SIM_ERROR' => 'phone',
			default => 'service_unavailable',
		};
	}

	private function client(): SkClient {
		return new SkClient( $this->config['base_url'], $this->config['pins'] ?? [] );
	}
}
