<?php
/**
 * Verify release version parity.
 *
 * Usage: php scripts/check-version.php [expected-version]
 *
 * @package TaxProofCouponsForWooCommerce
 */

$repository_root = dirname( __DIR__ );
$plugin_file     = file_get_contents( $repository_root . '/tax-proof-coupons-plugin.php' );
$plugin_class    = file_get_contents( $repository_root . '/includes/class-plugin.php' );
$readme          = file_get_contents( $repository_root . '/readme.txt' );
$expected        = isset( $argv[1] ) ? ltrim( $argv[1], 'v' ) : null;

$patterns = array(
	'plugin header'   => '/^ \* Version:\s+([^\s]+)$/m',
	'Plugin::VERSION' => "/VERSION\s*=\s*'([^']+)'/",
	'readme stable'   => '/^Stable tag:\s+([^\s]+)$/m',
);

$sources = array(
	'plugin header'   => $plugin_file,
	'Plugin::VERSION' => $plugin_class,
	'readme stable'   => $readme,
);

$versions = array();

foreach ( $patterns as $label => $pattern ) {
	if ( ! preg_match( $pattern, $sources[ $label ], $matches ) ) {
		fwrite( STDERR, sprintf( "Could not find %s.\n", $label ) );
		exit( 1 );
	}

	$versions[ $label ] = $matches[1];
}

$unique_versions = array_unique( array_values( $versions ) );

if ( 1 !== count( $unique_versions ) ) {
	fwrite( STDERR, 'Version mismatch: ' . json_encode( $versions ) . "\n" );
	exit( 1 );
}

$version = reset( $unique_versions );

if ( null !== $expected && $version !== $expected ) {
	fwrite( STDERR, sprintf( "Expected %s, found %s.\n", $expected, $version ) );
	exit( 1 );
}

fwrite( STDOUT, $version . "\n" );
