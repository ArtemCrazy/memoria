<?php
/**
 * REST endpoints used by the calculator, the login widget and Montonio.
 * Everything else (checkout, account) is plain form posts, see Front\Forms.
 */

namespace Kipora;

use Kipora\Auth\AuthException;
use Kipora\Auth\Flow;

final class Rest {

	public const NS = 'kipora/v1';

	public static function register(): void {
		add_action( 'rest_api_init', [ self::class, 'routes' ] );
	}

	public static function routes(): void {
		register_rest_route(
			self::NS,
			'/quote',
			[
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [ self::class, 'quote' ],
			]
		);
		register_rest_route(
			self::NS,
			'/auth/start',
			[
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [ self::class, 'auth_start' ],
			]
		);
		register_rest_route(
			self::NS,
			'/auth/status',
			[
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [ self::class, 'auth_status' ],
			]
		);
		register_rest_route(
			self::NS,
			'/montonio/notify',
			[
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [ self::class, 'montonio_notify' ],
			]
		);
	}

	public static function quote( \WP_REST_Request $request ): \WP_REST_Response {
		$lang  = Lang::normalize( $request->get_param( 'lang' ) );
		$quote = Pricing::calculate( TariffStore::get(), (array) $request->get_param( 'selection' ), $lang );
		return new \WP_REST_Response( self::present_quote( $quote ) );
	}

	/** Adds formatted amounts for display. */
	public static function present_quote( array $quote ): array {
		$quote['lines'] = array_map(
			static fn( $line ) => $line + [ 'formatted' => Pricing::format( (int) $line['amount'] ) ],
			$quote['lines']
		);
		$quote['total_formatted'] = Pricing::format( (int) $quote['total'] );
		return $quote;
	}

	public static function auth_start( \WP_REST_Request $request ): \WP_REST_Response {
		Lang::use( (string) $request->get_param( 'lang' ) );
		try {
			$result = Flow::start(
				(string) $request->get_param( 'method' ),
				(string) $request->get_param( 'idcode' ),
				(string) $request->get_param( 'phone' ),
				Lang::normalize( $request->get_param( 'lang' ) )
			);
			return new \WP_REST_Response( $result );
		} catch ( AuthException $e ) {
			return self::auth_error( $e );
		} catch ( \Throwable $e ) {
			error_log( '[kipora auth] ' . $e->getMessage() );
			return self::auth_error( new AuthException( 'service_unavailable' ) );
		}
	}

	public static function auth_status( \WP_REST_Request $request ): \WP_REST_Response {
		Lang::use( (string) $request->get_param( 'lang' ) );
		try {
			return new \WP_REST_Response( Flow::status( (string) $request->get_param( 'key' ) ) );
		} catch ( AuthException $e ) {
			return self::auth_error( $e );
		} catch ( \Throwable $e ) {
			error_log( '[kipora auth] ' . $e->getMessage() );
			return self::auth_error( new AuthException( 'service_unavailable' ) );
		}
	}

	private static function auth_error( AuthException $e ): \WP_REST_Response {
		if ( $e->getMessage() !== $e->reason ) {
			error_log( '[kipora auth] ' . $e->reason . ': ' . $e->getMessage() );
		}
		return new \WP_REST_Response(
			[
				'state'   => 'error',
				'reason'  => $e->reason,
				'message' => Flow::message( $e->reason ),
			],
			in_array( $e->reason, [ 'idcode', 'phone_format', 'input' ], true ) ? 400 : 200
		);
	}

	/** Montonio retries until it gets 2xx, so unknown tokens still get 200. */
	public static function montonio_notify( \WP_REST_Request $request ): \WP_REST_Response {
		$token = (string) ( $request->get_param( 'orderToken' ) ?? '' );
		if ( '' !== $token ) {
			Montonio::apply_token( $token );
		}
		return new \WP_REST_Response( [ 'ok' => true ], 200 );
	}
}
