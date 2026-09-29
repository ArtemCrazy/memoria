<?php

namespace Kipora;

final class Install {

	private const DB_VERSION = 1;

	public static function activate(): void {
		self::tables();
		self::roles();
		Files::protect_storage();
		update_option( 'kipora_db_version', self::DB_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		if ( (int) get_option( 'kipora_db_version' ) < self::DB_VERSION ) {
			self::activate();
		}
	}

	private static function tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = Files::table();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
				memorial_id bigint(20) unsigned NOT NULL DEFAULT 0,
				order_id bigint(20) unsigned NOT NULL DEFAULT 0,
				kind varchar(20) NOT NULL DEFAULT 'archive',
				path varchar(191) NOT NULL DEFAULT '',
				preview varchar(191) NOT NULL DEFAULT '',
				name varchar(191) NOT NULL DEFAULT '',
				mime varchar(100) NOT NULL DEFAULT '',
				size bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY memorial_id (memorial_id),
				KEY order_id (order_id),
				KEY owner_id (owner_id)
			) {$charset};"
		);
	}

	private static function roles(): void {
		add_role( Clients::ROLE, 'KIPORA klient', [ 'read' => true ] );

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'manage_kipora' );
		}
	}
}
