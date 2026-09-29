<?php
/**
 * Memorial card: a person or a pet the customer takes care of.
 * Private in phase 1. `kp_public` and the post slug are reserved for
 * public QR memorials in phase 2.
 */

namespace Kipora;

final class Memorials {

	public const TYPE = 'kp_memorial';

	public const KIND_HUMAN = 'human';
	public const KIND_PET   = 'pet';

	private const META = [ 'kind', 'born', 'died', 'cemetery_id', 'cemetery_name', 'sector', 'plot', 'public' ];

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_type' ] );
	}

	public static function register_type(): void {
		register_post_type(
			self::TYPE,
			[
				'labels'          => [
					'name'          => __( 'Memorial cards', 'kipora' ),
					'singular_name' => __( 'Memorial card', 'kipora' ),
					'edit_item'     => __( 'Memorial card', 'kipora' ),
				],
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'kipora',
				'supports'        => [ 'title', 'author' ],
				'capability_type' => 'post',
				'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
				'map_meta_cap'    => true,
			]
		);
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public static function get( int $id ): ?array {
		$post = get_post( $id );
		if ( ! $post || self::TYPE !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}
		$card = [
			'id'        => (int) $post->ID,
			'owner_id'  => (int) $post->post_author,
			'name'      => $post->post_title,
			'biography' => $post->post_content,
			'slug'      => $post->post_name,
			'created'   => $post->post_date,
		];
		foreach ( self::META as $key ) {
			$card[ $key ] = (string) get_post_meta( $post->ID, 'kp_' . $key, true );
		}
		$card['kind'] = self::KIND_PET === $card['kind'] ? self::KIND_PET : self::KIND_HUMAN;
		return $card;
	}

	/** @return array<int,array<string,mixed>> */
	public static function for_owner( int $user_id, ?string $kind = null ): array {
		$query = [
			'post_type'      => self::TYPE,
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		];
		if ( $kind ) {
			$query['meta_query'] = [ [ 'key' => 'kp_kind', 'value' => $kind ] ];
		}
		return array_values( array_filter( array_map( [ self::class, 'get' ], get_posts( $query ) ) ) );
	}

	public static function owned_by( int $id, int $user_id ): ?array {
		$card = self::get( $id );
		return $card && $card['owner_id'] === $user_id ? $card : null;
	}

	/**
	 * Creates or updates a card from user input. Returns the card id or an error.
	 *
	 * @return int|\WP_Error
	 */
	public static function save( int $user_id, array $input, int $id = 0 ) {
		$name = trim( sanitize_text_field( (string) ( $input['name'] ?? '' ) ) );
		if ( '' === $name ) {
			return new \WP_Error( 'name', __( 'Enter the name.', 'kipora' ) );
		}

		$postarr = [
			'post_type'    => self::TYPE,
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_title'   => $name,
			'post_content' => sanitize_textarea_field( (string) ( $input['biography'] ?? '' ) ),
		];

		$is_new = ! $id;
		if ( ! $is_new ) {
			if ( ! self::owned_by( $id, $user_id ) ) {
				return new \WP_Error( 'forbidden', __( 'Card not found.', 'kipora' ) );
			}
			$postarr['ID'] = $id;
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {
			// Unguessable slug: phase 2 public memorial URLs must not be enumerable.
			$postarr['post_name'] = strtolower( wp_generate_password( 12, false ) );
			$result               = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$id = (int) $result;

		if ( $is_new ) {
			update_post_meta( $id, 'kp_public', '0' );
		}
		if ( isset( $input['kind'] ) ) {
			update_post_meta( $id, 'kp_kind', self::KIND_PET === $input['kind'] ? self::KIND_PET : self::KIND_HUMAN );
		}
		foreach ( [ 'born', 'died', 'sector', 'plot', 'cemetery_name' ] as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				update_post_meta( $id, 'kp_' . $key, mb_substr( sanitize_text_field( (string) $input[ $key ] ), 0, 120 ) );
			}
		}
		if ( array_key_exists( 'cemetery_id', $input ) ) {
			update_post_meta( $id, 'kp_cemetery_id', sanitize_key( (string) $input['cemetery_id'] ) );
		}
		return $id;
	}

	/** "1938 – 2019" or a single year. */
	public static function years( array $card ): string {
		$born = trim( (string) $card['born'] );
		$died = trim( (string) $card['died'] );
		if ( $born && $died ) {
			return $born . ' – ' . $died;
		}
		return $born ?: $died;
	}

	public static function location( array $card ): string {
		$parts = array_filter(
			[
				$card['cemetery_name'],
				$card['sector'] ? sprintf( __( 'sector %s', 'kipora' ), $card['sector'] ) : '',
				$card['plot'] ? sprintf( __( 'plot %s', 'kipora' ), $card['plot'] ) : '',
			]
		);
		return implode( ', ', $parts );
	}
}
