<?php

namespace Kipora\Admin;

use Kipora\Memorials;
use Kipora\Orders;
use Kipora\Pricing;

final class Menu {

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );

		add_filter( 'manage_' . Orders::TYPE . '_posts_columns', [ self::class, 'order_columns' ] );
		add_action( 'manage_' . Orders::TYPE . '_posts_custom_column', [ self::class, 'order_column' ], 10, 2 );
		add_filter( 'manage_' . Memorials::TYPE . '_posts_columns', [ self::class, 'memorial_columns' ] );
		add_action( 'manage_' . Memorials::TYPE . '_posts_custom_column', [ self::class, 'memorial_column' ], 10, 2 );
		add_filter( 'post_row_actions', [ self::class, 'row_actions' ], 10, 2 );

		OrderBox::register();
		MemorialBox::register();
		TariffsPage::register();
		SettingsPage::register();
	}

	public static function menu(): void {
		add_menu_page( 'KIPORA', 'KIPORA', 'manage_kipora', 'kipora', '__return_null', 'dashicons-heart', 26 );
		// The CPTs attach themselves under "kipora"; drop the placeholder first item.
		add_action(
			'admin_menu',
			static function (): void {
				remove_submenu_page( 'kipora', 'kipora' );
			},
			99
		);
	}

	public static function assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		if ( in_array( $screen->post_type, [ Orders::TYPE, Memorials::TYPE ], true ) || str_contains( $hook, 'kipora' ) ) {
			wp_enqueue_style( 'kipora-admin', KIPORA_URL . 'assets/css/admin.css', [], (string) filemtime( KIPORA_DIR . 'assets/css/admin.css' ) );
		}
	}

	public static function order_columns( array $columns ): array {
		return [
			'cb'          => $columns['cb'] ?? '',
			'title'       => __( 'Order', 'kipora' ),
			'kp_status'   => __( 'Status', 'kipora' ),
			'kp_what'     => __( 'Service', 'kipora' ),
			'kp_customer' => __( 'Customer', 'kipora' ),
			'kp_total'    => __( 'Total', 'kipora' ),
			'date'        => $columns['date'] ?? __( 'Date', 'kipora' ),
		];
	}

	public static function order_column( string $column, int $post_id ): void {
		$order = Orders::get( $post_id );
		if ( ! $order ) {
			return;
		}
		switch ( $column ) {
			case 'kp_status':
				echo '<span class="kp-admin-status kp-admin-status--' . esc_attr( $order['status'] ) . '">' . esc_html( Orders::status_label( $order['status'] ) ) . '</span>';
				break;
			case 'kp_what':
				$card = Memorials::get( $order['memorial_id'] );
				echo esc_html( Orders::summary( $order ) );
				if ( $card ) {
					echo '<br><small>' . esc_html( $card['name'] . ( Memorials::location( $card ) ? ' · ' . Memorials::location( $card ) : '' ) ) . '</small>';
				}
				break;
			case 'kp_customer':
				echo esc_html( $order['contact']['name'] ?? '' ) . '<br><small>' . esc_html( ( $order['contact']['email'] ?? '' ) . ' ' . ( $order['contact']['phone'] ?? '' ) ) . '</small>';
				break;
			case 'kp_total':
				echo esc_html( Pricing::format( $order['total'] ) );
				break;
		}
	}

	public static function memorial_columns( array $columns ): array {
		return [
			'cb'       => $columns['cb'] ?? '',
			'title'    => __( 'Name', 'kipora' ),
			'kp_kind'  => __( 'Type', 'kipora' ),
			'kp_place' => __( 'Location', 'kipora' ),
			'author'   => __( 'Customer', 'kipora' ),
			'date'     => $columns['date'] ?? __( 'Date', 'kipora' ),
		];
	}

	public static function memorial_column( string $column, int $post_id ): void {
		$card = Memorials::get( $post_id );
		if ( ! $card ) {
			return;
		}
		if ( 'kp_kind' === $column ) {
			echo esc_html( Memorials::KIND_PET === $card['kind'] ? __( 'Pet', 'kipora' ) : __( 'Person', 'kipora' ) );
		} elseif ( 'kp_place' === $column ) {
			echo esc_html( Memorials::location( $card ) );
		}
	}

	public static function row_actions( array $actions, \WP_Post $post ): array {
		if ( in_array( $post->post_type, [ Orders::TYPE, Memorials::TYPE ], true ) ) {
			unset( $actions['inline hide-if-no-js'], $actions['view'] );
		}
		return $actions;
	}
}
