<?php
/**
 * Memorial card in the admin: read-only view of what the customer entered,
 * the archive and the orders.
 */

namespace Kipora\Admin;

use Kipora\Files;
use Kipora\Memorials;
use Kipora\Orders;

final class MemorialBox {

	public static function register(): void {
		add_action( 'add_meta_boxes_' . Memorials::TYPE, [ self::class, 'boxes' ] );
	}

	public static function boxes(): void {
		add_meta_box( 'kp-memorial', __( 'Memorial card', 'kipora' ), [ self::class, 'render' ], Memorials::TYPE, 'normal', 'high' );
		remove_meta_box( 'slugdiv', Memorials::TYPE, 'normal' );
	}

	public static function render( \WP_Post $post ): void {
		$card = Memorials::get( $post->ID );
		if ( ! $card ) {
			return;
		}
		$files = Files::for_memorial( $card['id'] );
		?>
		<dl class="kp-admin-dl">
			<dt><?php esc_html_e( 'Type', 'kipora' ); ?></dt>
			<dd><?php echo esc_html( Memorials::KIND_PET === $card['kind'] ? __( 'Pet', 'kipora' ) : __( 'Person', 'kipora' ) ); ?></dd>
			<dt><?php esc_html_e( 'Years', 'kipora' ); ?></dt>
			<dd><?php echo esc_html( Memorials::years( $card ) ); ?></dd>
			<dt><?php esc_html_e( 'Location', 'kipora' ); ?></dt>
			<dd><?php echo esc_html( Memorials::location( $card ) ); ?></dd>
			<dt><?php esc_html_e( 'Biography', 'kipora' ); ?></dt>
			<dd><?php echo nl2br( esc_html( $card['biography'] ) ); ?></dd>
		</dl>

		<h4><?php esc_html_e( 'Archive', 'kipora' ); ?></h4>
		<div class="kp-admin-report__images">
			<?php foreach ( $files as $file ) : ?>
				<a class="kp-admin-report__image" href="<?php echo esc_url( Files::url( (int) $file['id'] ) ); ?>" target="_blank" rel="noopener">
					<?php if ( Files::is_image( $file ) ) : ?>
						<img src="<?php echo esc_url( Files::url( (int) $file['id'], true ) ); ?>" alt="">
					<?php else : ?>
						<span class="kp-admin-doc">PDF</span>
					<?php endif; ?>
					<span><?php echo esc_html( $file['name'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<h4><?php esc_html_e( 'Orders', 'kipora' ); ?></h4>
		<ul>
			<?php foreach ( Orders::for_memorial( $card['id'] ) as $order ) : ?>
				<li><a href="<?php echo esc_url( get_edit_post_link( $order['id'] ) ); ?>"><?php echo esc_html( $order['reference'] ); ?></a> — <?php echo esc_html( Orders::status_label( $order['status'] ) . ', ' . Orders::summary( $order ) ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}
