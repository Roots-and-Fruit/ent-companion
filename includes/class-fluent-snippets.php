<?php
/**
 * FluentSnippets integration with R&F guardrails.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Fluent_Snippets {

	public const ABILITY_TAG   = 'rf-ability';
	public const DEFAULT_GROUP = 'Roots & Fruit';

	public static function is_available(): bool {
		return class_exists( 'FluentSnippets\App\Helpers\Helper' )
			&& class_exists( 'FluentSnippets\App\Model\Snippet' );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function list_snippets( array $input ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$per_page = isset( $input['per_page'] ) ? max( 1, min( 100, (int) $input['per_page'] ) ) : 50;
		$page     = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;
		$status   = isset( $input['status'] ) ? sanitize_text_field( (string) $input['status'] ) : '';
		$tag_only = ! isset( $input['rf_ability_only'] ) || filter_var( $input['rf_ability_only'], FILTER_VALIDATE_BOOLEAN );

		$model = new \FluentSnippets\App\Model\Snippet(
			array(
				'status' => in_array( $status, array( 'published', 'draft', 'paused' ), true ) ? $status : '',
			)
		);

		$result = $model->getIndexedSnippets( $per_page, $page );
		$rows   = is_array( $result ) && isset( $result['data'] ) ? $result['data'] : $result;

		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$snippets = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( $tag_only && ! self::row_has_rf_ability_tag( $row ) ) {
				continue;
			}
			$snippets[] = self::format_summary( $row );
		}

		return array(
			'snippets' => $snippets,
			'count'    => count( $snippets ),
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_snippet( array $input ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$file_name = self::normalize_file_name( $input['file_name'] ?? '' );
		if ( is_wp_error( $file_name ) ) {
			return $file_name;
		}

		$snippet = self::load_snippet( $file_name );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		if ( ! self::meta_has_rf_ability_tag( $snippet['meta'] ) ) {
			return RF_Errors::snippet_not_rf_ability( $file_name );
		}

		return array(
			'snippet' => self::format_detail( $snippet, $file_name ),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_snippet( array $input ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$name = isset( $input['name'] ) ? sanitize_text_field( (string) $input['name'] ) : '';
		$code = isset( $input['code'] ) ? (string) $input['code'] : '';

		if ( '' === trim( $name ) ) {
			return RF_Errors::invalid_input( 'name is required.' );
		}

		if ( '' === trim( $code ) ) {
			return RF_Errors::invalid_input( 'code is required.' );
		}

		$validated_code = self::validate_ability_snippet_code( $code );
		if ( is_wp_error( $validated_code ) ) {
			return $validated_code;
		}

		$meta = array(
			'name'        => $name,
			'description' => isset( $input['description'] ) ? sanitize_textarea_field( (string) $input['description'] ) : '',
			'type'        => 'PHP',
			'run_at'      => 'all',
			'status'      => 'draft',
			'tags'        => self::ensure_rf_ability_tag( isset( $input['tags'] ) ? (string) $input['tags'] : '' ),
			'group'       => isset( $input['group'] ) ? sanitize_text_field( (string) $input['group'] ) : self::DEFAULT_GROUP,
			'priority'    => isset( $input['priority'] ) ? max( 1, (int) $input['priority'] ) : 10,
		);

		$file_name = \FluentSnippets\App\Helpers\Helper::createSnippet(
			array(
				'meta' => $meta,
				'code' => $code,
			)
		);

		if ( is_wp_error( $file_name ) ) {
			return RF_Errors::snippet_operation_failed( $file_name );
		}

		// Override FluentSnippets auto_publish — agent snippets start inactive.
		$loaded = self::load_snippet( $file_name );
		if ( ! is_wp_error( $loaded ) && 'published' === ( $loaded['status'] ?? '' ) ) {
			$deactivated = self::set_status( $file_name, 'draft' );
			if ( is_wp_error( $deactivated ) ) {
				return $deactivated;
			}
		}

		$loaded = self::load_snippet( $file_name );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		return array(
			'file_name' => $file_name,
			'snippet'   => self::format_detail( $loaded, $file_name ),
			'message'   => 'Snippet created as draft.',
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update_snippet( array $input ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$file_name = self::normalize_file_name( $input['file_name'] ?? '' );
		if ( is_wp_error( $file_name ) ) {
			return $file_name;
		}

		$snippet = self::load_snippet( $file_name );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		if ( ! self::meta_has_rf_ability_tag( $snippet['meta'] ) ) {
			return RF_Errors::snippet_not_rf_ability( $file_name );
		}

		$meta = $snippet['meta'];
		$code = $snippet['code'];

		if ( isset( $input['name'] ) ) {
			$meta['name'] = sanitize_text_field( (string) $input['name'] );
		}
		if ( isset( $input['description'] ) ) {
			$meta['description'] = sanitize_textarea_field( (string) $input['description'] );
		}
		if ( isset( $input['group'] ) ) {
			$meta['group'] = sanitize_text_field( (string) $input['group'] );
		}
		if ( isset( $input['tags'] ) ) {
			$meta['tags'] = self::ensure_rf_ability_tag( (string) $input['tags'] );
		}
		if ( isset( $input['code'] ) ) {
			$code = (string) $input['code'];
			$validated_code = self::validate_ability_snippet_code( $code );
			if ( is_wp_error( $validated_code ) ) {
				return $validated_code;
			}
		}

		$meta['type']   = 'PHP';
		$meta['run_at'] = 'all';

		$updated = \FluentSnippets\App\Helpers\Helper::updateSnippet(
			array(
				'meta'       => $meta,
				'code'       => $code,
				'file_name'  => $file_name,
				'reactivate' => ! empty( $input['reactivate'] ),
			)
		);

		if ( is_wp_error( $updated ) ) {
			return RF_Errors::snippet_operation_failed( $updated );
		}

		$loaded = self::load_snippet( $file_name );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		return array(
			'file_name' => $file_name,
			'snippet'   => self::format_detail( $loaded, $file_name ),
			'message'   => 'Snippet updated.',
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function activate_snippet( array $input ) {
		return self::set_status_from_input( $input, 'published' );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function deactivate_snippet( array $input ) {
		return self::set_status_from_input( $input, 'draft' );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	private static function set_status_from_input( array $input, string $status ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$file_name = self::normalize_file_name( $input['file_name'] ?? '' );
		if ( is_wp_error( $file_name ) ) {
			return $file_name;
		}

		$snippet = self::load_snippet( $file_name );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		if ( ! self::meta_has_rf_ability_tag( $snippet['meta'] ) ) {
			return RF_Errors::snippet_not_rf_ability( $file_name );
		}

		$result = self::set_status( $file_name, $status );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$loaded = self::load_snippet( $file_name );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		return array(
			'file_name' => $file_name,
			'status'    => $loaded['status'],
			'snippet'   => self::format_summary( array_merge( $loaded['meta'], array( 'file_name' => $file_name ) ) ),
			'message'   => 'published' === $status ? 'Snippet activated.' : 'Snippet deactivated.',
		);
	}

	/**
	 * Loopback runtime check — loads the site once and reads FluentSnippets error_files.
	 *
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function verify_snippet( array $input ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$file_name = self::normalize_file_name( $input['file_name'] ?? '' );
		if ( is_wp_error( $file_name ) ) {
			return $file_name;
		}

		$snippet = self::load_snippet( $file_name );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		if ( ! self::meta_has_rf_ability_tag( $snippet['meta'] ) ) {
			return RF_Errors::snippet_not_rf_ability( $file_name );
		}

		$status = isset( $snippet['status'] ) ? (string) $snippet['status'] : 'draft';

		if ( 'published' !== $status ) {
			return array(
				'ok'          => true,
				'error'       => '',
				'file_name'   => $file_name,
				'status'      => $status,
				'skipped'     => true,
				'http_status' => 0,
				'message'     => 'Snippet is draft; runtime verify applies only to published snippets.',
			);
		}

		$fresh = ! isset( $input['fresh'] ) || filter_var( $input['fresh'], FILTER_VALIDATE_BOOLEAN );

		$existing_error = self::read_snippet_runtime_error( $file_name );
		if ( '' !== $existing_error && ! $fresh ) {
			return array(
				'ok'          => false,
				'error'       => $existing_error,
				'file_name'   => $file_name,
				'status'      => $status,
				'skipped'     => false,
				'http_status' => 0,
				'message'     => 'Snippet has a recorded runtime error.',
			);
		}

		if ( $fresh ) {
			self::clear_snippet_runtime_error( $file_name );
		}

		$probe = self::dispatch_loopback_probe();

		$runtime_error = '';
		$attempts      = 32;

		for ( $i = 0; $i < $attempts; $i++ ) {
			usleep( 250000 );
			$runtime_error = self::read_snippet_runtime_error( $file_name );
			if ( '' !== $runtime_error ) {
				break;
			}
		}

		$http_status = 0;
		if ( '' === $runtime_error ) {
			$runtime_error = self::read_snippet_runtime_error( $file_name );
		}

		if ( 'blocking' === $probe['mode'] && ! is_wp_error( $probe['response'] ) ) {
			$http_status = (int) wp_remote_retrieve_response_code( $probe['response'] );
		} elseif ( '' === $runtime_error ) {
			$head = wp_remote_head(
				home_url( '/' ),
				array(
					'timeout'   => 10,
					'sslverify' => false,
				)
			);
			if ( ! is_wp_error( $head ) ) {
				$http_status = (int) wp_remote_retrieve_response_code( $head );
			}
		}

		if ( '' !== $runtime_error ) {
			return array(
				'ok'          => false,
				'error'       => $runtime_error,
				'file_name'   => $file_name,
				'status'      => $status,
				'skipped'     => false,
				'http_status' => $http_status,
				'message'     => 'Snippet triggered a runtime error and was quarantined by FluentSnippets.',
			);
		}

		if ( $http_status >= 500 ) {
			return array(
				'ok'          => false,
				'error'       => sprintf( 'Loopback verify returned HTTP %d.', $http_status ),
				'file_name'   => $file_name,
				'status'      => $status,
				'skipped'     => false,
				'http_status' => $http_status,
				'message'     => 'Site returned a server error during verify; check snippet code.',
			);
		}

		if ( is_wp_error( $probe['response'] ) && 'async' !== $probe['mode'] ) {
			return array(
				'ok'          => false,
				'error'       => $probe['response']->get_error_message(),
				'file_name'   => $file_name,
				'status'      => $status,
				'skipped'     => false,
				'http_status' => $http_status,
				'message'     => 'Loopback verify request failed.',
			);
		}

		return array(
			'ok'          => true,
			'error'       => '',
			'file_name'   => $file_name,
			'status'      => $status,
			'skipped'     => false,
			'http_status' => $http_status,
			'message'     => 'Snippet passed runtime verify.',
		);
	}

	/**
	 * Trigger a front-end bootstrap in a separate process when possible (avoids single-worker loopback deadlocks).
	 *
	 * @return array{mode: string, response: array<string, mixed>|WP_Error|null}
	 */
	private static function dispatch_loopback_probe(): array {
		$url = home_url( '/' );

		if ( self::dispatch_async_http_probe( $url ) ) {
			return array(
				'mode'     => 'async',
				'response' => null,
			);
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		return array(
			'mode'     => 'blocking',
			'response' => $response,
		);
	}

	private static function dispatch_async_http_probe( string $url ): bool {
		if ( ! function_exists( 'proc_open' ) && ! function_exists( 'popen' ) ) {
			return false;
		}

		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			$command = 'start /B curl.exe -s -o NUL -k ' . escapeshellarg( $url );
			$handle  = @popen( $command, 'r' );
			if ( is_resource( $handle ) ) {
				pclose( $handle );
				return true;
			}

			return false;
		}

		$command = 'curl -s -o /dev/null ' . escapeshellarg( $url ) . ' > /dev/null 2>&1 &';
		$handle  = @popen( $command, 'r' );
		if ( is_resource( $handle ) ) {
			pclose( $handle );
			return true;
		}

		return false;
	}

	/**
	 * @return true|WP_Error
	 */
	private static function set_status( string $file_name, string $status ) {
		$snippet = self::load_snippet( $file_name );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		if ( 'published' !== $status ) {
			$status = 'draft';
		}

		$snippet['meta']['status'] = $status;
		$snippet['meta']['type']   = 'PHP';
		$snippet['meta']['run_at'] = 'all';

		$model = new \FluentSnippets\App\Model\Snippet();
		$updated = $model->updateSnippet( $file_name, $snippet['code'], $snippet['meta'] );

		if ( is_wp_error( $updated ) ) {
			return RF_Errors::snippet_operation_failed( $updated );
		}

		do_action( 'fluent_snippets/snippet_status_updated', $file_name );
		do_action( 'fluent_snippets/snippet_updated', $file_name );

		return true;
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	private static function load_snippet( string $file_name ) {
		$model   = new \FluentSnippets\App\Model\Snippet();
		$snippet = $model->findByFileName( $file_name );

		if ( is_wp_error( $snippet ) ) {
			return RF_Errors::snippet_not_found( $file_name );
		}

		return $snippet;
	}

	/**
	 * @return string|WP_Error
	 */
	private static function normalize_file_name( string $file_name ) {
		$file_name = sanitize_file_name( $file_name );
		if ( '' === $file_name ) {
			return RF_Errors::invalid_input( 'file_name is required.' );
		}
		if ( ! str_ends_with( $file_name, '.php' ) ) {
			$file_name .= '.php';
		}
		if ( 'index.php' === $file_name ) {
			return RF_Errors::invalid_input( 'Invalid snippet file name.' );
		}

		return $file_name;
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	private static function meta_has_rf_ability_tag( array $meta ): bool {
		$tags = isset( $meta['tags'] ) ? (string) $meta['tags'] : '';

		return self::tags_include_rf_ability( $tags );
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function row_has_rf_ability_tag( array $row ): bool {
		$tags = isset( $row['tags'] ) ? (string) $row['tags'] : '';

		return self::tags_include_rf_ability( $tags );
	}

	private static function tags_include_rf_ability( string $tags ): bool {
		if ( '' === trim( $tags ) ) {
			return false;
		}

		$parts = array_map( 'trim', explode( ',', $tags ) );

		return in_array( self::ABILITY_TAG, $parts, true );
	}

	private static function ensure_rf_ability_tag( string $tags ): string {
		$parts = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
		if ( ! in_array( self::ABILITY_TAG, $parts, true ) ) {
			$parts[] = self::ABILITY_TAG;
		}

		return implode( ', ', $parts );
	}

	/**
	 * @return true|WP_Error
	 */
	private static function validate_ability_snippet_code( string $code ) {
		if ( preg_match( '/^<\?php/', $code ) ) {
			return RF_Errors::invalid_input( 'Remove the opening <?php tag from snippet code.' );
		}

		$uses_helper = str_contains( $code, 'rf_register_agent_ability(' )
			|| str_contains( $code, 'rf_register_agent_abilities(' );
		$uses_legacy = str_contains( $code, 'wp_register_ability(' );

		if ( ! str_contains( $code, 'wp_abilities_api_init' ) ) {
			return RF_Errors::invalid_input(
				'Snippet code must hook wp_abilities_api_init.'
			);
		}

		if ( ! $uses_helper && ! $uses_legacy ) {
			return RF_Errors::invalid_input(
				'Snippet code must call rf_register_agent_ability(), rf_register_agent_abilities(), or wp_register_ability().'
			);
		}

		if ( preg_match_all( "/wp_register_ability\s*\(\s*['\"]([^'\"]+)['\"]/", $code, $matches ) ) {
			foreach ( $matches[1] as $ability_name ) {
				if ( ! preg_match( '/^rootsandfruit\/[a-z0-9-]+$/', $ability_name ) ) {
					return RF_Errors::invalid_input(
						sprintf(
							'Ability name must match rootsandfruit/name-with-dashes, got "%s".',
							$ability_name
						)
					);
				}
			}
		}

		if ( preg_match_all( "/['\"]slug['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $code, $slug_matches ) ) {
			foreach ( $slug_matches[1] as $slug ) {
				if ( ! preg_match( '/^(?:rootsandfruit\/)?[a-z0-9-]+$/', $slug ) ) {
					return RF_Errors::invalid_input(
						sprintf( 'Ability slug must use lowercase letters, numbers, and dashes, got "%s".', $slug )
					);
				}
			}
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function format_summary( array $row ): array {
		$summary = array(
			'file_name'   => isset( $row['file_name'] ) ? (string) $row['file_name'] : '',
			'name'        => isset( $row['name'] ) ? (string) $row['name'] : '',
			'status'      => isset( $row['status'] ) ? (string) $row['status'] : '',
			'type'        => isset( $row['type'] ) ? (string) $row['type'] : '',
			'run_at'      => isset( $row['run_at'] ) ? (string) $row['run_at'] : '',
			'tags'        => isset( $row['tags'] ) ? (string) $row['tags'] : '',
			'group'       => isset( $row['group'] ) ? (string) $row['group'] : '',
			'description' => isset( $row['description'] ) ? (string) $row['description'] : '',
			'updated_at'  => isset( $row['updated_at'] ) ? (string) $row['updated_at'] : '',
			'error'       => isset( $row['error'] ) ? (string) $row['error'] : '',
		);

		if ( '' === $summary['error'] && '' !== $summary['file_name'] ) {
			$summary['error'] = self::snippet_runtime_error( $summary['file_name'] );
		}

		return $summary;
	}

	/**
	 * FluentSnippets records runtime fatals in index.php error_files.
	 */
	private static function snippet_runtime_error( string $file_name ): string {
		return self::read_snippet_runtime_error( $file_name );
	}

	/**
	 * Read error_files directly so verify sees updates from loopback requests.
	 *
	 * @return array<string, string>
	 */
	private static function read_error_files_config(): array {
		if ( ! class_exists( 'FluentSnippets\App\Helpers\Helper' ) ) {
			return array();
		}

		$path = \FluentSnippets\App\Helpers\Helper::getStorageDir() . '/index.php';
		if ( ! is_file( $path ) ) {
			return array();
		}

		$config = include $path;

		if ( ! is_array( $config ) || empty( $config['error_files'] ) || ! is_array( $config['error_files'] ) ) {
			return array();
		}

		return $config['error_files'];
	}

	private static function read_snippet_runtime_error( string $file_name ): string {
		$error_files = self::read_error_files_config();

		if ( empty( $error_files[ $file_name ] ) ) {
			return '';
		}

		return (string) $error_files[ $file_name ];
	}

	private static function clear_snippet_runtime_error( string $file_name ): void {
		if ( ! class_exists( 'FluentSnippets\App\Helpers\Helper' ) ) {
			return;
		}

		$path = \FluentSnippets\App\Helpers\Helper::getStorageDir() . '/index.php';
		if ( ! is_file( $path ) ) {
			return;
		}

		$config = include $path;
		if ( ! is_array( $config ) ) {
			return;
		}

		if ( isset( $config['error_files'][ $file_name ] ) ) {
			unset( $config['error_files'][ $file_name ] );
			\FluentSnippets\App\Helpers\Helper::saveIndexedConfig( $config, $path );
		}
	}

	/**
	 * @param array<string, mixed> $snippet
	 * @return array<string, mixed>
	 */
	private static function format_detail( array $snippet, string $file_name ): array {
		$summary = self::format_summary( array_merge( $snippet['meta'], array( 'file_name' => $file_name ) ) );

		$code = (string) $snippet['code'];
		if ( 'PHP' === ( $snippet['meta']['type'] ?? '' ) ) {
			$code = preg_replace( '/^<\?php/', '', $code );
			$code = ltrim( (string) $code, "\r\n" );
		}

		$summary['code'] = $code;

		return $summary;
	}

	/**
	 * @return true|WP_Error
	 */
	private static function require_available() {
		if ( ! self::is_available() ) {
			return RF_Errors::fluent_snippets_unavailable();
		}

		return true;
	}
}
