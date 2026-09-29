<?php
/**
 * E2E helper: puts throwaway Montonio keys and a non-delivering team email
 * into settings, so the webhook test can sign a token. e2e-reset.php restores.
 * Prints the secret for the test runner.
 */

defined( 'ABSPATH' ) || exit;

update_option( 'kipora_e2e_backup', get_option( Kipora\Settings::OPTION, [] ), false );
$secret = bin2hex( random_bytes( 16 ) );
Kipora\Settings::save(
	[
		'montonio_access_key' => 'e2e-access',
		'montonio_secret_key' => $secret,
		'notify_email'        => 'e2e-team@example.com',
	]
);
echo $secret;
