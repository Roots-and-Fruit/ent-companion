<?php
/**
 * In-process bridge to GravityKit Block MCP services.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Block_Mcp {

	/** @var object|null */
	private static $block_crud = null;

	/** @var object|null */
	private static $block_mutator = null;

	/** @var object|null */
	private static $post_manager = null;

	/** @var object|null */
	private static $pattern_manager = null;

	private function __construct() {}

	public static function is_available(): bool {
		return class_exists( '\GravityKit\BlockMCP\Block_CRUD' )
			&& class_exists( '\GravityKit\BlockMCP\Block_Mutator' )
			&& class_exists( '\GravityKit\BlockMCP\Post_Manager' )
			&& class_exists( '\GravityKit\BlockMCP\Pattern_Manager' );
	}

	public static function block_crud(): object {
		self::boot_services();
		return self::$block_crud;
	}

	public static function block_mutator(): object {
		self::boot_services();
		return self::$block_mutator;
	}

	public static function post_manager(): object {
		self::boot_services();
		return self::$post_manager;
	}

	public static function pattern_manager(): object {
		self::boot_services();
		return self::$pattern_manager;
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public static function require_available() {
		if ( ! self::is_available() ) {
			return RF_Errors::block_mcp_unavailable();
		}

		return array( 'ok' => true );
	}

	/**
	 * @param mixed $result
	 * @return array<string, mixed>|WP_Error
	 */
	public static function normalize_result( $result ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! is_array( $result ) ) {
			return RF_Errors::invalid_input( 'Unexpected result from Block MCP.' );
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $block_def
	 * @return array<string, mixed>
	 */
	public static function sanitize_block_def( array $block_def ): array {
		$sanitized = array(
			'name'       => isset( $block_def['name'] ) ? sanitize_text_field( (string) $block_def['name'] ) : '',
			'attributes' => isset( $block_def['attributes'] ) && is_array( $block_def['attributes'] ) ? $block_def['attributes'] : array(),
			'innerHTML'  => isset( $block_def['innerHTML'] ) ? wp_kses_post( (string) $block_def['innerHTML'] ) : '',
		);

		if ( ! empty( $block_def['innerBlocks'] ) && is_array( $block_def['innerBlocks'] ) ) {
			$sanitized['innerBlocks'] = array_map(
				array( self::class, 'sanitize_block_def' ),
				$block_def['innerBlocks']
			);
		}

		return $sanitized;
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_block_list( array $input ): array {
		$blocks = array();
		foreach ( $input as $block_def ) {
			if ( ! is_array( $block_def ) ) {
				continue;
			}
			$blocks[] = self::sanitize_block_def( $block_def );
		}

		return $blocks;
	}

	/**
	 * Resolve insert position from ability input.
	 *
	 * @param array<string, mixed> $input
	 * @return mixed
	 */
	public static function resolve_insert_position( int $post_id, array $input ) {
		$crud = self::block_crud();

		$after_ref = isset( $input['after_ref'] ) ? (string) $input['after_ref'] : '';
		if ( '' !== $after_ref ) {
			return $crud->resolve_ref_to_top_level( $post_id, $after_ref );
		}

		$before_ref = isset( $input['before_ref'] ) ? (string) $input['before_ref'] : '';
		if ( '' !== $before_ref ) {
			$resolved = $crud->resolve_ref_to_top_level( $post_id, $before_ref );
			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}

			return $resolved > 0 ? $resolved - 1 : 'start';
		}

		if ( array_key_exists( 'after_top_level', $input ) && null !== $input['after_top_level'] ) {
			return (int) $input['after_top_level'];
		}

		if ( array_key_exists( 'before_top_level', $input ) && null !== $input['before_top_level'] ) {
			$before = (int) $input['before_top_level'];
			return $before > 0 ? $before - 1 : 'start';
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<int, int>|WP_Error
	 */
	public static function resolve_mutation_path( int $post_id, array $input ) {
		if ( isset( $input['path'] ) && is_array( $input['path'] ) && ! empty( $input['path'] ) ) {
			return array_map( 'intval', $input['path'] );
		}

		$ref = isset( $input['ref'] ) ? (string) $input['ref'] : '';
		if ( '' === $ref ) {
			return RF_Errors::invalid_input( 'Provide either path or ref.' );
		}

		$resolved = self::block_crud()->resolve_ref( $post_id, $ref );
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		return $resolved;
	}

	private static function boot_services(): void {
		if ( null !== self::$block_crud ) {
			return;
		}

		$preferences     = new \GravityKit\BlockMCP\Preferences();
		$block_inventory = new \GravityKit\BlockMCP\Block_Inventory();
		$block_safety    = new \GravityKit\BlockMCP\Block_Safety();
		$html_transformer = new \GravityKit\BlockMCP\HTML_Transformer();

		self::$block_crud       = new \GravityKit\BlockMCP\Block_CRUD( $preferences, $block_safety, $html_transformer, $block_inventory );
		self::$block_mutator    = new \GravityKit\BlockMCP\Block_Mutator( self::$block_crud, $preferences, $block_safety, $html_transformer );
		self::$post_manager     = new \GravityKit\BlockMCP\Post_Manager( self::$block_crud );
		self::$pattern_manager  = new \GravityKit\BlockMCP\Pattern_Manager( $preferences );
	}
}
