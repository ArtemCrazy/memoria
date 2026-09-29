<?php
/**
 * E2E helper: restores settings saved by e2e-keys.php and reports the
 * test orders so they can be checked or removed.
 */

defined( 'ABSPATH' ) || exit;

update_option( Kipora\Settings::OPTION, get_option( 'kipora_e2e_backup', [] ), false );
delete_option( 'kipora_e2e_backup' );
echo "settings restored\n";
