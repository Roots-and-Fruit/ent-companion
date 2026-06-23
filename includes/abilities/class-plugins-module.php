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
		return __( 'Plugin inventory and safe WordPress.org updates with rollback.', 'ent-companion' );
	}

	public function definitions(): array {
		$definitions = array(
			EC_Ability_Definition::make( 'ent-companion/list-plugins' )
				->label( __( 'List plugins', 'ent-companion' ) )
				->description(
					__(
						'Returns installed plugins with slug, version, active state, and pending update metadata.',
						'ent-companion'
					)
				)
				->category( $this->category_slug() )
				->input( EC_Schemas::list_plugins_input() )
				->output( EC_Schemas::list_plugins_output() )
				->execute( array( EC_Plugin_List::class, 'list_plugins' ) )
				->permission( array( EC_Permissions::class, 'can_list_plugins' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::read_only() )
				->build(),
		);

		if ( EC_Wp_Rollback::is_available() ) {
			$definitions[] = EC_Ability_Definition::make( 'ent-companion/plugin-update-safe' )
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
				->build();
		}

		return $definitions;
	}
}
