<?php
/**
 * Smart-ID RP API v3, notification-based authentication by personal code.
 * Protocol: ACSP_V2, RSASSA-PSS / SHA-512. See docs/integrations.md.
 */

namespace Kipora\Auth;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

final class SmartId {

	private const TEXT = [
		'et' => 'Sisselogimine: %s',
		'ru' => 'Вход: %s',
		'en' => 'Log in to %s',
	];

	/**
	 * @param array{base_url:string, rp_uuid:string, rp_name:string, pins?:string[], trusted?:string[]} $config
	 */
	public function __construct( private array $config ) {}

	/**
	 * Starts a session. The returned state is stored server side only.
	 *
	 * @return array{session:string, code:string, challenge:string, interactions:string, idcode:string}
	 */
	public function start( string $idcode, string $lang, string $service_name ): array {
		$challenge    = random_bytes( 64 );
		$text         = mb_substr( sprintf( self::TEXT[ $lang ] ?? self::TEXT['et'], $service_name ), 0, 60 );
		$interactions = base64_encode( (string) json_encode( [ [ 'type' => 'displayTextAndPIN', 'displayText60' => $text ] ], JSON_UNESCAPED_UNICODE ) );

		$response = $this->client()->post(
			'authentication/notification/etsi/PNOEE-' . $idcode,
			[
				'relyingPartyUUID'            => $this->config['rp_uuid'],
				'relyingPartyName'            => $this->config['rp_name'],
				'certificateLevel'            => 'QUALIFIED',
				'signatureProtocol'           => 'ACSP_V2',
				'signatureProtocolParameters' => [
					'rpChallenge'                  => base64_encode( $challenge ),
					'signatureAlgorithm'           => 'rsassa-pss',
					'signatureAlgorithmParameters' => [ 'hashAlgorithm' => 'SHA-512' ],
				],
				'interactions'                => $interactions,
				'vcType'                      => 'numeric4',
			]
		);

		if ( 404 === $response['status'] ) {
			throw new AuthException( 'not_found', 'Smart-ID account not found' );
		}
		if ( 200 !== $response['status'] || empty( $response['body']['sessionID'] ) ) {
			throw new AuthException( 'service_unavailable', 'Smart-ID start HTTP ' . $response['status'] . ' ' . json_encode( $response['body'] ) );
		}

		return [
			'session'      => (string) $response['body']['sessionID'],
			'code'         => VerificationCode::smart_id( $challenge ),
			'challenge'    => base64_encode( $challenge ),
			'interactions' => $interactions,
			'idcode'       => $idcode,
		];
	}

	/**
	 * @return array|null Identity when complete, null while the person has not answered yet.
	 */
	public function poll( array $state, int $wait_ms = 1500 ): ?array {
		$response = $this->client()->get( 'session/' . rawurlencode( $state['session'] ) . '?timeoutMs=' . max( 1000, $wait_ms ), (int) ceil( $wait_ms / 1000 ) + 10 );
		if ( 200 !== $response['status'] ) {
			throw new AuthException( 404 === $response['status'] ? 'expired' : 'service_unavailable', 'Smart-ID session HTTP ' . $response['status'] );
		}

		$body = $response['body'];
		if ( 'RUNNING' === ( $body['state'] ?? '' ) ) {
			return null;
		}

		$end = (string) ( $body['result']['endResult'] ?? '' );
		if ( 'OK' !== $end ) {
			throw new AuthException( self::reason( $end ), 'Smart-ID endResult ' . $end );
		}
		if ( 'ACSP_V2' !== ( $body['signatureProtocol'] ?? '' ) ) {
			throw new AuthException( 'signature', 'Unexpected signature protocol' );
		}

		$pem      = Certificate::pem_from_der_b64( (string) ( $body['cert']['value'] ?? '' ) );
		$identity = Certificate::verify( $pem, $this->config['trusted'] ?? [] );
		if ( 'EE' !== $identity['country'] || $identity['code'] !== $state['idcode'] ) {
			throw new AuthException( 'signature', 'Certificate belongs to another person' );
		}

		$this->verify_signature( $body, $state, $pem );
		return $identity;
	}

	private function verify_signature( array $body, array $state, string $pem ): void {
		$sig     = $body['signature'] ?? [];
		$payload = implode(
			'|',
			[
				// The DEMO environment signs with its own scheme name.
				$this->config['scheme'] ?? ( str_contains( $this->config['base_url'], '.demo.' ) ? 'smart-id-demo' : 'smart-id' ),
				'ACSP_V2',
				(string) ( $sig['serverRandom'] ?? '' ),
				$state['challenge'],
				(string) ( $sig['userChallenge'] ?? '' ),
				base64_encode( $this->config['rp_name'] ),
				'', // brokeredRpName: not a broker.
				base64_encode( hash( 'sha256', $state['interactions'], true ) ),
				(string) ( $body['interactionTypeUsed'] ?? '' ),
				'', // initialCallbackUrl: notification flow has none.
				(string) ( $sig['flowType'] ?? '' ),
			]
		);

		$params = $sig['signatureAlgorithmParameters'] ?? [];
		$hash   = strtolower( str_replace( '-', '', (string) ( $params['hashAlgorithm'] ?? 'SHA-512' ) ) );
		$salt   = (int) ( $params['saltLength'] ?? 64 );

		try {
			// Load the bare SubjectPublicKeyInfo: phpseclib misreads the key when given the whole certificate.
			$details = openssl_pkey_get_details( openssl_pkey_get_public( $pem ) );
			$key     = PublicKeyLoader::load( (string) ( $details['key'] ?? '' ) );
			if ( ! $key instanceof RSA\PublicKey ) {
				throw new AuthException( 'signature', 'Smart-ID certificate key is not RSA' );
			}
			$ok = $key->withPadding( RSA::SIGNATURE_PSS )
				->withHash( $hash )
				->withMGFHash( $hash )
				->withSaltLength( $salt )
				->verify( $payload, base64_decode( (string) ( $sig['value'] ?? '' ) ) );
		} catch ( \Throwable $e ) {
			throw new AuthException( 'signature', 'Smart-ID signature check error: ' . $e->getMessage() );
		}
		if ( ! $ok ) {
			throw new AuthException( 'signature', 'Smart-ID signature invalid' );
		}
	}

	private static function reason( string $end ): string {
		return match ( $end ) {
			'USER_REFUSED', 'USER_REFUSED_INTERACTION', 'USER_REFUSED_CERT_CHOICE' => 'refused',
			'TIMEOUT' => 'timeout',
			'WRONG_VC' => 'wrong_code',
			'DOCUMENT_UNUSABLE', 'ACCOUNT_UNUSABLE', 'REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP' => 'unusable',
			default => 'service_unavailable',
		};
	}

	private function client(): SkClient {
		return new SkClient( $this->config['base_url'], $this->config['pins'] ?? [] );
	}
}
