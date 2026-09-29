<?php
/**
 * Plugin Name: KIPORA Core
 * Description: Kalkulaator, tellimused, Montonio maksed, Smart-ID / Mobiil-ID sisselogimine ja mälestuskaardid.
 * Version:     0.1.0
 * Requires PHP: 8.1
 * Author:      Crazy Studio
 * Text Domain: kipora
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'KIPORA_VERSION', '0.1.0' );
define( 'KIPORA_FILE', __FILE__ );
define( 'KIPORA_DIR', plugin_dir_path( __FILE__ ) );
define( 'KIPORA_URL', plugin_dir_url( __FILE__ ) );

// phpseclib for Smart-ID signature checks (RSA-PSS is not available in openssl_verify).
require_once KIPORA_DIR . 'vendor/autoload.php';

spl_autoload_register(
	static function ( string $class ): void {
		if ( ! str_starts_with( $class, 'Kipora\\' ) ) {
			return;
		}
		$path = KIPORA_DIR . 'src/' . str_replace( '\\', '/', substr( $class, 7 ) ) . '.php';
		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

register_activation_hook( __FILE__, [ Kipora\Install::class, 'activate' ] );

add_action( 'plugins_loaded', [ Kipora\Plugin::class, 'boot' ] );
