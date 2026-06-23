#!/usr/bin/env php
<?php
/**
 * CLI validation for EC_Ability_Definition (gate 1.2).
 * Run: php ../abilities/bin/validate-definition-builder.php
 */

declare(strict_types=1);

define( 'ENT_COMPANION_PREFIX', 'ent-companion/' );
define( 'ABSPATH', __DIR__ );

require dirname( __DIR__ ) . '/includes/class-ability-definition.php';

$failures = 0;

function expect_exception( callable $fn, string $label ): void {
	global $failures;
	try {
		$fn();
		echo "FAIL: expected exception for {$label}\n";
		++$failures;
	} catch ( InvalidArgumentException $e ) {
		echo "PASS: {$label}\n";
	}
}

expect_exception(
	static function (): void {
		EC_Ability_Definition::make( 'ent-companion/test/x' )
			->label( 'X' )
			->description( 'Y' )
			->category( 'cat' )
			->execute( static fn() => array() )
			->permission( static fn() => true )
			->build();
	},
	'extra slash in name'
);

expect_exception(
	static function (): void {
		EC_Ability_Definition::make( 'wrong/prefix' )
			->label( 'X' )
			->description( 'Y' )
			->category( 'cat' )
			->execute( static fn() => array() )
			->permission( static fn() => true )
			->build();
	},
	'wrong prefix'
);

expect_exception(
	static function (): void {
		EC_Ability_Definition::make( 'ent-companion/test/x' )
			->label( 'X' )
			->description( '' )
			->category( 'cat' )
			->execute( static fn() => array() )
			->permission( static fn() => true )
			->build();
	},
	'empty description'
);

$built = EC_Ability_Definition::make( 'ent-companion/test-valid' )
	->label( 'Valid' )
	->description( 'Valid ability' )
	->category( 'ent-companion-site' )
	->execute( static fn() => array( 'ok' => true ) )
	->permission( static fn() => true )
	->mcp_public( false )
	->build();

if ( 'ent-companion/test-valid' === $built['name'] ) {
	echo "PASS: valid build\n";
} else {
	echo "FAIL: valid build name mismatch\n";
	++$failures;
}

exit( $failures > 0 ? 1 : 0 );
