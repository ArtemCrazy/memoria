<?php
/**
 * Orders. One order = one calculator selection for one memorial card,
 * paid once through Montonio.
 */

namespace Kipora;

final class Orders {

	public const TYPE = 'kp_order';

	public const AWAITING = 'awaiting_payment';
	public const PAID     = 'paid';
	public const WORKING  = 'in_progress';
	public const DONE     = 'done';
	public const FAILED   = 'payment_failed';
	public const REFUNDED = 'refunded';
	public const CANCELED = 'cancelled';

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_type' ] );
	}

	public static function register_type(): void {
		register_post_type(
			self::TYPE,
			[
				'labels'          => [
					'name'          => __( 'Orders', 'kipora' ),
					'singular_name' => __( 'Order', 'kipora' ),
					'edit_item'     => __( 'Order', 'kipora' ),
					'search_items'  => __( 'Search orders', 'kipora' ),
				],
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'kipora',
				'supports'        => [ 'title' ],
				'capability_type' => 'post',
				'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
				'map_meta_cap'    => true,
			]
		);
	}

	public static function statuses(): array {
		return [
			self::AWAITING => __( 'Awaiting payment', 'kipora' ),
			self::PAID     => __( 'Paid', 'kipora' ),
			self::WORKING  => __( 'In progress', 'kipora' ),
			self::DONE     => __( 'Done', 'kipora' ),
			self::FAILED   => __( 'Payment failed', 'kipora' ),
			self::REFUNDED => __( 'Refunded', 'kipora' ),
			self::CANCELED => __( 'Cancelled', 'kipora' ),
		];
	}

	public static function status_label( string $status ): string {
		return self::statuses()[ $status ] ?? $status;
	}

	/**
	 * @param array $quote Result of Pricing::calculate() — recomputed on the server.
	 */
	public static function create( int $user_id, int $memorial_id, array $quote, array $contact, string $comment, string $lang, string $method ): int {
		$id = wp_insert_post(
			[
				'post_type'   => self::TYPE,
				'post_status' => 'publish',
				'post_author' => $user_id,
				'post_title'  => 'KP-…',
			],
			true
		);
		if ( is_wp_error( $id ) ) {
			throw new \RuntimeException( $id->get_error_message() );
		}

		wp_update_post( [ 'ID' => $id, 'post_title' => self::reference( $id ) ] );
		$meta = [
			'kp_status'      => self::AWAITING,
			'kp_direction'   => $quote['summary']['direction'],
			'kp_selection'   => $quote['summary'],
			'kp_lines'       => $quote['lines'],
			'kp_total'       => (int) $quote['total'],
			'kp_currency'    => 'EUR',
			'kp_memorial_id' => $memorial_id,
			'kp_contact'     => $contact,
			'kp_comment'     => $comment,
			'kp_lang'        => $lang,
			'kp_pay_method'  => $method,
		];
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		self::log( $id, self::AWAITING, 'created' );
		return (int) $id;
	}

	public static function reference( int $id ): string {
		return 'KP-' . $id;
	}

	public static function id_from_reference( string $reference ): int {
		return preg_match( '/^KP-(\d+)$/', $reference, $m ) ? (int) $m[1] : 0;
	}

	public static function get( int $id ): ?array {
		$post = get_post( $id );
		if ( ! $post || self::TYPE !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}
		return [
			'id'          => (int) $post->ID,
			'reference'   => self::reference( (int) $post->ID ),
			'user_id'     => (int) $post->post_author,
			'created'     => $post->post_date,
			'status'      => (string) get_post_meta( $id, 'kp_status', true ),
			'direction'   => (string) get_post_meta( $id, 'kp_direction', true ),
			'selection'   => (array) get_post_meta( $id, 'kp_selection', true ),
			'lines'       => (array) get_post_meta( $id, 'kp_lines', true ),
			'total'       => (int) get_post_meta( $id, 'kp_total', true ),
			'memorial_id' => (int) get_post_meta( $id, 'kp_memorial_id', true ),
			'contact'     => (array) get_post_meta( $id, 'kp_contact', true ),
			'comment'     => (string) get_post_meta( $id, 'kp_comment', true ),
			'lang'        => Lang::normalize( get_post_meta( $id, 'kp_lang', true ) ),
			'pay_method'  => (string) get_post_meta( $id, 'kp_pay_method', true ),
			'montonio'    => (string) get_post_meta( $id, 'kp_montonio_uuid', true ),
			'paid_at'     => (int) get_post_meta( $id, 'kp_paid_at', true ),
			'history'     => (array) get_post_meta( $id, 'kp_history', true ),
		];
	}

	/** @return array<int,array> newest first */
	public static function for_user( int $user_id ): array {
		$ids = get_posts(
			[
				'post_type'      => self::TYPE,
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			]
		);
		return array_values( array_filter( array_map( [ self::class, 'get' ], $ids ) ) );
	}

	/** @return array<int,array> */
	public static function for_memorial( int $memorial_id ): array {
		$ids = get_posts(
			[
				'post_type'      => self::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'meta_query'     => [ [ 'key' => 'kp_memorial_id', 'value' => $memorial_id ] ],
			]
		);
		return array_values( array_filter( array_map( [ self::class, 'get' ], $ids ) ) );
	}

	/**
	 * Changes status and fires `kipora_order_status` once per real change.
	 */
	public static function set_status( int $id, string $status, string $note = '' ): bool {
		$current = (string) get_post_meta( $id, 'kp_status', true );
		if ( $current === $status || ! isset( self::statuses()[ $status ] ) ) {
			return false;
		}
		update_post_meta( $id, 'kp_status', $status );
		if ( self::PAID === $status && ! get_post_meta( $id, 'kp_paid_at', true ) ) {
			update_post_meta( $id, 'kp_paid_at', time() );
		}
		self::log( $id, $status, $note );
		do_action( 'kipora_order_status', $id, $status, $current );
		return true;
	}

	private static function log( int $id, string $status, string $note ): void {
		$history   = (array) get_post_meta( $id, 'kp_history', true );
		$history[] = [ 'time' => time(), 'status' => $status, 'note' => $note ];
		update_post_meta( $id, 'kp_history', array_slice( $history, -50 ) );
	}

	/** One line for lists: "Ühekordne koristus · Metsakalmistu". */
	public static function summary( array $order ): string {
		$labels = array_map( static fn( $line ) => $line['label'], array_slice( $order['lines'], 0, 2 ) );
		return implode( ', ', $labels ) . ( count( $order['lines'] ) > 2 ? ' …' : '' );
	}
}
