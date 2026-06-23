<?php
/**
 * Boots the ability registry and modules.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Plugin {

	private static ?EC_Plugin $instance = null;

	private EC_Ability_Registry $registry;

	private function __construct() {
		$this->registry = new EC_Ability_Registry();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		add_action( 'wp_abilities_api_categories_init', array( EC_Agent_Abilities::class, 'register_category' ) );

		$this->registry->add_module( new EC_Health_Module() );
		if ( class_exists( 'DS_Public_Post_Preview' ) ) {
			$this->registry->add_module( new EC_Preview_Module() );
		}
		if ( EC_Snippets::is_available() ) {
			$this->registry->add_module( new EC_Snippets_Module() );
		}
		if ( EC_Wp_Rollback::is_available() ) {
			$this->registry->add_module( new EC_Plugins_Module() );
		}
		$this->registry->boot();
	}

	public function registry(): EC_Ability_Registry {
		return $this->registry;
	}
}
