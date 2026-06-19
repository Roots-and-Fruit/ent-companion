<?php
/**
 * FluentSnippets management abilities for agent-defined handlers.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Snippets_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-snippets';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Snippets', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Manage FluentSnippets that register custom agent abilities.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/snippets-list' )
				->label( __( 'List FluentSnippets', 'rootsandfruit-abilities' ) )
				->description( __( 'Lists rf-ability FluentSnippets (draft and published).', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippets_list_input() )
				->output( RF_Schemas::snippets_list_output() )
				->execute( array( RF_Fluent_Snippets::class, 'list_snippets' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/snippets-get' )
				->label( __( 'Get FluentSnippet', 'rootsandfruit-abilities' ) )
				->description( __( 'Returns metadata and PHP code for one rf-ability snippet.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippet_file_input() )
				->output( RF_Schemas::snippet_detail_output() )
				->execute( array( RF_Fluent_Snippets::class, 'get_snippet' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/snippets-create' )
				->label( __( 'Create FluentSnippet', 'rootsandfruit-abilities' ) )
				->description( __( 'Creates a draft PHP snippet tagged rf-ability for custom ability registration.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippet_create_input() )
				->output( RF_Schemas::snippet_mutation_output() )
				->execute( array( RF_Fluent_Snippets::class, 'create_snippet' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/snippets-update' )
				->label( __( 'Update FluentSnippet', 'rootsandfruit-abilities' ) )
				->description( __( 'Updates an existing rf-ability snippet (metadata and/or PHP code).', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippet_update_input() )
				->output( RF_Schemas::snippet_mutation_output() )
				->execute( array( RF_Fluent_Snippets::class, 'update_snippet' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/snippets-activate' )
				->label( __( 'Activate FluentSnippet', 'rootsandfruit-abilities' ) )
				->description( __( 'Publishes an rf-ability snippet so its registered abilities load on the site.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippet_file_input() )
				->output( RF_Schemas::snippet_status_output() )
				->execute( array( RF_Fluent_Snippets::class, 'activate_snippet' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/snippets-deactivate' )
				->label( __( 'Deactivate FluentSnippet', 'rootsandfruit-abilities' ) )
				->description( __( 'Sets an rf-ability snippet to draft so its abilities stop registering.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippet_file_input() )
				->output( RF_Schemas::snippet_status_output() )
				->execute( array( RF_Fluent_Snippets::class, 'deactivate_snippet' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/snippets-verify' )
				->label( __( 'Verify FluentSnippet runtime', 'rootsandfruit-abilities' ) )
				->description( __( 'Loopback load check after update/activate; returns ok and error in one call.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::snippet_verify_input() )
				->output( RF_Schemas::snippet_verify_output() )
				->execute( array( RF_Fluent_Snippets::class, 'verify_snippet' ) )
				->permission( array( RF_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
		);
	}
}
