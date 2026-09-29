<?php
/**
 * JSON over HTTPS for SK ID Solutions services.
 * Plain cURL (not the WP HTTP API) so we can pin the server public key.
 */

namespace Kipora\Auth;

final class SkClient {

	/**
	 * @param string   $base   Service base URL without trailing slash.
	 * @param string[] $pins   Optional "sha256//BASE64" public key pins.
	 */
	public function __construct( private string $base, private array $pins = [] ) {
		$this->base = rtrim( $base, '/' );
	}

	/** @return array{status:int, body:array} */
	public function post( string $path, array $body, int $timeout = 15 ): array {
		return $this->request( 'POST', $path, $body, $timeout );
	}

	/** @return array{status:int, body:array} */
	public function get( string $path, int $timeout = 15 ): array {
		return $this->request( 'GET', $path, null, $timeout );
	}

	private function request( string $method, string $path, ?array $body, int $timeout ): array {
		$ch = curl_init( $this->base . '/' . ltrim( $path, '/' ) );
		curl_setopt_array(
			$ch,
			[
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => $method,
				CURLOPT_TIMEOUT        => $timeout,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json', 'Accept: application/json' ],
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
			]
		);
		if ( $this->pins ) {
			curl_setopt( $ch, CURLOPT_PINNEDPUBLICKEY, implode( ';', $this->pins ) );
		}
		if ( null !== $body ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		}

		$raw    = curl_exec( $ch );
		$status = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		$error  = curl_error( $ch );
		curl_close( $ch );

		if ( false === $raw ) {
			throw new AuthException( 'service_unavailable', 'SK request failed: ' . $error );
		}
		$decoded = json_decode( (string) $raw, true );
		return [ 'status' => $status, 'body' => is_array( $decoded ) ? $decoded : [] ];
	}
}
