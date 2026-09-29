<?php
/**
 * Live check against SK DEMO services (test accounts auto-confirm).
 * Run on a machine with network access: php tests/sk-demo.php [discover]
 *
 * "discover" prints issuer certificates (from AIA) of demo certificates,
 * so they can be reviewed and saved into kipora-core/certs/demo/.
 */

declare(strict_types=1);

$plugin = is_dir( dirname( __DIR__ ) . '/wp-content' )
	? dirname( __DIR__ ) . '/wp-content/plugins/kipora-core/'
	: dirname( __DIR__, 2 ) . '/plugins/kipora-core/';

require $plugin . 'vendor/autoload.php';
spl_autoload_register(
	static function ( string $class ) use ( $plugin ): void {
		if ( str_starts_with( $class, 'Kipora\\' ) ) {
			require $plugin . 'src/' . str_replace( '\\', '/', substr( $class, 7 ) ) . '.php';
		}
	}
);

use Kipora\Auth\AuthException;
use Kipora\Auth\Certificate;
use Kipora\Auth\MobileId;
use Kipora\Auth\SkClient;
use Kipora\Auth\SmartId;

$discover = in_array( 'discover', $argv, true );
$trusted  = Certificate::load_dir( $plugin . 'certs/demo' );
echo 'trusted CA certificates: ' . count( $trusted ) . "\n";

function describe_issuer( string $der_b64 ): void {
	$pem  = Certificate::pem_from_der_b64( $der_b64 );
	$info = openssl_x509_parse( $pem );
	echo '  subject: ' . ( $info['subject']['CN'] ?? '' ) . ' / ' . ( $info['subject']['serialNumber'] ?? '' ) . "\n";
	echo '  issuer:  ' . ( $info['issuer']['CN'] ?? '' ) . "\n";
	echo '  AIA:     ' . str_replace( "\n", ' | ', $info['extensions']['authorityInfoAccess'] ?? '' ) . "\n";
}

$failed = 0;
function expect( string $name, callable $fn, ?string $reason = null ): void {
	global $failed;
	try {
		$identity = $fn();
		if ( null !== $reason ) {
			$failed++;
			echo "FAIL {$name}: expected {$reason}, got success\n";
			return;
		}
		echo "OK   {$name}: " . json_encode( $identity, JSON_UNESCAPED_UNICODE ) . "\n";
	} catch ( AuthException $e ) {
		if ( $reason === $e->reason ) {
			echo "OK   {$name}: {$e->reason}\n";
			return;
		}
		$failed++;
		echo "FAIL {$name}: {$e->reason} — {$e->getMessage()}\n";
	}
}

function wait_for( callable $poll ): ?array {
	for ( $i = 0; $i < 20; $i++ ) {
		$identity = $poll();
		if ( null !== $identity ) {
			return $identity;
		}
	}
	throw new AuthException( 'timeout', 'still running after 20 polls' );
}

$sid = new SmartId(
	[
		'base_url' => 'https://sid.demo.sk.ee/smart-id-rp/v3',
		'rp_uuid'  => '00000000-0000-4000-8000-000000000000',
		'rp_name'  => 'DEMO',
		'trusted'  => $trusted,
	]
);
$mid = new MobileId(
	[
		'base_url' => 'https://tsp.demo.sk.ee/mid-api',
		'rp_uuid'  => '00000000-0000-0000-0000-000000000000',
		'rp_name'  => 'DEMO',
		'trusted'  => $trusted,
	]
);

if ( $discover ) {
	$state = $sid->start( '40504040001', 'et', 'KIPORA' );
	echo "Smart-ID code {$state['code']}\n";
	$c = new SkClient( 'https://sid.demo.sk.ee/smart-id-rp/v3' );
	do {
		$r = $c->get( 'session/' . $state['session'] . '?timeoutMs=5000', 20 );
	} while ( 'RUNNING' === ( $r['body']['state'] ?? '' ) );
	echo 'Smart-ID result: ' . json_encode( $r['body']['result'] ?? null ) . ' flow ' . ( $r['body']['signature']['flowType'] ?? '' ) . "\n";
	describe_issuer( $r['body']['cert']['value'] ?? '' );

	foreach ( [ [ '+37268000769', '60001017869' ], [ '+37200000766', '60001019906' ], [ '+37269930366', '51307149560' ] ] as [ $phone, $code ] ) {
		$state = $mid->start( $phone, $code, 'et', 'KIPORA' );
		$c     = new SkClient( 'https://tsp.demo.sk.ee/mid-api' );
		do {
			$r = $c->get( 'authentication/session/' . $state['session'] . '?timeoutMs=5000', 20 );
		} while ( 'RUNNING' === ( $r['body']['state'] ?? '' ) );
		echo "Mobile-ID {$phone}: " . ( $r['body']['result'] ?? '' ) . ' ' . ( $r['body']['signature']['algorithm'] ?? '' ) . "\n";
		describe_issuer( $r['body']['cert'] ?? '' );
	}
	exit( 0 );
}

expect(
	'Smart-ID OK 40504040001',
	function () use ( $sid ) {
		$state = $sid->start( '40504040001', 'et', 'KIPORA' );
		return wait_for( fn() => $sid->poll( $state, 5000 ) );
	}
);
expect(
	'Smart-ID OK 39901012239',
	function () use ( $sid ) {
		$state = $sid->start( '39901012239', 'ru', 'KIPORA' );
		return wait_for( fn() => $sid->poll( $state, 5000 ) );
	}
);
expect(
	'Smart-ID refused 30403039917',
	function () use ( $sid ) {
		$state = $sid->start( '30403039917', 'et', 'KIPORA' );
		return wait_for( fn() => $sid->poll( $state, 5000 ) );
	},
	'refused'
);
expect(
	'Smart-ID tampered challenge',
	function () use ( $sid ) {
		$state              = $sid->start( '50001029996', 'et', 'KIPORA' );
		$state['challenge'] = base64_encode( random_bytes( 64 ) );
		return wait_for( fn() => $sid->poll( $state, 5000 ) );
	},
	'signature'
);

foreach ( [ [ '+37268000769', '60001017869' ], [ '+37200000766', '60001019906' ], [ '+37269930366', '51307149560' ] ] as [ $phone, $code ] ) {
	expect(
		"Mobile-ID OK {$phone}",
		function () use ( $mid, $phone, $code ) {
			$state = $mid->start( $phone, $code, 'et', 'KIPORA' );
			return wait_for( fn() => $mid->poll( $state, 5000 ) );
		}
	);
}
expect(
	'Mobile-ID tampered random',
	function () use ( $mid ) {
		$state           = $mid->start( '+37268000769', '60001017869', 'et', 'KIPORA' );
		$state['random'] = base64_encode( random_bytes( 32 ) );
		return wait_for( fn() => $mid->poll( $state, 5000 ) );
	},
	'signature'
);
expect(
	'Mobile-ID cancelled',
	function () use ( $mid ) {
		$state = $mid->start( '+37201100266', '60001019950', 'et', 'KIPORA' );
		return wait_for( fn() => $mid->poll( $state, 5000 ) );
	},
	'refused'
);

echo $failed ? "\n{$failed} failed\n" : "\nall passed\n";
exit( $failed ? 1 : 0 );
