<?php
/**
 * WordPress.org plugin version read + upgrade helpers.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Plugin_Updater {

	/**
	 * @return string|WP_Error
	 */
	public static function get_installed_version( string $slug ) {
		$plugin_file = self::get_plugin_file( $slug );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );

		return isset( $data['Version'] ) ? (string) $data['Version'] : '';
	}

	/**
	 * @return array{version: string, plugin_file: string, package_url: string, skipped?: bool}|WP_Error
	 */
	public static function update_from_wordpress_org( string $slug, string $target_version = '' ) {
		$plugin_file = self::get_plugin_file( $slug );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		$resolved = self::resolve_update_package( $slug, $plugin_file, $target_version );
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		if ( ! empty( $resolved['skipped'] ) ) {
			return array(
				'version'     => $resolved['version'],
				'plugin_file' => $plugin_file,
				'package_url' => $resolved['package_url'],
				'skipped'     => true,
			);
		}

		self::load_upgrader_dependencies();

		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );

		if ( 'upgrade' === $resolved['method'] ) {
			$result = $upgrader->upgrade( $plugin_file );
		} else {
			$result = $upgrader->run(
				array(
					'package'           => $resolved['package_url'],
					'destination'       => WP_PLUGIN_DIR,
					'clear_destination' => true,
					'clear_working'     => true,
					'hook_extra'        => array(
						'plugin' => $plugin_file,
						'type'   => 'plugin',
						'action' => 'update',
					),
				)
			);
		}

		if ( is_wp_error( $result ) ) {
			return EC_Errors::plugin_update_failed( $result->get_error_message() );
		}

		if ( false === $result ) {
			$errors = $skin->get_errors();
			if ( is_wp_error( $errors ) && $errors->has_errors() ) {
				return EC_Errors::plugin_update_failed( $errors->get_error_message() );
			}

			return EC_Errors::plugin_update_failed( 'Plugin update failed.' );
		}

		$new_version = self::get_installed_version( $slug );
		if ( is_wp_error( $new_version ) ) {
			return $new_version;
		}

		return array(
			'version'     => $new_version,
			'plugin_file' => $plugin_file,
			'package_url' => $resolved['package_url'],
			'skipped'     => false,
		);
	}

	/**
	 * Prefer update_plugins transient data (same source as wp-admin and list-plugins).
	 *
	 * @return array{version: string, package_url: string, method: 'upgrade'|'run', skipped?: bool}|WP_Error
	 */
	private static function resolve_update_package( string $slug, string $plugin_file, string $target_version ) {
		$target = trim( $target_version );

		$current = self::get_installed_version( $slug );
		if ( is_wp_error( $current ) ) {
			return $current;
		}

		$transient_entry = self::get_transient_update_entry( $plugin_file );
		if ( $transient_entry ) {
			$transient_version = (string) ( $transient_entry->new_version ?? '' );
			$package           = (string) ( $transient_entry->package ?? '' );

			if ( '' === $target || $target === $transient_version ) {
				if ( '' !== $transient_version && $current === $transient_version ) {
					return self::skipped_package( $transient_version, $package );
				}

				if ( '' !== $transient_version && '' !== $package ) {
					return array(
						'version'     => $transient_version,
						'package_url' => $package,
						'method'      => '' === $target ? 'upgrade' : 'run',
					);
				}

				if ( '' === $target && '' !== $transient_version ) {
					$target = $transient_version;
				}
			}
		}

		if ( '' === $target ) {
			$api = self::fetch_download_link( $slug );
			if ( is_wp_error( $api ) ) {
				return $api;
			}

			$latest  = (string) ( $api->version ?? '' );
			$package = (string) ( $api->download_link ?? '' );

			if ( '' === $latest || '' === $package ) {
				return EC_Errors::plugin_update_failed(
					sprintf(
						'WordPress.org returned no download link for "%s". Check outbound HTTP access to api.wordpress.org.',
						$slug
					)
				);
			}

			if ( $current === $latest ) {
				return self::skipped_package( $latest, $package );
			}

			return array(
				'version'     => $latest,
				'package_url' => $package,
				'method'      => 'run',
			);
		}

		if ( $current === $target ) {
			return self::skipped_package( $target, self::wordpress_org_package_url( $slug, $target ) );
		}

		return array(
			'version'     => $target,
			'package_url' => self::wordpress_org_package_url( $slug, $target ),
			'method'      => 'run',
		);
	}

	/**
	 * @return array{version: string, package_url: string, method: 'run', skipped: true}
	 */
	private static function skipped_package( string $version, string $package_url ): array {
		return array(
			'version'     => $version,
			'package_url' => $package_url,
			'method'      => 'run',
			'skipped'     => true,
		);
	}

	private static function get_transient_update_entry( string $plugin_file ): ?object {
		$transient = get_site_transient( 'update_plugins' );
		if ( is_object( $transient ) && isset( $transient->response[ $plugin_file ] ) ) {
			return $transient->response[ $plugin_file ];
		}

		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		wp_update_plugins();

		$transient = get_site_transient( 'update_plugins' );
		if ( is_object( $transient ) && isset( $transient->response[ $plugin_file ] ) ) {
			return $transient->response[ $plugin_file ];
		}

		return null;
	}

	/**
	 * @return object|WP_Error
	 */
	private static function fetch_download_link( string $slug ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'downloadlink' => true,
					'version'      => true,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return EC_Errors::plugin_update_failed(
				sprintf(
					'WordPress.org API error for "%s": %s',
					$slug,
					$api->get_error_message()
				)
			);
		}

		if ( empty( $api ) || ! is_object( $api ) || ! empty( $api->error ) ) {
			return EC_Errors::plugin_not_on_wordpress_org( $slug );
		}

		return $api;
	}

	private static function wordpress_org_package_url( string $slug, string $version ): string {
		return sprintf(
			'https://downloads.wordpress.org/plugin/%s.%s.zip',
			sanitize_key( $slug ),
			sanitize_text_field( $version )
		);
	}

	/**
	 * @return string|WP_Error
	 */
	private static function get_plugin_file( string $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $plugin_file ) {
			if ( dirname( $plugin_file ) === $slug ) {
				return $plugin_file;
			}
		}

		return EC_Errors::plugin_not_installed( $slug );
	}

	private static function load_upgrader_dependencies(): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
}
