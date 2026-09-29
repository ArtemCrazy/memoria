<?php

namespace Kipora;

final class Plugin {

	public static function boot(): void {
		add_action( 'init', [ self::class, 'init' ], 5 );

		Install::maybe_upgrade();
		Memorials::register();
		Orders::register();
		Files::register();
		Rest::register();
		Clients::register();
		Front\Shortcodes::register();
		Front\Forms::register();
		Mailer::register();

		if ( is_admin() ) {
			Admin\Menu::register();
		}
	}

	public static function init(): void {
		load_plugin_textdomain( 'kipora', false, dirname( plugin_basename( KIPORA_FILE ) ) . '/languages' );
	}
}
