<?php
/**
 * Site health / diagnostic abilities.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Health_Module implements EC_Ability_Module {

	public function category_slug(): string {
		return 'ent-companion-site';
	}

	public function category_label(): string {
		return __( 'Ent Companion — Site', 'ent-companion' );
	}

	public function category_description(): string {
		return __( 'Read-only site diagnostics for Ent Companion agents.', 'ent-companion' );
	}

	public function definitions(): array {
		return array(
			EC_Ability_Definition::make( 'ent-companion/ping' )
				->label( __( 'Ping', 'ent-companion' ) )
				->description( __( 'Returns plugin health and version. Use to verify MCP connectivity.', 'ent-companion' ) )
				->category( $this->category_slug() )
				->output( EC_Schemas::ping_output() )
				->execute( array( self::class, 'ping' ) )
				->permission( array( EC_Permissions::class, 'can_read' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::read_only() )
				->build(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public static function ping( array $input = array() ): array {
		return array(
			'ok'                     => true,
			'plugin_version'         => ENT_COMPANION_VERSION,
			'snippet_providers'      => EC_Snippets::available_providers(),
			'fluent_snippets_active' => EC_Fluent_Snippets::is_available(),
			'code_snippets_active'   => EC_Code_Snippets::is_available(),
		);
	}
}
