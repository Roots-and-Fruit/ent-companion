<?php
/**
 * Stable WP_Error codes for R&F abilities.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Errors {

	public static function not_found( string $message = 'Resource not found.' ): WP_Error {
		return new WP_Error( 'EC_not_found', $message, array( 'status' => 404 ) );
	}

	public static function forbidden( string $message = 'Permission denied.' ): WP_Error {
		return new WP_Error( 'EC_forbidden', $message, array( 'status' => 403 ) );
	}

	public static function invalid_input( string $message ): WP_Error {
		return new WP_Error( 'EC_invalid_input', $message, array( 'status' => 400 ) );
	}

	public static function preview_plugin_inactive(): WP_Error {
		return new WP_Error(
			'EC_preview_plugin_inactive',
			'Public Post Preview plugin is not active.',
			array( 'status' => 503 )
		);
	}

	public static function post_not_found( int $post_id ): WP_Error {
		return self::not_found( sprintf( 'Post %d not found.', $post_id ) );
	}

	public static function fluent_snippets_unavailable(): WP_Error {
		return new WP_Error(
			'EC_fluent_snippets_unavailable',
			'FluentSnippets is not active.',
			array( 'status' => 503 )
		);
	}

	public static function snippet_not_found( string $file_name ): WP_Error {
		return self::not_found( sprintf( 'Snippet "%s" not found.', $file_name ) );
	}

	public static function snippets_unavailable(): WP_Error {
		return new WP_Error(
			'ec_snippets_unavailable',
			'No supported snippet plugin is active (FluentSnippets or Code Snippets).',
			array( 'status' => 503 )
		);
	}

	public static function code_snippets_unavailable(): WP_Error {
		return new WP_Error(
			'ec_code_snippets_unavailable',
			'Code Snippets is not active.',
			array( 'status' => 503 )
		);
	}

	public static function snippet_not_ec_ability( string $file_name ): WP_Error {
		return new WP_Error(
			'ec_snippet_not_ec_ability',
			sprintf( 'Snippet "%s" is not tagged ec-ability and cannot be managed via MCP.', $file_name ),
			array( 'status' => 403 )
		);
	}

	public static function snippet_operation_failed( WP_Error $error ): WP_Error {
		return new WP_Error(
			'EC_snippet_operation_failed',
			$error->get_error_message(),
			array(
				'status' => 422,
				'data'   => $error->get_error_data(),
			)
		);
	}

	public static function wp_rollback_unavailable(): WP_Error {
		return new WP_Error(
			'EC_wp_rollback_unavailable',
			'WP Rollback is not active.',
			array( 'status' => 503 )
		);
	}

	public static function plugin_not_installed( string $slug ): WP_Error {
		return self::not_found( sprintf( 'Plugin "%s" is not installed.', $slug ) );
	}

	public static function plugin_not_on_wordpress_org( string $slug ): WP_Error {
		return new WP_Error(
			'EC_plugin_not_on_wordpress_org',
			sprintf( 'Plugin "%s" is not available on WordPress.org (WP Rollback free tier scope).', $slug ),
			array( 'status' => 400 )
		);
	}

	public static function plugin_update_failed( string $message ): WP_Error {
		return new WP_Error(
			'EC_plugin_update_failed',
			$message,
			array( 'status' => 422 )
		);
	}

	public static function plugin_rollback_failed( string $message ): WP_Error {
		return new WP_Error(
			'EC_plugin_rollback_failed',
			$message,
			array( 'status' => 422 )
		);
	}

	public static function block_mcp_unavailable(): WP_Error {
		return new WP_Error(
			'EC_block_mcp_unavailable',
			'Block MCP (gk-block-mcp) is not active.',
			array( 'status' => 503 )
		);
	}
}
