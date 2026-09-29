<?php
/**
 * Page building blocks. Pages hold a shortcode, so the client can edit
 * the text around them in the block editor:
 * [kipora_calculator] [kipora_checkout] [kipora_account] [kipora_login]
 */

namespace Kipora\Front;

use Kipora\Lang;
use Kipora\Memorials;
use Kipora\Orders;
use Kipora\Pricing;
use Kipora\Rest;
use Kipora\Settings;
use Kipora\TariffStore;

final class Shortcodes {

	public static function register(): void {
		add_shortcode( 'kipora_calculator', [ self::class, 'calculator' ] );
		add_shortcode( 'kipora_checkout', [ self::class, 'checkout' ] );
		add_shortcode( 'kipora_account', [ self::class, 'account' ] );
		add_shortcode( 'kipora_login', [ self::class, 'login' ] );
		add_shortcode( 'kipora_contact', [ self::class, 'contact' ] );
		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
	}

	public static function assets(): void {
		wp_register_style( 'kipora', KIPORA_URL . 'assets/css/kipora.css', [], self::ver( 'assets/css/kipora.css' ) );
		wp_register_script( 'kipora-calculator', KIPORA_URL . 'assets/js/calculator.js', [], self::ver( 'assets/js/calculator.js' ), true );
		wp_register_script( 'kipora-login', KIPORA_URL . 'assets/js/login.js', [], self::ver( 'assets/js/login.js' ), true );
		wp_register_script( 'kipora-account', KIPORA_URL . 'assets/js/account.js', [], self::ver( 'assets/js/account.js' ), true );
		wp_enqueue_style( 'kipora' );
	}

	private static function ver( string $rel ): string {
		$path = KIPORA_DIR . $rel;
		return is_file( $path ) ? (string) filemtime( $path ) : KIPORA_VERSION;
	}

	public static function calculator(): string {
		$lang = Lang::current();
		wp_enqueue_script( 'kipora-calculator' );
		wp_add_inline_script(
			'kipora-calculator',
			'window.kiporaCalculator = ' . wp_json_encode(
				[
					'lang'     => $lang,
					'tariffs'  => TariffStore::public_view( $lang ),
					'quoteUrl' => rest_url( 'kipora/v1/quote' ),
					'checkout' => Lang::page_url( 'checkout', $lang ),
					'strings'  => self::calculator_strings(),
				]
			) . ';',
			'before'
		);
		return self::template( 'calculator' );
	}

	private static function calculator_strings(): array {
		return [
			'direction'      => __( 'What do you need?', 'kipora' ),
			'grave'          => __( 'Grave care', 'kipora' ),
			'graveHint'      => __( 'Cleaning, seasonal care, flowers and candles', 'kipora' ),
			'pet'            => __( 'Pet services', 'kipora' ),
			'petHint'        => __( 'Cremation, transport, urn and memorial page', 'kipora' ),
			'package'        => __( 'Service', 'kipora' ),
			'size'           => __( 'Plot size', 'kipora' ),
			'extras'         => __( 'Additional work', 'kipora' ),
			'cemetery'       => __( 'Cemetery', 'kipora' ),
			'cemeteryPick'   => __( 'Choose a cemetery', 'kipora' ),
			'tallinn'        => __( 'Tallinn', 'kipora' ),
			'harjumaa'       => __( 'Harju County', 'kipora' ),
			'kmNote'         => __( '%s per km', 'kipora' ),
			'services'       => __( 'Services', 'kipora' ),
			'urn'            => __( 'Urn', 'kipora' ),
			'options'        => __( 'Options', 'kipora' ),
			'from'           => __( 'from %s', 'kipora' ),
			'total'          => __( 'Total', 'kipora' ),
			'continue'       => __( 'Continue to order', 'kipora' ),
			'summary'        => __( 'Your order', 'kipora' ),
			'incomplete'     => __( 'Choose the service, plot size and cemetery to see the price.', 'kipora' ),
			'incompletePet'  => __( 'Choose at least one service to see the price.', 'kipora' ),
			'error'          => __( 'The price could not be calculated. Refresh the page and try again.', 'kipora' ),
			'otherCemetery'  => __( 'Your cemetery is not on the list? Write to us and we will calculate the price.', 'kipora' ),
		];
	}

	public static function login( $atts = [] ): string {
		$lang = Lang::current();
		if ( is_user_logged_in() ) {
			return '';
		}
		wp_enqueue_script( 'kipora-login' );
		wp_add_inline_script(
			'kipora-login',
			'window.kiporaLogin = ' . wp_json_encode(
				[
					'lang'      => $lang,
					'startUrl'  => rest_url( 'kipora/v1/auth/start' ),
					'statusUrl' => rest_url( 'kipora/v1/auth/status' ),
					'strings'   => [
						'wait'    => __( 'Check your phone. Enter PIN1 if the control code matches.', 'kipora' ),
						'code'    => __( 'Control code', 'kipora' ),
						'success' => __( 'Logged in. Opening your page…', 'kipora' ),
						'network' => __( 'No connection to the server. Check the internet and try again.', 'kipora' ),
						'cancel'  => __( 'Cancel', 'kipora' ),
					],
				]
			) . ';',
			'before'
		);
		return self::template( 'login', [ 'demo' => Settings::is_demo_auth(), 'intro' => (string) ( $atts['intro'] ?? '' ) ] );
	}

	public static function checkout(): string {
		$lang = Lang::current();

		if ( ! empty( $_GET['kp_order'] ) ) {
			return self::payment_result( absint( $_GET['kp_order'] ) );
		}

		$selection = self::decode_selection( (string) ( $_GET['sel'] ?? '' ) );
		$quote     = Pricing::calculate( TariffStore::get(), $selection, $lang );
		if ( ! $quote['valid'] ) {
			return self::template( 'checkout-empty', [ 'calculator' => Lang::page_url( 'calculator', $lang ) ] );
		}

		$quote = Rest::present_quote( $quote );
		$vars  = [
			'quote'      => $quote,
			'sel'        => (string) $_GET['sel'],
			'calculator' => add_query_arg( 'sel', rawurlencode( (string) $_GET['sel'] ), Lang::page_url( 'calculator', $lang ) ),
			'cemetery'   => self::cemetery_name( $quote['summary']['cemetery'] ?? '' ),
		];

		if ( ! is_user_logged_in() ) {
			return self::template( 'checkout-login', $vars + [ 'login' => self::login( [ 'intro' => 'checkout' ] ) ] );
		}

		wp_enqueue_script( 'kipora-account' );
		$user_id = get_current_user_id();
		$kind    = Pricing::DIRECTION_PET === $quote['summary']['direction'] ? Memorials::KIND_PET : Memorials::KIND_HUMAN;
		return self::template(
			'checkout',
			$vars + [
				'kind'    => $kind,
				'cards'   => Memorials::for_owner( $user_id, $kind ),
				'contact' => \Kipora\Clients::contact( $user_id ),
				'terms'   => Lang::page_url( 'terms', $lang ),
				'notice'  => self::notice(),
				'old'     => (array) get_transient( 'kp_old_' . $user_id ),
			]
		);
	}

	private static function payment_result( int $order_id ): string {
		$order = Orders::get( $order_id );
		if ( ! $order || $order['user_id'] !== get_current_user_id() ) {
			return self::template( 'checkout-empty', [ 'calculator' => Lang::page_url( 'calculator' ) ] );
		}
		if ( ! empty( $_GET['order-token'] ) ) {
			$order = \Kipora\Montonio::apply_token( sanitize_text_field( wp_unslash( $_GET['order-token'] ) ) ) ?? $order;
		}
		return self::template(
			'checkout-result',
			[
				'order'   => $order,
				'account' => Lang::page_url( 'account' ),
				'notice'  => self::notice(),
			]
		);
	}

	public static function account(): string {
		if ( ! is_user_logged_in() ) {
			return self::template( 'account-login', [ 'login' => self::login( [ 'intro' => 'account' ] ) ] );
		}
		wp_enqueue_script( 'kipora-account' );
		$user_id = get_current_user_id();
		$view    = sanitize_key( (string) ( $_GET['view'] ?? 'cards' ) );
		$vars    = [
			'view'    => $view,
			'user'    => wp_get_current_user(),
			'base'    => Lang::page_url( 'account' ),
			'notice'  => self::notice(),
			'contact' => \Kipora\Clients::contact( $user_id ),
		];

		if ( 'card' === $view ) {
			$card = Memorials::owned_by( absint( $_GET['id'] ?? 0 ), $user_id );
			if ( $card ) {
				$vars['card']   = $card;
				$vars['files']  = \Kipora\Files::for_memorial( $card['id'] );
				$vars['orders'] = Orders::for_memorial( $card['id'] );
				return self::template( 'account', $vars );
			}
			$vars['view'] = 'cards';
		}
		if ( 'orders' === $vars['view'] ) {
			$vars['orders'] = Orders::for_user( $user_id );
		} elseif ( 'profile' !== $vars['view'] ) {
			$vars['view']  = 'cards';
			$vars['cards'] = Memorials::for_owner( $user_id );
		}
		return self::template( 'account', $vars );
	}

	public static function contact(): string {
		$key    = md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$notice = [];
		$old    = [];
		if ( ! empty( $_GET['kp_sent'] ) ) {
			$notice = [ 'type' => 'ok', 'text' => __( 'Thank you, the message is sent. We usually answer within one working day.', 'kipora' ) ];
		} elseif ( 'contact_error' === ( $_GET['kp_notice'] ?? '' ) ) {
			$notice = [ 'type' => 'error', 'text' => (string) get_transient( 'kp_contact_err_' . $key ) ];
			$old    = (array) get_transient( 'kp_contact_old_' . $key );
		}
		wp_enqueue_script( 'kipora-account' );
		return self::template( 'contact', [ 'notice' => $notice, 'old' => $old ] );
	}

	public static function decode_selection( string $raw ): array {
		if ( '' === $raw || strlen( $raw ) > 2000 ) {
			return [];
		}
		$json = base64_decode( strtr( $raw, '-_', '+/' ), true );
		$data = $json ? json_decode( $json, true ) : null;
		return is_array( $data ) ? $data : [];
	}

	public static function cemetery_name( string $id ): string {
		foreach ( TariffStore::get()['grave']['cemeteries'] ?? [] as $row ) {
			if ( $row['id'] === $id ) {
				return (string) $row['name'];
			}
		}
		return '';
	}

	/** One-off message after a form post: ?kp_notice=key */
	public static function notice(): array {
		$key  = sanitize_key( (string) ( $_GET['kp_notice'] ?? '' ) );
		$list = [
			'card_saved'    => [ 'ok', __( 'Memorial card saved.', 'kipora' ) ],
			'card_created'  => [ 'ok', __( 'Memorial card created. You can add photos and documents below.', 'kipora' ) ],
			'file_saved'    => [ 'ok', __( 'Files added to the archive.', 'kipora' ) ],
			'file_deleted'  => [ 'ok', __( 'File deleted.', 'kipora' ) ],
			'profile_saved' => [ 'ok', __( 'Contact details saved.', 'kipora' ) ],
			'no_payment'    => [ 'warn', __( 'The order is saved, but online payment is not connected yet. We will contact you about payment.', 'kipora' ) ],
			'pay_error'     => [ 'error', __( 'The payment service did not respond. Try again in a minute.', 'kipora' ) ],
		];
		if ( isset( $list[ $key ] ) ) {
			return [ 'type' => $list[ $key ][0], 'text' => $list[ $key ][1] ];
		}
		$error = get_transient( 'kp_err_' . get_current_user_id() );
		if ( $error && 'error' === $key ) {
			delete_transient( 'kp_err_' . get_current_user_id() );
			return [ 'type' => 'error', 'text' => (string) $error ];
		}
		return [];
	}

	public static function template( string $name, array $vars = [] ): string {
		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		ob_start();
		include KIPORA_DIR . 'templates/' . $name . '.php';
		return (string) ob_get_clean();
	}
}
