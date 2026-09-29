<?php
/**
 * Unit tests for the pure logic of kipora-core (no WordPress, no database).
 * Run: php tests/run.php
 */

declare(strict_types=1);

// Repo layout locally, wp-content/database/tests on the test server.
$root = is_dir( dirname( __DIR__ ) . '/wp-content' )
	? dirname( __DIR__ ) . '/wp-content/plugins/kipora-core/src/'
	: dirname( __DIR__, 2 ) . '/plugins/kipora-core/src/';
require $root . 'Pricing.php';
require $root . 'IdCode.php';
require $root . 'Jwt.php';
require $root . 'Auth/VerificationCode.php';

use Kipora\Auth\VerificationCode;
use Kipora\IdCode;
use Kipora\Jwt;
use Kipora\Pricing;

$failed = 0;
$passed = 0;
function check( string $name, $expected, $actual ): void {
	global $failed, $passed;
	if ( $expected === $actual ) {
		$passed++;
		return;
	}
	$failed++;
	echo "FAIL {$name}\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n";
}

// Tariff fixture independent of defaults, so changing defaults does not break tests.
$t = [
	'grave' => [
		'packages'   => [
			[ 'id' => 'cleaning', 'label' => [ 'et' => 'Koristus', 'ru' => 'Уборка', 'en' => '' ], 'price' => 4500, 'per_size' => true, 'extras' => true ],
			[ 'id' => 'flowers', 'label' => [ 'et' => 'Lilled' ], 'price' => 3500, 'per_size' => false, 'extras' => false ],
			[ 'id' => 'old', 'label' => [ 'et' => 'Vana' ], 'price' => 100, 'hidden' => true ],
		],
		'sizes'      => [
			[ 'id' => 'single', 'label' => [ 'et' => 'Üksik' ], 'coef' => 1 ],
			[ 'id' => 'double', 'label' => [ 'et' => 'Kaksik' ], 'coef' => 1.5 ],
		],
		'extras'     => [
			[ 'id' => 'fence', 'label' => [ 'et' => 'Aed' ], 'price' => 6000, 'per_size' => true ],
			[ 'id' => 'stone', 'label' => [ 'et' => 'Kivi' ], 'price' => 4000, 'per_size' => false ],
		],
		'cemeteries' => [
			[ 'id' => 'metsa', 'name' => 'Metsakalmistu', 'zone' => 'tallinn', 'km' => 0 ],
			[ 'id' => 'keila', 'name' => 'Keila', 'zone' => 'harjumaa', 'km' => 28 ],
		],
		'km_price'   => 80,
	],
	'pet'   => [
		'services' => [
			[ 'id' => 'cremation', 'label' => [ 'et' => 'Tuhastamine' ], 'price' => 15000 ],
			[ 'id' => 'transport', 'label' => [ 'et' => 'Transport' ], 'price' => 4000 ],
		],
		'urns'     => [
			[ 'id' => 'none', 'label' => [ 'et' => 'Ilma' ], 'price' => 0 ],
			[ 'id' => 'wood', 'label' => [ 'et' => 'Puit' ], 'price' => 5500 ],
		],
		'options'  => [
			[ 'id' => 'engraving', 'label' => [ 'et' => 'Graveering' ], 'price' => 2500 ],
		],
	],
];

// Grave: double plot, both extras, Harjumaa 28 km.
// 4500*1.5 = 6750; fence 6000*1.5 = 9000; stone 4000; 28*80 = 2240 → 21990.
$r = Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'cleaning', 'size' => 'double', 'extras' => [ 'fence', 'stone', 'fence' ], 'cemetery' => 'keila' ] );
check( 'grave valid', true, $r['valid'] );
check( 'grave total', 21990, $r['total'] );
check( 'grave lines', 4, count( $r['lines'] ) );
check( 'grave ru label', 'Уборка · Kaksik', Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'cleaning', 'size' => 'double', 'cemetery' => 'metsa' ], 'ru' )['lines'][0]['label'] );
check( 'empty en label falls back to et', 'Koristus · Üksik', Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'cleaning', 'size' => 'single', 'cemetery' => 'metsa' ], 'en' )['lines'][0]['label'] );

// Tallinn: no distance line.
$r = Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'cleaning', 'size' => 'single', 'cemetery' => 'metsa' ] );
check( 'tallinn total', 4500, $r['total'] );

// Flowers: size does not change the price, extras are not allowed.
$r = Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'flowers', 'size' => 'double', 'cemetery' => 'metsa' ] );
check( 'flowers total', 3500, $r['total'] );
$r = Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'flowers', 'size' => 'single', 'extras' => [ 'stone' ], 'cemetery' => 'metsa' ] );
check( 'flowers with extras invalid', [ 'extras' ], $r['errors'] );

// Invalid input.
check( 'hidden package rejected', [ 'package' ], Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'old', 'size' => 'single', 'cemetery' => 'metsa' ] )['errors'] );
check( 'unknown cemetery', [ 'cemetery' ], Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'cleaning', 'size' => 'single', 'cemetery' => 'x' ] )['errors'] );
check( 'invalid total is zero', 0, Pricing::calculate( $t, [ 'direction' => 'grave', 'package' => 'cleaning' ] )['total'] );
check( 'no direction', false, Pricing::calculate( $t, [] )['valid'] );

// Pet: cremation + wooden urn + engraving = 15000 + 5500 + 2500.
$r = Pricing::calculate( $t, [ 'direction' => 'pet', 'services' => [ 'cremation' ], 'urn' => 'wood', 'options' => [ 'engraving' ] ] );
check( 'pet total', 23000, $r['total'] );
$r = Pricing::calculate( $t, [ 'direction' => 'pet', 'services' => [ 'cremation', 'transport' ], 'urn' => 'none' ] );
check( 'pet no urn line', 2, count( $r['lines'] ) );
check( 'pet no services', [ 'services' ], Pricing::calculate( $t, [ 'direction' => 'pet', 'services' => [] ] )['errors'] );

check( 'format whole', "45\u{00A0}€", Pricing::format( 4500 ) );
check( 'format cents', "219,90\u{00A0}€", Pricing::format( 21990 ) );
check( 'format thousands', "1\u{00A0}200,05\u{00A0}€", Pricing::format( 120005 ) );

// Isikukood.
check( 'idcode SK demo 1', true, IdCode::is_valid( '30303039914' ) );
check( 'idcode SK demo 2', true, IdCode::is_valid( '60001017869' ) );
check( 'idcode bad checksum', false, IdCode::is_valid( '30303039915' ) );
check( 'idcode bad month', false, IdCode::is_valid( '30313039914' ) );
check( 'idcode short', false, IdCode::is_valid( '3030303991' ) );
check( 'idcode mask', '3030303xxxx', IdCode::mask( '30303039914' ) );

// JWT.
$token = Jwt::encode( [ 'a' => 1, 'exp' => time() + 60 ], 'secret' );
check( 'jwt roundtrip', 1, Jwt::decode( $token, 'secret' )['a'] ?? null );
check( 'jwt wrong secret', null, Jwt::decode( $token, 'other' ) );
check( 'jwt expired', null, Jwt::decode( Jwt::encode( [ 'exp' => time() - 3600 ], 'secret' ), 'secret' ) );
$parts    = explode( '.', $token );
$parts[1] = rtrim( strtr( base64_encode( '{"a":2}' ), '+/', '-_' ), '=' );
check( 'jwt tampered', null, Jwt::decode( implode( '.', $parts ), 'secret' ) );

// Verification codes.
// Smart-ID: last 2 bytes of SHA-256(hash) as integer mod 10000, zero-padded.
$h = hash( 'sha512', 'kipora', true );
$d = hash( 'sha256', $h, true );
check( 'smart-id code', str_pad( (string) ( unpack( 'n', substr( $d, -2 ) )[1] % 10000 ), 4, '0', STR_PAD_LEFT ), VerificationCode::smart_id( $h ) );
check( 'smart-id code length', 4, strlen( VerificationCode::smart_id( $h ) ) );
// Mobile-ID: 6 high bits of the first byte + 7 low bits of the last byte.
$h = hash( 'sha256', 'kipora', true );
$expected = str_pad( (string) ( ( ( ord( $h[0] ) & 0xFC ) << 5 ) | ( ord( $h[31] ) & 0x7F ) ), 4, '0', STR_PAD_LEFT );
check( 'mobile-id code', $expected, VerificationCode::mobile_id( $h ) );

echo "\n{$passed} passed, {$failed} failed\n";
exit( $failed ? 1 : 0 );
