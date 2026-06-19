<?php
/**
 * MCP annotation presets.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Annotations {

	public static function read_only(): array {
		return array(
			'readonly'    => true,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	public static function write_safe(): array {
		return array(
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => false,
		);
	}

	public static function publish(): array {
		return array(
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => false,
		);
	}
}
