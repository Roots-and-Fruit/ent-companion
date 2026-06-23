<?php
/**
 * Registers categories and abilities from modules.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Ability_Registry {

	/** @var EC_Ability_Module[] */
	private array $modules = array();

	public function add_module( EC_Ability_Module $module ): void {
		$this->modules[] = $module;
	}

	public function boot(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	public function register_categories(): void {
		$seen = array();

		foreach ( $this->modules as $module ) {
			$slug = $module->category_slug();
			if ( isset( $seen[ $slug ] ) ) {
				continue;
			}

			wp_register_ability_category(
				$slug,
				array(
					'label'       => $module->category_label(),
					'description' => $module->category_description(),
				)
			);

			$seen[ $slug ] = true;
		}
	}

	public function register_abilities(): void {
		foreach ( $this->modules as $module ) {
			foreach ( $module->definitions() as $definition ) {
				wp_register_ability( $definition['name'], $definition['args'] );
			}
		}
	}
}
