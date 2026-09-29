<?php
/**
 * Private file storage for memorial archives and photo reports.
 *
 * Files are stored with a random name and a .bin extension outside the
 * media library: some hosts serve .jpg/.pdf directly from nginx and ignore
 * .htaccess, so an original extension would leak the file. Access goes
 * through ?kipora_file=ID with an owner check. On production the folder
 * should live outside the web root: define KIPORA_PRIVATE_DIR.
 */

namespace Kipora;

final class Files {

	public const MAX_BYTES = 15 * 1024 * 1024;

	public const KIND_ARCHIVE = 'archive';
	public const KIND_BEFORE  = 'before';
	public const KIND_AFTER   = 'after';

	private const ALLOWED = [
		'image/jpeg'      => 'jpg',
		'image/png'       => 'png',
		'image/webp'      => 'webp',
		'application/pdf' => 'pdf',
	];

	public static function register(): void {
		add_action( 'init', [ self::class, 'maybe_serve' ], 20 );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'kp_files';
	}

	public static function dir(): string {
		$dir = defined( 'KIPORA_PRIVATE_DIR' ) ? KIPORA_PRIVATE_DIR : WP_CONTENT_DIR . '/kipora-private';
		return rtrim( $dir, '/' );
	}

	public static function protect_storage(): void {
		$dir = self::dir();
		wp_mkdir_p( $dir );
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" );
		}
	}

	public static function accept_types(): string {
		return implode( ',', array_merge( array_keys( self::ALLOWED ), array_map( static fn( $e ) => '.' . $e, self::ALLOWED ), [ '.jpeg' ] ) );
	}

	/**
	 * Stores one uploaded file ($_FILES entry).
	 *
	 * @return int|\WP_Error File id.
	 */
	public static function store( array $upload, int $owner_id, int $memorial_id, int $order_id, string $kind ) {
		if ( UPLOAD_ERR_OK !== ( $upload['error'] ?? UPLOAD_ERR_NO_FILE ) || ! is_uploaded_file( $upload['tmp_name'] ?? '' ) ) {
			return new \WP_Error( 'upload', __( 'The file did not upload. Try again.', 'kipora' ) );
		}
		if ( (int) $upload['size'] > self::MAX_BYTES ) {
			return new \WP_Error( 'size', sprintf( __( 'The file is larger than %d MB.', 'kipora' ), self::MAX_BYTES / 1024 / 1024 ) );
		}

		// Trust the content, not the name the browser sent.
		$mime = (string) ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $upload['tmp_name'] );
		if ( ! isset( self::ALLOWED[ $mime ] ) ) {
			return new \WP_Error( 'type', __( 'Allowed formats: JPG, PNG, WEBP and PDF.', 'kipora' ) );
		}

		self::protect_storage();
		$sub  = gmdate( 'Y/m' );
		$base = self::dir() . '/' . $sub;
		wp_mkdir_p( $base );

		$name = bin2hex( random_bytes( 16 ) );
		$path = $sub . '/' . $name . '.bin';
		if ( ! move_uploaded_file( $upload['tmp_name'], self::dir() . '/' . $path ) ) {
			return new \WP_Error( 'upload', __( 'The file did not upload. Try again.', 'kipora' ) );
		}

		$preview = str_starts_with( $mime, 'image/' ) ? self::make_preview( self::dir() . '/' . $path, self::ALLOWED[ $mime ], $sub . '/' . $name . '-p.bin' ) : '';

		global $wpdb;
		$wpdb->insert(
			self::table(),
			[
				'owner_id'    => $owner_id,
				'memorial_id' => $memorial_id,
				'order_id'    => $order_id,
				'kind'        => $kind,
				'path'        => $path,
				'preview'     => $preview,
				'name'        => mb_substr( sanitize_file_name( wp_basename( (string) $upload['name'] ) ), 0, 190 ),
				'mime'        => $mime,
				'size'        => (int) $upload['size'],
				'created_at'  => current_time( 'mysql', true ),
			]
		);
		return (int) $wpdb->insert_id;
	}

	/** Phone photos are 3–8 MB; the account shows a 1200 px copy. */
	private static function make_preview( string $source, string $ext, string $target_rel ): string {
		// The image editor needs a real extension, our stored file has .bin.
		$tmp = get_temp_dir() . 'kp-' . bin2hex( random_bytes( 8 ) ) . '.' . $ext;
		copy( $source, $tmp );

		$editor = wp_get_image_editor( $tmp );
		$result = '';
		if ( ! is_wp_error( $editor ) ) {
			$editor->resize( 1200, 1200, false );
			$editor->set_quality( 80 );
			$saved = $editor->save( $tmp . '-out.jpg', 'image/jpeg' );
			if ( ! is_wp_error( $saved ) ) {
				// copy, not rename: /tmp is often another filesystem and rename() fails across devices.
				if ( copy( $saved['path'], self::dir() . '/' . $target_rel ) ) {
					$result = $target_rel;
				}
				wp_delete_file( $saved['path'] );
			}
		}
		wp_delete_file( $tmp );
		return $result;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ), ARRAY_A );
		return $row ?: null;
	}

	/** @return array<int,array> */
	public static function for_memorial( int $memorial_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE memorial_id = %d ORDER BY id DESC', $memorial_id ), ARRAY_A );
	}

	/** @return array<int,array> */
	public static function for_order( int $order_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE order_id = %d ORDER BY id ASC', $order_id ), ARRAY_A );
	}

	public static function can_access( array $file, int $user_id ): bool {
		if ( user_can( $user_id, 'manage_kipora' ) ) {
			return true;
		}
		if ( ! $user_id ) {
			return false;
		}
		if ( (int) $file['memorial_id'] && Memorials::owned_by( (int) $file['memorial_id'], $user_id ) ) {
			return true;
		}
		$order = (int) $file['order_id'] ? Orders::get( (int) $file['order_id'] ) : null;
		return $order && $order['user_id'] === $user_id;
	}

	public static function delete( int $id ): void {
		$file = self::get( $id );
		if ( ! $file ) {
			return;
		}
		foreach ( [ $file['path'], $file['preview'] ] as $rel ) {
			if ( $rel && is_file( self::dir() . '/' . $rel ) ) {
				wp_delete_file( self::dir() . '/' . $rel );
			}
		}
		global $wpdb;
		$wpdb->delete( self::table(), [ 'id' => $id ] );
	}

	public static function url( int $id, bool $preview = false, bool $download = false ): string {
		$args = [ 'kipora_file' => $id ];
		if ( $preview ) {
			$args['v'] = 'p';
		}
		if ( $download ) {
			$args['dl'] = 1;
		}
		return add_query_arg( $args, home_url( '/' ) );
	}

	public static function is_image( array $file ): bool {
		return str_starts_with( (string) $file['mime'], 'image/' );
	}

	public static function maybe_serve(): void {
		if ( empty( $_GET['kipora_file'] ) ) {
			return;
		}
		$file = self::get( absint( $_GET['kipora_file'] ) );
		if ( ! $file || ! self::can_access( $file, get_current_user_id() ) ) {
			status_header( 404 );
			exit;
		}

		$use_preview = ! empty( $_GET['v'] ) && $file['preview'];
		$path        = self::dir() . '/' . ( $use_preview ? $file['preview'] : $file['path'] );
		if ( ! is_file( $path ) ) {
			status_header( 404 );
			exit;
		}

		nocache_headers();
		header( 'Content-Type: ' . ( $use_preview ? 'image/jpeg' : $file['mime'] ) );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Disposition: ' . ( empty( $_GET['dl'] ) ? 'inline' : 'attachment' ) . '; filename="' . rawurlencode( $file['name'] ) . '"' );
		readfile( $path );
		exit;
	}
}
