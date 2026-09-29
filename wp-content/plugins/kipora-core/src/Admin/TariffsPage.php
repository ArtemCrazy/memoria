<?php
/**
 * "Prices" screen: every number of the calculator in one place.
 * Rows can be added, hidden (kept for old orders) or removed.
 */

namespace Kipora\Admin;

use Kipora\TariffStore;

final class TariffsPage {

	public const SLUG = 'kipora-prices';

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 20 );
		add_action( 'admin_post_kipora_tariffs', [ self::class, 'save' ] );
	}

	public static function menu(): void {
		add_submenu_page( 'kipora', __( 'Prices', 'kipora' ), __( 'Prices', 'kipora' ), 'manage_kipora', self::SLUG, [ self::class, 'render' ] );
	}

	/** Table definitions: path in the tree => columns. */
	private static function sections(): array {
		$label = [ 'label.et' => 'ET', 'label.ru' => 'RU', 'label.en' => 'EN' ];
		return [
			'grave.packages'   => [ __( 'Grave care: services', 'kipora' ), $label + [ 'price' => __( 'Price, €', 'kipora' ), 'per_size' => __( '× plot size', 'kipora' ), 'extras' => __( 'Extra work allowed', 'kipora' ) ] ],
			'grave.sizes'      => [ __( 'Plot sizes', 'kipora' ), $label + [ 'coef' => __( 'Coefficient', 'kipora' ) ] ],
			'grave.extras'     => [ __( 'Additional work', 'kipora' ), $label + [ 'price' => __( 'Price, €', 'kipora' ), 'per_size' => __( '× plot size', 'kipora' ) ] ],
			'grave.cemeteries' => [ __( 'Cemeteries', 'kipora' ), [ 'name' => __( 'Name', 'kipora' ), 'zone' => __( 'Zone', 'kipora' ), 'km' => __( 'Distance, km', 'kipora' ) ] ],
			'pet.services'     => [ __( 'Pets: base services', 'kipora' ), $label + [ 'price' => __( 'Price, €', 'kipora' ) ] ],
			'pet.urns'         => [ __( 'Pets: urns', 'kipora' ), $label + [ 'price' => __( 'Price, €', 'kipora' ) ] ],
			'pet.options'      => [ __( 'Pets: options', 'kipora' ), $label + [ 'price' => __( 'Price, €', 'kipora' ) ] ],
		];
	}

	public static function render(): void {
		$tree = TariffStore::get();
		wp_enqueue_script( 'kipora-admin-tariffs', KIPORA_URL . 'assets/js/admin-tariffs.js', [], (string) filemtime( KIPORA_DIR . 'assets/js/admin-tariffs.js' ), true );
		?>
		<div class="wrap kp-admin-prices">
			<h1><?php esc_html_e( 'Prices', 'kipora' ); ?></h1>
			<?php if ( ! empty( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Prices saved. The calculator uses them right away.', 'kipora' ); ?></p></div>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Price of a plot service = price × plot coefficient (if "× plot size" is on). Cemeteries in Harju County add distance × price per km. "Hidden" rows disappear from the calculator but stay in old orders.', 'kipora' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kipora_tariffs">
				<?php wp_nonce_field( 'kipora_tariffs' ); ?>

				<?php foreach ( self::sections() as $path => [ $title, $columns ] ) : ?>
					<?php
					[ $group, $list ] = explode( '.', $path );
					$rows             = $tree[ $group ][ $list ] ?? [];
					$name             = "tariffs[{$group}][{$list}]";
					?>
					<h2><?php echo esc_html( $title ); ?></h2>
					<table class="widefat kp-admin-table" data-kp-table>
						<thead>
							<tr>
								<?php foreach ( $columns as $col_label ) : ?>
									<th><?php echo esc_html( $col_label ); ?></th>
								<?php endforeach; ?>
								<th><?php esc_html_e( 'Hidden', 'kipora' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $i => $row ) : ?>
								<?php self::row( $name . '[' . $i . ']', $columns, $row ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
					<template data-kp-template><?php self::row( $name . '[__i__]', $columns, [] ); ?></template>
					<p><button type="button" class="button" data-kp-add><?php esc_html_e( 'Add row', 'kipora' ); ?></button></p>

					<?php if ( 'grave.cemeteries' === $path ) : ?>
						<p>
							<label><?php esc_html_e( 'Harju County: price per km, €', 'kipora' ); ?>
								<input type="text" inputmode="decimal" name="tariffs[grave][km_price]" value="<?php echo esc_attr( self::euros( (int) ( $tree['grave']['km_price'] ?? 0 ) ) ); ?>" class="small-text">
							</label>
						</p>
					<?php endif; ?>
				<?php endforeach; ?>

				<?php submit_button( __( 'Save prices', 'kipora' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function row( string $name, array $columns, array $row ): void {
		$id = (string) ( $row['id'] ?? '' );
		echo '<tr>';
		foreach ( array_keys( $columns ) as $key ) {
			echo '<td>';
			if ( str_starts_with( $key, 'label.' ) ) {
				$lang = substr( $key, 6 );
				printf( '<input type="text" name="%s[label][%s]" value="%s" class="widefat">', esc_attr( $name ), esc_attr( $lang ), esc_attr( $row['label'][ $lang ] ?? '' ) );
			} elseif ( in_array( $key, [ 'per_size', 'extras' ], true ) ) {
				printf( '<input type="checkbox" name="%s[%s]" value="1" %s>', esc_attr( $name ), esc_attr( $key ), checked( ! empty( $row[ $key ] ), true, false ) );
			} elseif ( 'price' === $key ) {
				printf( '<input type="text" inputmode="decimal" name="%s[price]" value="%s" class="small-text">', esc_attr( $name ), esc_attr( isset( $row['price'] ) ? self::euros( (int) $row['price'] ) : '' ) );
			} elseif ( 'zone' === $key ) {
				printf(
					'<select name="%1$s[zone]"><option value="tallinn" %2$s>%3$s</option><option value="harjumaa" %4$s>%5$s</option></select>',
					esc_attr( $name ),
					selected( $row['zone'] ?? 'tallinn', 'tallinn', false ),
					esc_html__( 'Tallinn', 'kipora' ),
					selected( $row['zone'] ?? '', 'harjumaa', false ),
					esc_html__( 'Harju County', 'kipora' )
				);
			} else {
				printf( '<input type="text" name="%s[%s]" value="%s" class="%s">', esc_attr( $name ), esc_attr( $key ), esc_attr( (string) ( $row[ $key ] ?? '' ) ), 'name' === $key ? 'widefat' : 'small-text' );
			}
			echo '</td>';
		}
		printf( '<td><input type="checkbox" name="%s[hidden]" value="1" %s></td>', esc_attr( $name ), checked( ! empty( $row['hidden'] ), true, false ) );
		printf( '<td><input type="hidden" name="%s[id]" value="%s" data-kp-id><button type="button" class="button-link kp-admin-remove" data-kp-remove>%s</button></td>', esc_attr( $name ), esc_attr( $id ), esc_html__( 'Remove', 'kipora' ) );
		echo '</tr>';
	}

	private static function euros( int $cents ): string {
		return 0 === $cents % 100 ? (string) intdiv( $cents, 100 ) : number_format( $cents / 100, 2, ',', '' );
	}

	public static function save(): void {
		check_admin_referer( 'kipora_tariffs' );
		if ( ! current_user_can( 'manage_kipora' ) ) {
			wp_die( '', 403 );
		}
		TariffStore::save( (array) wp_unslash( $_POST['tariffs'] ?? [] ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&saved=1' ) );
		exit;
	}
}
