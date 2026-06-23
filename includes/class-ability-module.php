<?php
/**
 * Ability module contract.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface EC_Ability_Module {

	/**
	 * Category slug registered via wp_register_ability_category().
	 */
	public function category_slug(): string;

	/**
	 * Human-readable category label.
	 */
	public function category_label(): string;

	/**
	 * Category description.
	 */
	public function category_description(): string;

	/**
	 * Built ability definitions ready for wp_register_ability().
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function definitions(): array;
}
