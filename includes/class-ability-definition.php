<?php
/**
 * Fluent builder for wp_register_ability() arguments.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Ability_Definition {

	private string $name = '';

	private string $label = '';

	private string $description = '';

	private string $category = '';

	/** @var array<string, mixed> */
	private array $input_schema = array();

	/** @var array<string, mixed> */
	private array $output_schema = array();

	/** @var callable */
	private $execute_callback;

	/** @var callable */
	private $permission_callback;

	private bool $mcp_public = false;

	/** @var array<string, mixed> */
	private array $annotations = array();

	private function __construct() {}

	public static function make( string $name ): self {
		$definition = new self();
		$definition->name = $name;

		return $definition;
	}

	public function label( string $label ): self {
		$this->label = $label;
		return $this;
	}

	public function description( string $description ): self {
		$this->description = $description;
		return $this;
	}

	public function category( string $category ): self {
		$this->category = $category;
		return $this;
	}

	/**
	 * @param array<string, mixed> $schema
	 */
	public function input( array $schema ): self {
		$this->input_schema = $schema;
		return $this;
	}

	/**
	 * @param array<string, mixed> $schema
	 */
	public function output( array $schema ): self {
		$this->output_schema = $schema;
		return $this;
	}

	public function execute( callable $callback ): self {
		$this->execute_callback = $callback;
		return $this;
	}

	public function permission( callable $callback ): self {
		$this->permission_callback = $callback;
		return $this;
	}

	public function mcp_public( bool $public = true ): self {
		$this->mcp_public = $public;
		return $this;
	}

	/**
	 * @param array<string, mixed> $annotations
	 */
	public function annotations( array $annotations ): self {
		$this->annotations = $annotations;
		return $this;
	}

	/**
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException
	 */
	public function build(): array {
		$this->validate();

		$args = array(
			'label'               => $this->label,
			'description'         => $this->description,
			'category'            => $this->category,
			'execute_callback'    => $this->execute_callback,
			'permission_callback' => $this->permission_callback,
			'meta'                => array(
				'annotations' => $this->annotations,
				'mcp'         => array(
					'public' => $this->mcp_public,
					'type'   => 'tool',
				),
			),
		);

		if ( ! empty( $this->input_schema ) ) {
			$args['input_schema'] = $this->input_schema;
		}

		if ( ! empty( $this->output_schema ) ) {
			$args['output_schema'] = $this->output_schema;
		}

		return array(
			'name' => $this->name,
			'args' => $args,
		);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function validate(): void {
		$prefix = defined( 'ENT_COMPANION_PREFIX' ) ? ENT_COMPANION_PREFIX : 'ent-companion/';

		if ( ! str_starts_with( $this->name, $prefix ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Ability name must start with "%s", got "%s".', $prefix, $this->name )
			);
		}

		if ( 1 !== substr_count( $this->name, '/' ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Ability name must use exactly one slash (namespace/name), got "%s".',
					$this->name
				)
			);
		}

		if ( ! preg_match( '/^[a-z0-9-]+\/[a-z0-9-]+$/', $this->name ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Ability name must match namespace/name pattern, got "%s".', $this->name )
			);
		}

		if ( '' === trim( $this->label ) ) {
			throw new InvalidArgumentException( 'Ability label is required.' );
		}

		if ( '' === trim( $this->description ) ) {
			throw new InvalidArgumentException( 'Ability description is required.' );
		}

		if ( '' === trim( $this->category ) ) {
			throw new InvalidArgumentException( 'Ability category is required.' );
		}

		if ( ! isset( $this->execute_callback ) ) {
			throw new InvalidArgumentException( 'Ability execute callback is required.' );
		}

		if ( ! isset( $this->permission_callback ) ) {
			throw new InvalidArgumentException( 'Ability permission callback is required.' );
		}

		if ( $this->mcp_public && $this->permission_callback === '__return_true' ) {
			throw new InvalidArgumentException( 'MCP-public abilities cannot use __return_true permission.' );
		}
	}
}
