<?php
/**
 * Plugin Name: Ent Companion
 * Plugin URI: https://github.com/Roots-and-Fruit/ent-companion
 * Description: Ent Companion — abilities engine for MCP Adapter (snippets, preview, safe plugin updates).
 * Version: 2.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: Roots & Fruit
 * License: GPL-2.0-or-later
 * Text Domain: ent-companion
 * GitHub Plugin URI: https://github.com/Roots-and-Fruit/ent-companion
 * Primary Branch: main
 * Release Asset: true
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ENT_COMPANION_VERSION', '2.0.0' );
define( 'ENT_COMPANION_FILE', __FILE__ );
define( 'ENT_COMPANION_PATH', plugin_dir_path( __FILE__ ) );
define( 'ENT_COMPANION_URL', plugin_dir_url( __FILE__ ) );
define( 'ENT_COMPANION_PREFIX', 'ent-companion/' );

require_once ENT_COMPANION_PATH . 'includes/class-errors.php';
require_once ENT_COMPANION_PATH . 'includes/class-annotations.php';
require_once ENT_COMPANION_PATH . 'includes/class-schemas.php';
require_once ENT_COMPANION_PATH . 'includes/class-permissions.php';
require_once ENT_COMPANION_PATH . 'includes/class-ability-module.php';
require_once ENT_COMPANION_PATH . 'includes/class-ability-definition.php';
require_once ENT_COMPANION_PATH . 'includes/class-ability-registry.php';
require_once ENT_COMPANION_PATH . 'includes/class-agent-abilities.php';
require_once ENT_COMPANION_PATH . 'includes/class-fluent-snippets.php';
require_once ENT_COMPANION_PATH . 'includes/class-code-snippets.php';
require_once ENT_COMPANION_PATH . 'includes/class-snippets.php';
require_once ENT_COMPANION_PATH . 'includes/class-site-smoke-test.php';
require_once ENT_COMPANION_PATH . 'includes/class-wp-rollback-runner.php';
require_once ENT_COMPANION_PATH . 'includes/class-plugin-updater.php';
require_once ENT_COMPANION_PATH . 'includes/class-plugin-update-safe.php';
require_once ENT_COMPANION_PATH . 'includes/class-plugin.php';
require_once ENT_COMPANION_PATH . 'includes/abilities/class-health-module.php';
require_once ENT_COMPANION_PATH . 'includes/abilities/class-preview-module.php';
require_once ENT_COMPANION_PATH . 'includes/abilities/class-snippets-module.php';
require_once ENT_COMPANION_PATH . 'includes/abilities/class-plugins-module.php';

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'Ent Companion requires WordPress 6.9 or newer (Abilities API).', 'ent-companion' );
					echo '</p></div>';
				}
			);
			return;
		}

		EC_Plugin::instance()->boot();
	}
);
