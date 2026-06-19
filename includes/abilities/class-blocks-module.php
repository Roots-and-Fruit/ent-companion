<?php
/**
 * Block editor abilities bridged to GravityKit Block MCP.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Blocks_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-blocks';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Blocks', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Block editor read/write via GravityKit Block MCP (requires gk-block-mcp plugin).', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/blocks-get-page' )
				->label( __( 'Get page blocks', 'rootsandfruit-abilities' ) )
				->description( __( 'Returns structured block tree for a post or page. Use for block-level editing instead of rootsandfruit/update-post content.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::blocks_get_page_input() )
				->output( RF_Schemas::blocks_get_page_output() )
				->execute( array( self::class, 'get_page' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/blocks-update' )
				->label( __( 'Update block', 'rootsandfruit-abilities' ) )
				->description( __( 'Updates one block by flat_index or ref. Provide attributes and/or innerHTML.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::blocks_update_input() )
				->output( RF_Schemas::blocks_write_output() )
				->execute( array( self::class, 'update_block' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/blocks-mutate' )
				->label( __( 'Mutate block tree', 'rootsandfruit-abilities' ) )
				->description( __( 'Path- or ref-based structural mutation (update-attrs, replace-block, insert-child, move, etc.).', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::blocks_mutate_input() )
				->output( RF_Schemas::blocks_write_output() )
				->execute( array( self::class, 'mutate' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/blocks-insert' )
				->label( __( 'Insert blocks', 'rootsandfruit-abilities' ) )
				->description( __( 'Inserts one or more blocks at a position (after/before ref or top-level counter).', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::blocks_insert_input() )
				->output( RF_Schemas::blocks_write_output() )
				->execute( array( self::class, 'insert_blocks' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/blocks-create-page' )
				->label( __( 'Create block page', 'rootsandfruit-abilities' ) )
				->description( __( 'Creates a post or page from a structured blocks array. Prefer over create-draft for Gutenberg content.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::blocks_create_page_input() )
				->output( RF_Schemas::blocks_create_page_output() )
				->execute( array( self::class, 'create_page' ) )
				->permission( array( RF_Permissions::class, 'can_create_posts' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/blocks-list-patterns' )
				->label( __( 'List block patterns', 'rootsandfruit-abilities' ) )
				->description( __( 'Lists synced and registered block patterns with preference scoring.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::blocks_list_patterns_input() )
				->output( RF_Schemas::blocks_list_patterns_output() )
				->execute( array( self::class, 'list_patterns' ) )
				->permission( array( RF_Permissions::class, 'can_list_posts' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_page( array $input ) {
		$ready = RF_Block_Mcp::require_available();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$post_id = (int) ( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return RF_Errors::invalid_input( 'post_id is required.' );
		}

		$render       = ! empty( $input['render'] );
		$persist_refs = ! array_key_exists( 'persist_refs', $input ) || ! empty( $input['persist_refs'] );
		$blocks       = RF_Block_Mcp::block_crud()->get_blocks( $post_id, $render, $persist_refs );

		$result = RF_Block_Mcp::normalize_result( $blocks );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'post_id'     => $post_id,
			'revision_id' => RF_Block_Mcp::block_crud()->get_latest_revision_id( $post_id ),
			'blocks'      => $result,
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update_block( array $input ) {
		$ready = RF_Block_Mcp::require_available();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$post_id = (int) ( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return RF_Errors::invalid_input( 'post_id is required.' );
		}

		$attributes = isset( $input['attributes'] ) && is_array( $input['attributes'] ) ? $input['attributes'] : null;
		$inner_html = array_key_exists( 'innerHTML', $input ) ? $input['innerHTML'] : null;

		if ( null === $attributes && null === $inner_html ) {
			return RF_Errors::invalid_input( 'Provide at least one of attributes or innerHTML.' );
		}

		$options = array(
			'allow_bound_writes' => ! empty( $input['allow_bound_writes'] ),
		);

		if ( isset( $input['ref'] ) && '' !== (string) $input['ref'] ) {
			$index = RF_Block_Mcp::block_crud()->resolve_ref_to_index( $post_id, (string) $input['ref'] );
			if ( is_wp_error( $index ) ) {
				return $index;
			}
		} elseif ( isset( $input['flat_index'] ) ) {
			$index = (int) $input['flat_index'];
		} else {
			return RF_Errors::invalid_input( 'Provide either ref or flat_index.' );
		}

		return RF_Block_Mcp::normalize_result(
			RF_Block_Mcp::block_crud()->update_block(
				$post_id,
				$index,
				is_array( $attributes ) ? $attributes : array(),
				$inner_html,
				$options
			)
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function mutate( array $input ) {
		$ready = RF_Block_Mcp::require_available();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$post_id = (int) ( $input['post_id'] ?? 0 );
		$op      = isset( $input['op'] ) ? sanitize_key( (string) $input['op'] ) : '';

		if ( $post_id <= 0 || '' === $op ) {
			return RF_Errors::invalid_input( 'post_id and op are required.' );
		}

		$path = RF_Block_Mcp::resolve_mutation_path( $post_id, $input );
		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$params = array(
			'attributes'  => $input['attributes'] ?? null,
			'innerHTML'   => $input['innerHTML'] ?? null,
			'block'       => $input['block'] ?? null,
			'wrapper'     => $input['wrapper'] ?? null,
			'position'    => $input['position'] ?? null,
			'destination' => $input['destination'] ?? null,
			'count'       => $input['count'] ?? null,
		);

		if ( isset( $input['destination_ref'] ) && '' !== (string) $input['destination_ref'] ) {
			$resolved = RF_Block_Mcp::block_crud()->resolve_ref( $post_id, (string) $input['destination_ref'] );
			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}
			$params['destination'] = $resolved;
		}

		if ( is_array( $params['destination'] ) ) {
			$params['destination'] = array_map( 'intval', $params['destination'] );
		}

		$dry_run = ! empty( $input['dry_run'] );

		return RF_Block_Mcp::normalize_result(
			RF_Block_Mcp::block_mutator()->mutate( $post_id, $op, $path, $params, $dry_run )
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function insert_blocks( array $input ) {
		$ready = RF_Block_Mcp::require_available();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$post_id = (int) ( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return RF_Errors::invalid_input( 'post_id is required.' );
		}

		if ( empty( $input['blocks'] ) || ! is_array( $input['blocks'] ) ) {
			return RF_Errors::invalid_input( 'blocks must be a non-empty array.' );
		}

		$position = RF_Block_Mcp::resolve_insert_position( $post_id, $input );
		if ( is_wp_error( $position ) ) {
			return $position;
		}

		$blocks = RF_Block_Mcp::sanitize_block_list( $input['blocks'] );

		return RF_Block_Mcp::normalize_result(
			RF_Block_Mcp::block_crud()->insert_blocks( $post_id, $position, $blocks )
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_page( array $input ) {
		$ready = RF_Block_Mcp::require_available();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$title = isset( $input['title'] ) ? sanitize_text_field( (string) $input['title'] ) : '';
		if ( '' === $title ) {
			return RF_Errors::invalid_input( 'title is required.' );
		}

		$args = array(
			'title'     => $title,
			'post_type' => isset( $input['post_type'] ) ? sanitize_key( (string) $input['post_type'] ) : 'page',
			'status'    => isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : 'draft',
		);

		if ( isset( $input['excerpt'] ) ) {
			$args['excerpt'] = sanitize_textarea_field( (string) $input['excerpt'] );
		}
		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( (string) $input['slug'] );
		}
		if ( ! empty( $input['blocks'] ) && is_array( $input['blocks'] ) ) {
			$args['blocks'] = RF_Block_Mcp::sanitize_block_list( $input['blocks'] );
		}

		return RF_Block_Mcp::normalize_result(
			RF_Block_Mcp::post_manager()->create_post( $args )
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function list_patterns( array $input ) {
		$ready = RF_Block_Mcp::require_available();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$args = array();
		if ( isset( $input['search'] ) ) {
			$args['q'] = sanitize_text_field( (string) $input['search'] );
		}
		if ( isset( $input['per_page'] ) ) {
			$args['limit'] = max( 1, min( 100, (int) $input['per_page'] ) );
		}

		$patterns = RF_Block_Mcp::pattern_manager()->get_patterns( $args );
		if ( is_wp_error( $patterns ) ) {
			return $patterns;
		}

		return array( 'patterns' => $patterns );
	}
}
