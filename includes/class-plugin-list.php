<?php
/**
 * Read-only plugin inventory for MCP agents.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Plugin_List {

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public static function list_plugins( array $input = array() ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$status = isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : 'all';
		if ( ! in_array( $status, array( 'all', 'active', 'inactive' ), true ) ) {
			$status = 'all';
		}

		$search = isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '';
		$search = strtolower( trim( $search ) );

		$all_plugins   = get_plugins();
		$active_files  = (array) get_option( 'active_plugins', array() );
		$update_notice = get_site_transient( 'update_plugins' );
		$updates       = is_object( $update_notice ) && isset( $update_notice->response )
			? (array) $update_notice->response
			: array();

		$plugins = array();

		foreach ( $all_plugins as $plugin_file => $data ) {
			$slug      = self::plugin_slug_from_file( $plugin_file );
			$is_active = in_array( $plugin_file, $active_files, true );

			if ( 'active' === $status && ! $is_active ) {
				continue;
			}
			if ( 'inactive' === $status && $is_active ) {
				continue;
			}

			if ( '' !== $search ) {
				$haystack = strtolower(
					implode(
						' ',
						array(
							$slug,
							(string) ( $data['Name'] ?? '' ),
							(string) ( $data['Description'] ?? '' ),
							(string) ( $data['Author'] ?? '' ),
						)
					)
				);
				if ( ! str_contains( $haystack, $search ) ) {
					continue;
				}
			}

			$update = $updates[ $plugin_file ] ?? null;

			$plugins[] = array(
				'slug'             => $slug,
				'name'             => (string) ( $data['Name'] ?? '' ),
				'version'          => (string) ( $data['Version'] ?? '' ),
				'active'           => $is_active,
				'plugin_file'      => $plugin_file,
				'author'           => wp_strip_all_tags( (string) ( $data['Author'] ?? '' ) ),
				'update_available' => is_object( $update ),
				'new_version'      => is_object( $update ) ? (string) ( $update->new_version ?? '' ) : '',
			);
		}

		usort(
			$plugins,
			static function ( array $a, array $b ): int {
				return strcasecmp( (string) $a['name'], (string) $b['name'] );
			}
		);

		return array(
			'plugins' => $plugins,
			'count'   => count( $plugins ),
		);
	}

	private static function plugin_slug_from_file( string $plugin_file ): string {
		$dir = dirname( $plugin_file );
		if ( '.' === $dir || '' === $dir ) {
			return basename( $plugin_file, '.php' );
		}

		return $dir;
	}
}
