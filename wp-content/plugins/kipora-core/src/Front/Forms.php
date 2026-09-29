<?php
/**
 * Form posts from the site (admin-post.php): checkout, memorial cards,
 * archive files, contact details. Every handler checks the nonce and the
 * owner, then redirects back with a notice.
 */

namespace Kipora\Front;

use Kipora\Clients;
use Kipora\Files;
use Kipora\Lang;
use Kipora\Memorials;
use Kipora\Montonio;
use Kipora\Orders;
use Kipora\Pricing;
use Kipora\TariffStore;

final class Forms {

	private const ACTIONS = [ 'checkout', 'pay_again', 'card_save', 'file_upload', 'file_delete', 'profile_save' ];

	public static function register(): void {
		foreach ( self::ACTIONS as $action ) {
			add_action( 'admin_post_kipora_' . $action, [ self::class, $action ] );
			add_action( 'admin_post_nopriv_kipora_' . $action, [ self::class, 'logged_out' ] );
		}
		// The contact form is open to everyone.
		add_action( 'admin_post_kipora_contact', [ self::class, 'contact' ] );
		add_action( 'admin_post_nopriv_kipora_contact', [ self::class, 'contact' ] );
	}

	public static function contact(): void {
		check_admin_referer( 'kipora_contact' );
		self::$lang = Lang::normalize( self::field( 'lang' ) );
		Lang::use( self::$lang );
		$back = wp_get_referer() ?: home_url( '/' );
		$back = remove_query_arg( [ 'kp_notice', 'kp_sent' ], $back );

		// Bots fill every field, people do not see this one.
		if ( '' !== self::field( 'website' ) ) {
			wp_safe_redirect( add_query_arg( 'kp_sent', 1, $back ) );
			exit;
		}
		$ip_key = 'kp_contact_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		if ( (int) get_transient( $ip_key ) >= 5 ) {
			self::contact_error( $back, __( 'Too many messages in a short time. Try again in an hour or call us.', 'kipora' ) );
		}

		$name    = mb_substr( sanitize_text_field( self::field( 'name' ) ), 0, 120 );
		$email   = sanitize_email( self::field( 'email' ) );
		$phone   = mb_substr( sanitize_text_field( self::field( 'phone' ) ), 0, 40 );
		$message = mb_substr( sanitize_textarea_field( self::field( 'message' ) ), 0, 5000 );
		if ( '' === $name || ! is_email( $email ) || '' === $message ) {
			self::contact_error( $back, __( 'Fill in the name, email and message.', 'kipora' ) );
		}

		$attachments = [];
		foreach ( array_slice( self::normalize_files( $_FILES['files'] ?? [] ), 0, 3 ) as $file ) {
			if ( (int) $file['size'] > 10 * 1024 * 1024 || ! is_uploaded_file( $file['tmp_name'] ) ) {
				self::contact_error( $back, __( 'Each file must be up to 10 MB.', 'kipora' ) );
			}
			$mime = (string) ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $file['tmp_name'] );
			if ( ! in_array( $mime, [ 'image/jpeg', 'image/png', 'image/webp', 'application/pdf' ], true ) ) {
				self::contact_error( $back, __( 'Allowed formats: JPG, PNG, WEBP and PDF.', 'kipora' ) );
			}
			$path = get_temp_dir() . 'kp-contact-' . bin2hex( random_bytes( 6 ) ) . '-' . sanitize_file_name( $file['name'] );
			move_uploaded_file( $file['tmp_name'], $path );
			$attachments[] = $path;
		}

		$sent = wp_mail(
			\Kipora\Settings::get( 'notify_email' ),
			/* translators: %s: sender name */
			sprintf( __( 'Message from the website: %s', 'kipora' ), $name ),
			implode( "\n", array_filter( [ $message, '', $name, $email, $phone, strtoupper( self::$lang ) ] ) ),
			[ 'Reply-To: ' . $name . ' <' . $email . '>' ],
			$attachments
		);
		array_map( 'wp_delete_file', $attachments );

		if ( ! $sent ) {
			self::contact_error( $back, __( 'The message was not sent. Write to us by email or call.', 'kipora' ) );
		}
		set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, HOUR_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'kp_sent', 1, $back ) . '#kp-contact' );
		exit;
	}

	private static function contact_error( string $back, string $message ): void {
		set_transient( 'kp_contact_err_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ), $message, 120 );
		set_transient( 'kp_contact_old_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ), wp_unslash( $_POST ), 300 );
		wp_safe_redirect( add_query_arg( 'kp_notice', 'contact_error', $back ) . '#kp-contact' );
		exit;
	}

	public static function logged_out(): void {
		wp_safe_redirect( Lang::page_url( 'account', self::$lang ) );
		exit;
	}

	/** Language of the page the form was sent from. */
	private static string $lang = 'et';

	private static function guard( string $action ): int {
		check_admin_referer( 'kipora_' . $action );
		self::$lang = Lang::normalize( self::field( 'lang' ) );
		Lang::use( self::$lang );
		return get_current_user_id();
	}

	private static function back( string $url, string $notice = '', string $error = '' ): void {
		if ( $error ) {
			set_transient( 'kp_err_' . get_current_user_id(), $error, 120 );
			$notice = 'error';
		}
		wp_safe_redirect( $notice ? add_query_arg( 'kp_notice', $notice, $url ) : $url );
		exit;
	}

	private static function field( string $key ): string {
		return trim( (string) wp_unslash( $_POST[ $key ] ?? '' ) );
	}

	public static function checkout(): void {
		$user_id = self::guard( 'checkout' );
		$lang    = Lang::normalize( self::field( 'lang' ) );
		$sel_raw = self::field( 'sel' );
		$back    = add_query_arg( 'sel', rawurlencode( $sel_raw ), Lang::page_url( 'checkout', $lang ) );

		// Keep what the person typed if we have to send them back.
		set_transient( 'kp_old_' . $user_id, wp_unslash( $_POST ), 600 );

		// Price is always recomputed here; the browser only says what was chosen.
		$quote = Pricing::calculate( TariffStore::get(), Shortcodes::decode_selection( $sel_raw ), $lang );
		if ( ! $quote['valid'] ) {
			self::back( Lang::page_url( 'calculator', $lang ) );
		}
		if ( empty( $_POST['terms'] ) ) {
			self::back( $back, '', __( 'Please accept the terms of sale.', 'kipora' ) );
		}

		$is_pet = Pricing::DIRECTION_PET === $quote['summary']['direction'];
		$kind   = $is_pet ? Memorials::KIND_PET : Memorials::KIND_HUMAN;

		$card_id = absint( $_POST['card'] ?? 0 );
		if ( $card_id ) {
			$card = Memorials::owned_by( $card_id, $user_id );
			if ( ! $card || $card['kind'] !== $kind ) {
				self::back( $back, '', __( 'Card not found.', 'kipora' ) );
			}
		} else {
			$input = [
				'kind' => $kind,
				'name' => self::field( 'name' ),
				'born' => self::field( 'born' ),
				'died' => self::field( 'died' ),
			];
			if ( ! $is_pet ) {
				$input += [ 'sector' => self::field( 'sector' ), 'plot' => self::field( 'plot' ) ];
			}
			$card_id = Memorials::save( $user_id, $input );
			if ( is_wp_error( $card_id ) ) {
				self::back( $back, '', $card_id->get_error_message() );
			}
		}
		if ( ! $is_pet ) {
			// The calculator knows the cemetery: keep the card location in sync with the latest order.
			update_post_meta( $card_id, 'kp_cemetery_id', $quote['summary']['cemetery'] );
			update_post_meta( $card_id, 'kp_cemetery_name', Shortcodes::cemetery_name( $quote['summary']['cemetery'] ) );
		}

		$email = self::field( 'email' );
		$phone = self::field( 'phone' );
		if ( ! is_email( $email ) ) {
			self::back( $back, '', __( 'Enter an email address: we send the order confirmation and photo report notice there.', 'kipora' ) );
		}
		$saved = Clients::save_contact( $user_id, $email, $phone );
		if ( is_wp_error( $saved ) ) {
			self::back( $back, '', $saved->get_error_message() );
		}

		$contact  = Clients::contact( $user_id );
		$method   = Montonio::METHOD_CARD === self::field( 'method' ) ? Montonio::METHOD_CARD : Montonio::METHOD_BANK;
		$order_id = Orders::create( $user_id, (int) $card_id, $quote, $contact, mb_substr( sanitize_textarea_field( self::field( 'comment' ) ), 0, 1000 ), $lang, $method );
		delete_transient( 'kp_old_' . $user_id );

		self::redirect_to_payment( $order_id );
	}

	public static function pay_again(): void {
		$user_id = self::guard( 'pay_again' );
		$order   = Orders::get( absint( $_POST['order'] ?? 0 ) );
		if ( ! $order || $order['user_id'] !== $user_id || ! in_array( $order['status'], [ Orders::AWAITING, Orders::FAILED ], true ) ) {
			self::back( Lang::page_url( 'account', self::$lang ) );
		}
		if ( Orders::FAILED === $order['status'] ) {
			Orders::set_status( $order['id'], Orders::AWAITING, 'retry' );
		}
		self::redirect_to_payment( $order['id'] );
	}

	private static function redirect_to_payment( int $order_id ): void {
		$order = Orders::get( $order_id );
		$url   = Montonio::create_payment( $order );
		if ( is_wp_error( $url ) ) {
			self::back( Montonio::return_url( $order ), Montonio::configured() ? 'pay_error' : 'no_payment' );
		}
		// Montonio's domain is not ours, so wp_safe_redirect would refuse it.
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}

	public static function card_save(): void {
		$user_id = self::guard( 'card_save' );
		$id      = absint( $_POST['id'] ?? 0 );
		$input   = [
			'name'      => self::field( 'name' ),
			'born'      => self::field( 'born' ),
			'died'      => self::field( 'died' ),
			'biography' => self::field( 'biography' ),
		];
		if ( ! $id ) {
			$input['kind'] = self::field( 'kind' );
		}
		$existing = $id ? Memorials::owned_by( $id, $user_id ) : null;
		if ( ( $existing && Memorials::KIND_HUMAN === $existing['kind'] ) || ( ! $id && Memorials::KIND_PET !== $input['kind'] ) ) {
			$input += [
				'cemetery_name' => self::field( 'cemetery_name' ),
				'sector'        => self::field( 'sector' ),
				'plot'          => self::field( 'plot' ),
			];
		}

		$result = Memorials::save( $user_id, $input, $id );
		$base   = Lang::page_url( 'account', self::$lang );
		if ( is_wp_error( $result ) ) {
			self::back( $id ? add_query_arg( [ 'view' => 'card', 'id' => $id ], $base ) : $base, '', $result->get_error_message() );
		}
		self::back( add_query_arg( [ 'view' => 'card', 'id' => $result ], $base ), $id ? 'card_saved' : 'card_created' );
	}

	public static function file_upload(): void {
		$user_id = self::guard( 'file_upload' );
		$card    = Memorials::owned_by( absint( $_POST['id'] ?? 0 ), $user_id );
		$base    = Lang::page_url( 'account', self::$lang );
		if ( ! $card ) {
			self::back( $base );
		}
		$back = add_query_arg( [ 'view' => 'card', 'id' => $card['id'] ], $base );

		$files = self::normalize_files( $_FILES['files'] ?? [] );
		if ( ! $files ) {
			self::back( $back, '', __( 'Choose at least one file.', 'kipora' ) );
		}
		foreach ( array_slice( $files, 0, 20 ) as $file ) {
			$result = Files::store( $file, $user_id, $card['id'], 0, Files::KIND_ARCHIVE );
			if ( is_wp_error( $result ) ) {
				self::back( $back, '', $file['name'] . ': ' . $result->get_error_message() );
			}
		}
		self::back( $back, 'file_saved' );
	}

	public static function file_delete(): void {
		$user_id = self::guard( 'file_delete' );
		$file    = Files::get( absint( $_POST['file'] ?? 0 ) );
		$base    = Lang::page_url( 'account', self::$lang );
		// Customers delete their own archive files; photo reports belong to the order.
		if ( ! $file || Files::KIND_ARCHIVE !== $file['kind'] || ! Memorials::owned_by( (int) $file['memorial_id'], $user_id ) ) {
			self::back( $base );
		}
		Files::delete( (int) $file['id'] );
		self::back( add_query_arg( [ 'view' => 'card', 'id' => $file['memorial_id'] ], $base ), 'file_deleted' );
	}

	public static function profile_save(): void {
		$user_id = self::guard( 'profile_save' );
		$back    = add_query_arg( 'view', 'profile', Lang::page_url( 'account', self::$lang ) );
		$result  = Clients::save_contact( $user_id, self::field( 'email' ), self::field( 'phone' ) );
		if ( is_wp_error( $result ) ) {
			self::back( $back, '', $result->get_error_message() );
		}
		self::back( $back, 'profile_saved' );
	}

	/** $_FILES['files'] with multiple="" comes as parallel arrays. */
	public static function normalize_files( array $raw ): array {
		if ( ! isset( $raw['name'] ) ) {
			return [];
		}
		if ( ! is_array( $raw['name'] ) ) {
			return UPLOAD_ERR_NO_FILE === (int) $raw['error'] ? [] : [ $raw ];
		}
		$out = [];
		foreach ( array_keys( $raw['name'] ) as $i ) {
			if ( UPLOAD_ERR_NO_FILE === (int) $raw['error'][ $i ] ) {
				continue;
			}
			$out[] = [
				'name'     => $raw['name'][ $i ],
				'type'     => $raw['type'][ $i ],
				'tmp_name' => $raw['tmp_name'][ $i ],
				'error'    => $raw['error'][ $i ],
				'size'     => $raw['size'][ $i ],
			];
		}
		return $out;
	}
}
