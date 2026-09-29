<?php
/**
 * Diagnostic: prints a raw Smart-ID DEMO session result and tries payload
 * variants against the signature. Kept for future protocol changes.
 * Run: php tests/sid-debug.php
 */

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

use Kipora\Auth\Certificate;
use Kipora\Auth\SkClient;
use Kipora\Auth\SmartId;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

$base  = 'https://sid.demo.sk.ee/smart-id-rp/v3';
$sid   = new SmartId( [ 'base_url' => $base, 'rp_uuid' => '00000000-0000-4000-8000-000000000000', 'rp_name' => 'DEMO' ] );
$state = $sid->start( '40504040001', 'et', 'KIPORA' );
$c     = new SkClient( $base );
do {
	$r = $c->get( 'session/' . $state['session'] . '?timeoutMs=5000', 20 );
} while ( 'RUNNING' === ( $r['body']['state'] ?? '' ) );

$b = $r['body'];
$s = $b['signature'];
echo json_encode( array_diff_key( $b, [ 'cert' => 1 ] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), "\n";

$key = PublicKeyLoader::load( Certificate::pem_from_der_b64( $b['cert']['value'] ) );
if ( ! $key instanceof RSA\PublicKey ) {
	$key = $key->getPublicKey();
}

$variants = [
	'interactions b64 string' => $state['interactions'],
	'interactions json'       => base64_decode( $state['interactions'] ),
];
foreach ( $variants as $name => $interactions ) {
	$msg = implode( '|', [ 'smart-id', 'ACSP_V2', $s['serverRandom'], $state['challenge'], $s['userChallenge'], base64_encode( 'DEMO' ), '', base64_encode( hash( 'sha256', $interactions, true ) ), $b['interactionTypeUsed'], '', $s['flowType'] ] );
	foreach ( [ 64, 32 ] as $salt ) {
		$ok = $key->withPadding( RSA::SIGNATURE_PSS )->withHash( 'sha512' )->withMGFHash( 'sha512' )->withSaltLength( $salt )->verify( $msg, base64_decode( $s['value'] ) );
		echo "{$name}, salt {$salt}: " . var_export( $ok, true ) . "\n";
	}
}
