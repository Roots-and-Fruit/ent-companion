<?php
/**
 * Public Post Preview abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Preview_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-preview';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Preview', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Public Post Preview helpers for sharing draft links.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/enable-public-preview' )
				->label( __( 'Enable public post preview', 'rootsandfruit-abilities' ) )
				->description( __( 'Enables Public Post Preview for a draft and returns the shareable logged-out URL.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::post_id_input() )
				->output( RF_Schemas::preview_result_output() )
				->execute( array( self::class, 'enable_public_preview' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/get-public-preview-url' )
				->label( __( 'Get public post preview URL', 'rootsandfruit-abilities' ) )
				->description( __( 'Returns the public preview URL if enabled for a post, otherwise enabled=false.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::post_id_input() )
				->output( RF_Schemas::preview_result_output() )
				->execute( array( self::class, 'get_public_preview_url' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function enable_public_preview( array $input ) {
		if ( ! class_exists( 'DS_Public_Post_Preview' ) ) {
			return RF_Errors::preview_plugin_inactive();
		}

		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		self::add_post_to_preview_registry( $post_id );

		return array(
			'post_id'     => $post_id,
			'enabled'     => true,
			'preview_url' => DS_Public_Post_Preview::get_preview_link( $post ),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_public_preview_url( array $input ) {
		if ( ! class_exists( 'DS_Public_Post_Preview' ) ) {
			return RF_Errors::preview_plugin_inactive();
		}

		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		$enabled = self::is_post_in_preview_registry( $post_id );

		return array(
			'post_id'     => $post_id,
			'enabled'     => $enabled,
			'preview_url' => $enabled ? DS_Public_Post_Preview::get_preview_link( $post ) : '',
		);
	}

	private static function add_post_to_preview_registry( int $post_id ): void {
		$post_ids = array_map( 'intval', (array) get_option( 'public_post_preview', array() ) );
		if ( ! in_array( $post_id, $post_ids, true ) ) {
			$post_ids[] = $post_id;
			update_option(
				'public_post_preview',
				array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) )
			);
		}
	}

	private static function is_post_in_preview_registry( int $post_id ): bool {
		$post_ids = array_map( 'intval', (array) get_option( 'public_post_preview', array() ) );

		return in_array( $post_id, $post_ids, true );
	}
}
