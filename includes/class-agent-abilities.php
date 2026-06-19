<?php
/**
 * Helpers for registering agent abilities from FluentSnippets.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Agent_Abilities {

	public const CUSTOM_CATEGORY = 'rootsandfruit-custom';

	/**
	 * @var array<string, callable>
	 */
	private static array $permission_map = array(
		'read'            => array( RF_Permissions::class, 'can_read' ),
		'edit_posts'      => array( RF_Permissions::class, 'can_create_posts' ),
		'list_posts'      => array( RF_Permissions::class, 'can_list_posts' ),
		'create_posts'    => array( RF_Permissions::class, 'can_create_posts' ),
		'edit_post'       => array( RF_Permissions::class, 'can_edit_post' ),
		'publish_post'    => array( RF_Permissions::class, 'can_publish_post' ),
		'manage_snippets' => array( RF_Permissions::class, 'can_manage_snippets' ),
	);

	public static function register_category(): void {
		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( self::CUSTOM_CATEGORY ) ) {
			return;
		}

		wp_register_ability_category(
			self::CUSTOM_CATEGORY,
			array(
				'label'       => __( 'Roots & Fruit — Custom', 'rootsandfruit-abilities' ),
				'description' => __( 'Agent-defined abilities registered via FluentSnippets.', 'rootsandfruit-abilities' ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $args {
	 *     @type string          $slug          Short slug (example-task) or full name (rootsandfruit/example-task).
	 *     @type string          $label         Human-readable label.
	 *     @type string          $description   Optional description.
	 *     @type string|callable $handler       Function name or callable.
	 *     @type string          $permission    Preset: read, edit_posts, edit_post, publish_post, manage_snippets.
	 *     @type array           $input_schema  Optional JSON Schema for input.
	 *     @type array           $output_schema Optional JSON Schema for output.
	 *     @type bool            $readonly      Optional MCP readonly hint.
	 *     @type bool            $mcp_public    Optional MCP discover flag (default true).
	 * }
	 */
	public static function register( array $args ): void {
		if ( ! doing_action( 'wp_abilities_api_init' ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				'rf_register_agent_ability() must be called from a wp_abilities_api_init callback.',
				'1.2.0'
			);
			return;
		}

		$name = self::normalize_name( self::extract_slug( $args ) );
		if ( '' === $name ) {
			_doing_it_wrong( __FUNCTION__, 'Ability slug is required.', '1.2.0' );
			return;
		}

		$label = isset( $args['label'] ) ? trim( (string) $args['label'] ) : '';
		if ( '' === $label ) {
			_doing_it_wrong( __FUNCTION__, 'Ability label is required.', '1.2.0' );
			return;
		}

		$handler = $args['handler'] ?? null;
		if ( ! is_string( $handler ) && ! is_callable( $handler ) ) {
			_doing_it_wrong( __FUNCTION__, 'Ability handler must be a function name or callable.', '1.2.0' );
			return;
		}

		if ( is_string( $handler ) && ! function_exists( $handler ) && ! is_callable( $handler ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf( 'Handler function "%s" is not defined.', $handler ),
				'1.2.0'
			);
			return;
		}

		$permission = isset( $args['permission'] ) ? (string) $args['permission'] : 'edit_posts';
		$permission_callback = self::resolve_permission( $permission );
		if ( null === $permission_callback ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf( 'Unknown permission preset "%s".', $permission ),
				'1.2.0'
			);
			return;
		}

		$description = isset( $args['description'] ) ? trim( (string) $args['description'] ) : $label;
		$readonly      = ! empty( $args['readonly'] );
		$mcp_public    = ! isset( $args['mcp_public'] ) || filter_var( $args['mcp_public'], FILTER_VALIDATE_BOOLEAN );

		$ability_args = array(
			'label'               => $label,
			'description'         => $description,
			'category'            => self::CUSTOM_CATEGORY,
			'input_schema'        => isset( $args['input_schema'] ) && is_array( $args['input_schema'] )
				? $args['input_schema']
				: self::default_input_schema(),
			'output_schema'       => isset( $args['output_schema'] ) && is_array( $args['output_schema'] )
				? $args['output_schema']
				: self::default_output_schema(),
			'execute_callback'    => is_string( $handler ) ? $handler : $handler,
			'permission_callback' => $permission_callback,
			'meta'                => array(
				'annotations' => $readonly ? RF_Annotations::read_only() : RF_Annotations::write_safe(),
				'mcp'         => array(
					'public' => $mcp_public,
					'type'   => 'tool',
				),
			),
		);

		wp_register_ability( $name, $ability_args );
	}

	/**
	 * @param array<int, array<string, mixed>> $abilities
	 */
	public static function register_many( array $abilities ): void {
		foreach ( $abilities as $args ) {
			if ( is_array( $args ) ) {
				self::register( $args );
			}
		}
	}

	/**
	 * @param array<string, mixed> $args
	 */
	private static function extract_slug( array $args ): string {
		if ( ! empty( $args['slug'] ) ) {
			return (string) $args['slug'];
		}

		if ( ! empty( $args['name'] ) ) {
			return (string) $args['name'];
		}

		return '';
	}

	private static function normalize_name( string $slug ): string {
		$slug = trim( $slug );
		if ( '' === $slug ) {
			return '';
		}

		$prefix = defined( 'RF_ABILITIES_PREFIX' ) ? RF_ABILITIES_PREFIX : 'rootsandfruit/';

		if ( str_contains( $slug, '/' ) ) {
			$name = $slug;
		} else {
			$name = $prefix . $slug;
		}

		if ( ! preg_match( '/^rootsandfruit\/[a-z0-9-]+$/', $name ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf( 'Ability name must match rootsandfruit/name-with-dashes, got "%s".', $name ),
				'1.2.0'
			);
			return '';
		}

		return $name;
	}

	/**
	 * @return callable|null
	 */
	private static function resolve_permission( string $preset ) {
		if ( isset( self::$permission_map[ $preset ] ) ) {
			return self::$permission_map[ $preset ];
		}

		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function default_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function default_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'ok' => array( 'type' => 'boolean' ),
			),
		);
	}
}

/**
 * Register one agent ability from a FluentSnippets wp_abilities_api_init callback.
 *
 * @param array<string, mixed> $args Ability definition (see RF_Agent_Abilities::register).
 */
function rf_register_agent_ability( array $args ): void {
	RF_Agent_Abilities::register( $args );
}

/**
 * Register multiple agent abilities from one wp_abilities_api_init callback.
 *
 * @param array<int, array<string, mixed>> $abilities List of ability definitions.
 */
function rf_register_agent_abilities( array $abilities ): void {
	RF_Agent_Abilities::register_many( $abilities );
}
