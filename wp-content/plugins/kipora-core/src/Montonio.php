<?php
/**
 * Montonio Stargate payments: create an order, verify the signed tokens
 * Montonio sends back (webhook and return URL). See docs/integrations.md.
 */

namespace Kipora;

final class Montonio {

	public const METHOD_BANK = 'paymentInitiation';
	public const METHOD_CARD = 'cardPayments';

	public static function configured(): bool {
		return '' !== Settings::get( 'montonio_access_key' ) && '' !== Settings::get( 'montonio_secret_key' );
	}

	public static function base_url(): string {
		return 'live' === Settings::get( 'montonio_env' )
			? 'https://stargate.montonio.com/api'
			: 'https://sandbox-stargate.montonio.com/api';
	}

	/**
	 * Builds the signed order payload. Separate from the HTTP call so tests
	 * can check it without network.
	 */
	public static function order_payload( array $order, string $return_url, string $notify_url ): array {
		$amount  = round( $order['total'] / 100, 2 );
		$contact = $order['contact'];
		[ $first, $last ] = array_pad( explode( ' ', (string) ( $contact['name'] ?? '' ), 2 ), 2, '' );

		$payment = [
			'amount'   => $amount,
			'currency' => 'EUR',
			'method'   => self::METHOD_CARD === $order['pay_method'] ? self::METHOD_CARD : self::METHOD_BANK,
		];
		if ( self::METHOD_BANK === $payment['method'] ) {
			// No preferredProvider: the customer picks a bank on Montonio's page.
			$payment['methodOptions'] = [
				'preferredCountry'   => 'EE',
				'preferredLocale'    => Lang::montonio_locale( $order['lang'] ),
				'paymentDescription' => $order['reference'],
			];
		}

		$lines = [];
		foreach ( $order['lines'] as $line ) {
			$lines[] = [
				'name'       => mb_substr( (string) $line['label'], 0, 100 ),
				'quantity'   => 1,
				'finalPrice' => round( $line['amount'] / 100, 2 ),
			];
		}

		return [
			'accessKey'         => Settings::get( 'montonio_access_key' ),
			'merchantReference' => $order['reference'],
			'returnUrl'         => $return_url,
			'notificationUrl'   => $notify_url,
			'currency'          => 'EUR',
			'grandTotal'        => $amount,
			'locale'            => Lang::montonio_locale( $order['lang'] ),
			'billingAddress'    => array_filter(
				[
					'firstName'   => $first,
					'lastName'    => $last,
					'email'       => (string) ( $contact['email'] ?? '' ),
					'phoneNumber' => (string) ( $contact['phone'] ?? '' ),
					'country'     => 'EE',
				]
			),
			'lineItems'         => $lines,
			'payment'           => $payment,
			'expiresIn'         => 60,
			'exp'               => time() + 600,
		];
	}

	/**
	 * @return string|\WP_Error Payment URL to redirect the customer to.
	 */
	public static function create_payment( array $order ) {
		if ( ! self::configured() ) {
			return new \WP_Error( 'montonio', __( 'Online payment is not connected yet.', 'kipora' ) );
		}

		$payload = self::order_payload( $order, self::return_url( $order ), rest_url( 'kipora/v1/montonio/notify' ) );
		$token   = Jwt::encode( $payload, Settings::get( 'montonio_secret_key' ) );

		$response = wp_remote_post(
			self::base_url() . '/orders',
			[
				'timeout' => 20,
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( [ 'data' => $token ] ),
			]
		);
		if ( is_wp_error( $response ) ) {
			self::log( 'create failed: ' . $response->get_error_message() );
			return new \WP_Error( 'montonio', __( 'The payment service did not respond. Try again in a minute.', 'kipora' ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code >= 300 || empty( $body['paymentUrl'] ) || empty( $body['uuid'] ) ) {
			self::log( 'create HTTP ' . $code . ' ' . wp_remote_retrieve_body( $response ) );
			return new \WP_Error( 'montonio', __( 'The payment service did not respond. Try again in a minute.', 'kipora' ) );
		}

		update_post_meta( $order['id'], 'kp_montonio_uuid', sanitize_text_field( $body['uuid'] ) );
		return (string) $body['paymentUrl'];
	}

	public static function return_url( array $order ): string {
		return add_query_arg( [ 'kp_order' => $order['id'] ], Lang::page_url( 'checkout', $order['lang'] ) );
	}

	/**
	 * Verifies a token from Montonio and applies it to our order.
	 *
	 * @return array|null The order after update, null when the token is not ours.
	 */
	public static function apply_token( string $token ): ?array {
		$payload = Jwt::decode( $token, Settings::get( 'montonio_secret_key' ) );
		if ( ! $payload || ( $payload['accessKey'] ?? '' ) !== Settings::get( 'montonio_access_key' ) ) {
			self::log( 'rejected token' );
			return null;
		}

		$order = Orders::get( Orders::id_from_reference( (string) ( $payload['merchantReference'] ?? '' ) ) );
		if ( ! $order ) {
			self::log( 'unknown reference ' . ( $payload['merchantReference'] ?? '' ) );
			return null;
		}
		if ( $order['montonio'] && ( $payload['uuid'] ?? '' ) !== $order['montonio'] ) {
			self::log( 'uuid mismatch for ' . $order['reference'] );
			return null;
		}

		$status = (string) ( $payload['paymentStatus'] ?? '' );
		$paid   = abs( (float) ( $payload['grandTotal'] ?? 0 ) * 100 - $order['total'] ) < 1;

		switch ( $status ) {
			case 'PAID':
				if ( ! $paid ) {
					self::log( 'amount mismatch for ' . $order['reference'] );
					return $order;
				}
				if ( Orders::AWAITING === $order['status'] || Orders::FAILED === $order['status'] ) {
					Orders::set_status( $order['id'], Orders::PAID, 'Montonio ' . ( $payload['paymentProviderName'] ?? '' ) );
				}
				break;
			case 'ABANDONED':
				if ( Orders::AWAITING === $order['status'] ) {
					Orders::set_status( $order['id'], Orders::FAILED, 'Montonio ABANDONED' );
				}
				break;
			case 'VOIDED':
				Orders::set_status( $order['id'], Orders::FAILED, 'Montonio VOIDED' );
				break;
			case 'REFUNDED':
			case 'PARTIALLY_REFUNDED':
				Orders::set_status( $order['id'], Orders::REFUNDED, 'Montonio ' . $status );
				break;
		}
		return Orders::get( $order['id'] );
	}

	private static function log( string $message ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[kipora montonio] ' . $message );
		}
	}
}
