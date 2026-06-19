<?php
/**
 * Site health / diagnostic abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Health_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-site';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Site', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Read-only site diagnostics for Roots & Fruit agents.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/ping' )
				->label( __( 'Ping', 'rootsandfruit-abilities' ) )
				->description( __( 'Returns plugin health and version. Use to verify MCP connectivity.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->output( RF_Schemas::ping_output() )
				->execute( array( self::class, 'ping' ) )
				->permission( array( RF_Permissions::class, 'can_read' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public static function ping( array $input = array() ): array {
		return array(
			'ok'               => true,
			'plugin_version'   => RF_ABILITIES_VERSION,
			'block_mcp_active' => RF_Block_Mcp::is_available(),
		);
	}
}
