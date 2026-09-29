<?php
/**
 * Order screen in the admin: details, status, photo report upload.
 */

namespace Kipora\Admin;

use Kipora\Files;
use Kipora\Front\Forms;
use Kipora\Memorials;
use Kipora\Orders;
use Kipora\Pricing;

final class OrderBox {

	public static function register(): void {
		add_action( 'add_meta_boxes_' . Orders::TYPE, [ self::class, 'boxes' ] );
		add_action( 'post_edit_form_tag', [ self::class, 'multipart' ] );
		add_action( 'save_post_' . Orders::TYPE, [ self::class, 'save' ], 10, 2 );
	}

	public static function multipart( \WP_Post $post ): void {
		if ( Orders::TYPE === $post->post_type ) {
			echo ' enctype="multipart/form-data"';
		}
	}

	public static function boxes(): void {
		add_meta_box( 'kp-order', __( 'Order', 'kipora' ), [ self::class, 'details' ], Orders::TYPE, 'normal', 'high' );
		add_meta_box( 'kp-report', __( 'Photo report', 'kipora' ), [ self::class, 'report' ], Orders::TYPE, 'normal' );
		add_meta_box( 'kp-status', __( 'Status', 'kipora' ), [ self::class, 'status' ], Orders::TYPE, 'side', 'high' );
		remove_meta_box( 'slugdiv', Orders::TYPE, 'normal' );
	}

	public static function details( \WP_Post $post ): void {
		$order = Orders::get( $post->ID );
		$card  = $order ? Memorials::get( $order['memorial_id'] ) : null;
		if ( ! $order ) {
			return;
		}
		?>
		<div class="kp-admin-order">
			<table class="widefat striped">
				<tbody>
					<?php foreach ( $order['lines'] as $line ) : ?>
						<tr><td><?php echo esc_html( $line['label'] ); ?></td><td class="kp-admin-num"><?php echo esc_html( Pricing::format( (int) $line['amount'] ) ); ?></td></tr>
					<?php endforeach; ?>
					<tr><th><?php esc_html_e( 'Total', 'kipora' ); ?></th><th class="kp-admin-num"><?php echo esc_html( Pricing::format( $order['total'] ) ); ?></th></tr>
				</tbody>
			</table>

			<dl class="kp-admin-dl">
				<dt><?php esc_html_e( 'Memorial card', 'kipora' ); ?></dt>
				<dd>
					<?php if ( $card ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $card['id'] ) ); ?>"><?php echo esc_html( $card['name'] ); ?></a>
						<?php echo esc_html( ' · ' . implode( ' · ', array_filter( [ Memorials::years( $card ), Memorials::location( $card ) ] ) ) ); ?>
					<?php endif; ?>
				</dd>
				<dt><?php esc_html_e( 'Customer', 'kipora' ); ?></dt>
				<dd><?php echo esc_html( implode( ', ', array_filter( [ $order['contact']['name'] ?? '', $order['contact']['email'] ?? '', $order['contact']['phone'] ?? '' ] ) ) ); ?></dd>
				<?php if ( $order['comment'] ) : ?>
					<dt><?php esc_html_e( 'Comment', 'kipora' ); ?></dt>
					<dd><?php echo nl2br( esc_html( $order['comment'] ) ); ?></dd>
				<?php endif; ?>
				<dt><?php esc_html_e( 'Language', 'kipora' ); ?></dt>
				<dd><?php echo esc_html( strtoupper( $order['lang'] ) ); ?></dd>
				<dt><?php esc_html_e( 'Payment', 'kipora' ); ?></dt>
				<dd><?php echo esc_html( trim( $order['pay_method'] . ' ' . ( $order['montonio'] ? 'Montonio ' . $order['montonio'] : '' ) ) ); ?></dd>
			</dl>

			<h4><?php esc_html_e( 'History', 'kipora' ); ?></h4>
			<ul class="kp-admin-history">
				<?php foreach ( array_reverse( $order['history'] ) as $entry ) : ?>
					<li><?php echo esc_html( wp_date( 'd.m.Y H:i', (int) $entry['time'] ) . ' — ' . Orders::status_label( (string) $entry['status'] ) . ( $entry['note'] ? ' (' . $entry['note'] . ')' : '' ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	public static function status( \WP_Post $post ): void {
		$order = Orders::get( $post->ID );
		wp_nonce_field( 'kp_order_save', 'kp_order_nonce' );
		?>
		<select name="kp_status" class="widefat">
			<?php foreach ( Orders::statuses() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $order['status'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'When the status becomes "Done", the customer gets an email with a link to the photo report.', 'kipora' ); ?></p>
		<?php
	}

	public static function report( \WP_Post $post ): void {
		$files = Files::for_order( $post->ID );
		?>
		<div class="kp-admin-report">
			<?php foreach ( [ Files::KIND_BEFORE => __( 'Before', 'kipora' ), Files::KIND_AFTER => __( 'After', 'kipora' ) ] as $kind => $label ) : ?>
				<div class="kp-admin-report__col">
					<h4><?php echo esc_html( $label ); ?></h4>
					<div class="kp-admin-report__images">
						<?php foreach ( $files as $file ) : ?>
							<?php if ( $file['kind'] === $kind ) : ?>
								<label class="kp-admin-report__image">
									<a href="<?php echo esc_url( Files::url( (int) $file['id'] ) ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( Files::url( (int) $file['id'], true ) ); ?>" alt=""></a>
									<span><input type="checkbox" name="kp_delete_files[]" value="<?php echo esc_attr( $file['id'] ); ?>"> <?php esc_html_e( 'Delete', 'kipora' ); ?></span>
								</label>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<input type="file" name="kp_report_<?php echo esc_attr( $kind ); ?>[]" multiple accept="image/jpeg,image/png,image/webp">
				</div>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Photos are added to the order and to the memorial card archive. The customer sees them in the account. Click "Update" to save.', 'kipora' ); ?></p>
		<?php
	}

	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['kp_order_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kp_order_nonce'] ), 'kp_order_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_kipora' ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		// set_status() writes post meta, not the post: no save_post recursion.
		$order = Orders::get( $post_id );
		if ( ! $order ) {
			return;
		}

		foreach ( array_map( 'absint', (array) ( $_POST['kp_delete_files'] ?? [] ) ) as $file_id ) {
			$file = Files::get( $file_id );
			if ( $file && (int) $file['order_id'] === $post_id ) {
				Files::delete( $file_id );
			}
		}

		foreach ( [ Files::KIND_BEFORE, Files::KIND_AFTER ] as $kind ) {
			foreach ( Forms::normalize_files( $_FILES[ 'kp_report_' . $kind ] ?? [] ) as $upload ) {
				Files::store( $upload, $order['user_id'], $order['memorial_id'], $post_id, $kind );
			}
		}

		$status = sanitize_key( (string) ( $_POST['kp_status'] ?? '' ) );
		if ( $status ) {
			Orders::set_status( $post_id, $status, wp_get_current_user()->display_name );
		}
	}
}
