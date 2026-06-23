<?php
/**
 * Plugin update and rollback abilities.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Plugins_Module implements EC_Ability_Module {

	public function category_slug(): string {
		return 'ent-companion-plugins';
	}

	public function category_label(): string {
		return __( 'Ent Companion — Plugins', 'ent-companion' );
	}

	public function category_description(): string {
		return __( 'Safe WordPress.org plugin updates with smoke testing and rollback.', 'ent-companion' );
	}

	public function definitions(): array {
		return array(
			EC_Ability_Definition::make( 'ent-companion/plugin-update-safe' )
				->label( __( 'Safe plugin update', 'ent-companion' ) )
				->description(
					__(
						'Updates a WordPress.org plugin, runs a homepage smoke test, and rolls back automatically on failure. Captures pre-update version internally.',
						'ent-companion'
					)
				)
				->category( $this->category_slug() )
				->input( EC_Schemas::plugin_update_safe_input() )
				->output( EC_Schemas::plugin_update_safe_output() )
				->execute( array( EC_Plugin_Update_Safe::class, 'run' ) )
				->permission( array( EC_Permissions::class, 'can_update_plugins' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::write_safe() )
				->build(),
		);
	}
}
