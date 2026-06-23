<?php
/**
 * FluentSnippets management abilities for agent-defined handlers.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Snippets_Module implements EC_Ability_Module {

	public function category_slug(): string {
		return 'ent-companion-snippets';
	}

	public function category_label(): string {
		return __( 'Ent Companion — Snippets', 'ent-companion' );
	}

	public function category_description(): string {
		return __( 'Manage FluentSnippets that register custom agent abilities.', 'ent-companion' );
	}

	public function definitions(): array {
		return array(
			EC_Ability_Definition::make( 'ent-companion/snippets-list' )
				->label( __( 'List FluentSnippets', 'ent-companion' ) )
				->description( __( 'Lists ec-ability FluentSnippets (draft and published).', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippets_list_input() )
				->output( EC_Schemas::snippets_list_output() )
				->execute( array( EC_Snippets::class, 'list_snippets' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::read_only() )
				->build(),
			EC_Ability_Definition::make( 'ent-companion/snippets-get' )
				->label( __( 'Get FluentSnippet', 'ent-companion' ) )
				->description( __( 'Returns metadata and PHP code for one ec-ability snippet.', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippet_file_input() )
				->output( EC_Schemas::snippet_detail_output() )
				->execute( array( EC_Snippets::class, 'get_snippet' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::read_only() )
				->build(),
			EC_Ability_Definition::make( 'ent-companion/snippets-create' )
				->label( __( 'Create FluentSnippet', 'ent-companion' ) )
				->description( __( 'Creates a draft PHP snippet tagged ec-ability for custom ability registration.', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippet_create_input() )
				->output( EC_Schemas::snippet_mutation_output() )
				->execute( array( EC_Snippets::class, 'create_snippet' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::write_safe() )
				->build(),
			EC_Ability_Definition::make( 'ent-companion/snippets-update' )
				->label( __( 'Update FluentSnippet', 'ent-companion' ) )
				->description( __( 'Updates an existing ec-ability snippet (metadata and/or PHP code).', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippet_update_input() )
				->output( EC_Schemas::snippet_mutation_output() )
				->execute( array( EC_Snippets::class, 'update_snippet' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::write_safe() )
				->build(),
			EC_Ability_Definition::make( 'ent-companion/snippets-activate' )
				->label( __( 'Activate FluentSnippet', 'ent-companion' ) )
				->description( __( 'Publishes an ec-ability snippet so its registered abilities load on the site.', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippet_file_input() )
				->output( EC_Schemas::snippet_status_output() )
				->execute( array( EC_Snippets::class, 'activate_snippet' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::write_safe() )
				->build(),
			EC_Ability_Definition::make( 'ent-companion/snippets-deactivate' )
				->label( __( 'Deactivate FluentSnippet', 'ent-companion' ) )
				->description( __( 'Sets an ec-ability snippet to draft so its abilities stop registering.', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippet_file_input() )
				->output( EC_Schemas::snippet_status_output() )
				->execute( array( EC_Snippets::class, 'deactivate_snippet' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::write_safe() )
				->build(),
			EC_Ability_Definition::make( 'ent-companion/snippets-verify' )
				->label( __( 'Verify FluentSnippet runtime', 'ent-companion' ) )
				->description( __( 'Loopback load check after update/activate; returns ok and error in one call.', 'ent-companion' ) )
				->category( $this->category_slug() )
				->input( EC_Schemas::snippet_verify_input() )
				->output( EC_Schemas::snippet_verify_output() )
				->execute( array( EC_Snippets::class, 'verify_snippet' ) )
				->permission( array( EC_Permissions::class, 'can_manage_snippets' ) )
				->mcp_public( true )
				->annotations( EC_Annotations::read_only() )
				->build(),
		);
	}
}
