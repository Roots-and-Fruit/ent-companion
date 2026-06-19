<?php
/**
 * Permission callbacks for R&F abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Permissions {

	public static function can_read(): bool {
		return current_user_can( 'read' );
	}

	public static function can_create_posts( $input = null ): bool {
		return current_user_can( 'edit_posts' );
	}

	public static function can_list_posts( $input = null ): bool {
		return current_user_can( 'edit_posts' );
	}

	public static function can_edit_post( $input = null ): bool {
		$post_id = self::extract_post_id( $input );
		if ( $post_id <= 0 ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	public static function can_publish_post( $input = null ): bool {
		$post_id = self::extract_post_id( $input );
		if ( $post_id <= 0 ) {
			return false;
		}

		return current_user_can( 'publish_posts' ) && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * FluentSnippets mutating operations require unfiltered_html (matches FluentSnippets REST).
	 */
	public static function can_manage_snippets( $input = null ): bool {
		return current_user_can( 'unfiltered_html' );
	}

	public static function can_update_plugins( $input = null ): bool {
		return current_user_can( 'update_plugins' );
	}

	/**
	 * @param mixed $input Ability input.
	 */
	private static function extract_post_id( $input ): int {
		if ( ! is_array( $input ) || ! isset( $input['post_id'] ) ) {
			return 0;
		}

		return (int) $input['post_id'];
	}
}
