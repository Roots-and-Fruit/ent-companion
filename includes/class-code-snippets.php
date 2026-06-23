<?php
/**
 * Code Snippets plugin integration for Ent Companion ability snippets.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Code_Snippets {

	public const ABILITY_TAG = 'ec-ability';

	public static function is_available(): bool {
		return function_exists( 'code_snippets' ) && function_exists( 'get_snippets' );
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
		$tag_only = ! isset( $input['ec_ability_only'] ) || filter_var( $input['ec_ability_only'], FILTER_VALIDATE_BOOLEAN );

		$all = get_snippets( array(), false );
		if ( ! is_array( $all ) ) {
			$all = array();
		}

		$filtered = array();
		foreach ( $all as $snippet ) {
			if ( ! self::is_ec_ability_snippet( $snippet ) && $tag_only ) {
				continue;
			}
			if ( '' !== $status ) {
				$active = self::snippet_is_active( $snippet );
				if ( 'published' === $status && ! $active ) {
					continue;
				}
				if ( 'draft' === $status && $active ) {
					continue;
				}
			}
			$filtered[] = $snippet;
		}

		$offset   = ( $page - 1 ) * $per_page;
		$pageRows = array_slice( $filtered, $offset, $per_page );

		return array(
			'provider' => 'code_snippets',
			'snippets' => array_map( array( self::class, 'format_summary' ), $pageRows ),
			'count'    => count( $pageRows ),
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

		$id = self::normalize_id( $input['file_name'] ?? ( $input['snippet_id'] ?? '' ) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$snippet = self::load_snippet( $id );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		if ( ! self::is_ec_ability_snippet( $snippet ) ) {
			return EC_Errors::snippet_not_ec_ability( (string) $id );
		}

		return array(
			'provider' => 'code_snippets',
			'snippet'  => self::format_detail( $snippet ),
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
			return EC_Errors::invalid_input( 'name is required.' );
		}
		if ( '' === trim( $code ) ) {
			return EC_Errors::invalid_input( 'code is required.' );
		}

		$validated = self::validate_ability_snippet_code( $code );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$data = array(
			'name'        => $name,
			'description' => self::ensure_ec_ability_description(
				isset( $input['description'] ) ? (string) $input['description'] : ''
			),
			'code'        => $code,
			'scope'       => 'global',
			'active'      => false,
			'tags'        => self::ensure_ec_ability_tags( $input['tags'] ?? array() ),
		);

		$id = self::persist_snippet( $data );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$snippet = self::load_snippet( $id );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}

		return array(
			'provider'  => 'code_snippets',
			'file_name' => (string) $id,
			'snippet'   => self::format_detail( $snippet ),
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

		$id = self::normalize_id( $input['file_name'] ?? '' );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$snippet = self::load_snippet( $id );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}
		if ( ! self::is_ec_ability_snippet( $snippet ) ) {
			return EC_Errors::snippet_not_ec_ability( (string) $id );
		}

		$data = self::snippet_to_array( $snippet );
		if ( isset( $input['name'] ) ) {
			$data['name'] = sanitize_text_field( (string) $input['name'] );
		}
		if ( isset( $input['description'] ) ) {
			$data['description'] = self::ensure_ec_ability_description( (string) $input['description'] );
		}
		if ( isset( $input['code'] ) ) {
			$data['code'] = (string) $input['code'];
			$validated    = self::validate_ability_snippet_code( $data['code'] );
			if ( is_wp_error( $validated ) ) {
				return $validated;
			}
		}
		if ( isset( $input['tags'] ) ) {
			$data['tags'] = self::ensure_ec_ability_tags( $input['tags'] );
		}

		$data['id'] = $id;
		$saved      = self::persist_snippet( $data, $id );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$loaded = self::load_snippet( $id );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		return array(
			'provider'  => 'code_snippets',
			'file_name' => (string) $id,
			'snippet'   => self::format_detail( $loaded ),
			'message'   => 'Snippet updated.',
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function activate_snippet( array $input ) {
		return self::set_active_from_input( $input, true );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function deactivate_snippet( array $input ) {
		return self::set_active_from_input( $input, false );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function verify_snippet( array $input ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$id = self::normalize_id( $input['file_name'] ?? '' );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$snippet = self::load_snippet( $id );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}
		if ( ! self::is_ec_ability_snippet( $snippet ) ) {
			return EC_Errors::snippet_not_ec_ability( (string) $id );
		}

		if ( ! self::snippet_is_active( $snippet ) ) {
			return array(
				'ok'          => true,
				'error'       => '',
				'file_name'   => (string) $id,
				'status'      => 'draft',
				'skipped'     => true,
				'http_status' => 0,
				'message'     => 'Snippet is inactive; runtime verify applies only to active snippets.',
			);
		}

		$response = wp_remote_get(
			home_url( '/' ),
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);
		$http_status = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'          => false,
				'error'       => $response->get_error_message(),
				'file_name'   => (string) $id,
				'status'      => 'published',
				'skipped'     => false,
				'http_status' => $http_status,
				'message'     => 'Loopback verify request failed.',
			);
		}

		if ( $http_status >= 500 ) {
			return array(
				'ok'          => false,
				'error'       => sprintf( 'Loopback verify returned HTTP %d.', $http_status ),
				'file_name'   => (string) $id,
				'status'      => 'published',
				'skipped'     => false,
				'http_status' => $http_status,
				'message'     => 'Site returned a server error during verify; check snippet code.',
			);
		}

		return array(
			'ok'          => true,
			'error'       => '',
			'file_name'   => (string) $id,
			'status'      => 'published',
			'skipped'     => false,
			'http_status' => $http_status,
			'message'     => 'Snippet passed runtime verify.',
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	private static function set_active_from_input( array $input, bool $active ) {
		$unavailable = self::require_available();
		if ( is_wp_error( $unavailable ) ) {
			return $unavailable;
		}

		$id = self::normalize_id( $input['file_name'] ?? '' );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$snippet = self::load_snippet( $id );
		if ( is_wp_error( $snippet ) ) {
			return $snippet;
		}
		if ( ! self::is_ec_ability_snippet( $snippet ) ) {
			return EC_Errors::snippet_not_ec_ability( (string) $id );
		}

		$data           = self::snippet_to_array( $snippet );
		$data['id']     = $id;
		$data['active'] = $active;
		$saved          = self::persist_snippet( $data, $id );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$loaded = self::load_snippet( $id );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		return array(
			'provider'  => 'code_snippets',
			'file_name' => (string) $id,
			'status'    => self::snippet_is_active( $loaded ) ? 'published' : 'draft',
			'snippet'   => self::format_summary( $loaded ),
			'message'   => $active ? 'Snippet activated.' : 'Snippet deactivated.',
		);
	}

	/**
	 * @param mixed $snippet
	 */
	private static function is_ec_ability_snippet( $snippet ): bool {
		$tags = self::snippet_tags( $snippet );
		if ( in_array( self::ABILITY_TAG, $tags, true ) ) {
			return true;
		}

		$description = self::snippet_field( $snippet, 'description', '' );
		return str_contains( strtolower( $description ), self::ABILITY_TAG );
	}

	/**
	 * @param mixed $snippet
	 * @return array<int, string>
	 */
	private static function snippet_tags( $snippet ): array {
		$tags = self::snippet_field( $snippet, 'tags', array() );
		if ( is_string( $tags ) ) {
			return array_filter( array_map( 'trim', explode( ',', $tags ) ) );
		}
		if ( is_array( $tags ) ) {
			return array_values( array_filter( array_map( 'strval', $tags ) ) );
		}

		return array();
	}

	/**
	 * @param mixed $snippet
	 */
	private static function snippet_is_active( $snippet ): bool {
		$active = self::snippet_field( $snippet, 'active', false );
		return filter_var( $active, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @param mixed  $snippet
	 * @param string $field
	 * @param mixed  $default
	 * @return mixed
	 */
	private static function snippet_field( $snippet, string $field, $default = '' ) {
		if ( is_object( $snippet ) && isset( $snippet->{$field} ) ) {
			return $snippet->{$field};
		}
		if ( is_array( $snippet ) && array_key_exists( $field, $snippet ) ) {
			return $snippet[ $field ];
		}

		return $default;
	}

	/**
	 * @param mixed $snippet
	 * @return array<string, mixed>
	 */
	private static function snippet_to_array( $snippet ): array {
		return array(
			'id'          => self::snippet_field( $snippet, 'id', 0 ),
			'name'        => (string) self::snippet_field( $snippet, 'name', '' ),
			'description' => (string) self::snippet_field( $snippet, 'description', '' ),
			'code'        => (string) self::snippet_field( $snippet, 'code', '' ),
			'scope'       => (string) self::snippet_field( $snippet, 'scope', 'global' ),
			'active'      => self::snippet_is_active( $snippet ),
			'tags'        => self::snippet_tags( $snippet ),
		);
	}

	/**
	 * @param mixed $snippet
	 * @return array<string, mixed>
	 */
	private static function format_summary( $snippet ): array {
		$id = (string) self::snippet_field( $snippet, 'id', '' );

		return array(
			'file_name'   => $id,
			'snippet_id'  => $id,
			'name'        => (string) self::snippet_field( $snippet, 'name', '' ),
			'status'      => self::snippet_is_active( $snippet ) ? 'published' : 'draft',
			'type'        => 'PHP',
			'run_at'      => (string) self::snippet_field( $snippet, 'scope', 'global' ),
			'tags'        => implode( ', ', self::snippet_tags( $snippet ) ),
			'group'       => '',
			'description' => (string) self::snippet_field( $snippet, 'description', '' ),
			'updated_at'  => '',
			'error'       => '',
		);
	}

	/**
	 * @param mixed $snippet
	 * @return array<string, mixed>
	 */
	private static function format_detail( $snippet ): array {
		$summary         = self::format_summary( $snippet );
		$summary['code'] = ltrim( (string) self::snippet_field( $snippet, 'code', '' ), "\r\n" );

		return $summary;
	}

	/**
	 * @return int|WP_Error
	 */
	private static function normalize_id( $raw ) {
		$id = is_numeric( $raw ) ? (int) $raw : (int) preg_replace( '/\D+/', '', (string) $raw );
		if ( $id < 1 ) {
			return EC_Errors::invalid_input( 'file_name or snippet_id is required.' );
		}

		return $id;
	}

	/**
	 * @return mixed|WP_Error
	 */
	private static function load_snippet( int $id ) {
		if ( function_exists( 'get_snippet' ) ) {
			$snippet = get_snippet( $id );
			if ( $snippet ) {
				return $snippet;
			}
		}

		foreach ( get_snippets( array( $id ), false ) as $snippet ) {
			if ( (int) self::snippet_field( $snippet, 'id', 0 ) === $id ) {
				return $snippet;
			}
		}

		return EC_Errors::snippet_not_found( (string) $id );
	}

	/**
	 * @param array<string, mixed> $data
	 * @param int|null             $id
	 * @return int|WP_Error
	 */
	private static function persist_snippet( array $data, ?int $id = null ) {
		$db = code_snippets()->db;

		if ( $id && method_exists( $db, 'update_snippet' ) ) {
			$result = $db->update_snippet( $id, $data, false );
			return is_wp_error( $result ) ? EC_Errors::snippet_operation_failed( $result ) : $id;
		}

		if ( method_exists( $db, 'add_snippet' ) ) {
			$result = $db->add_snippet( $data, false );
			if ( is_wp_error( $result ) ) {
				return EC_Errors::snippet_operation_failed( $result );
			}
			return (int) $result;
		}

		if ( class_exists( '\Code_Snippets\Model\Snippet' ) ) {
			$model = new \Code_Snippets\Model\Snippet( $data );
			if ( method_exists( $db, 'save_snippet' ) ) {
				$result = $db->save_snippet( $model, false );
				if ( is_wp_error( $result ) ) {
					return EC_Errors::snippet_operation_failed( $result );
				}
				return (int) self::snippet_field( $model, 'id', $result );
			}
		}

		return EC_Errors::invalid_input( 'Code Snippets save API is not available on this site version.' );
	}

	/**
	 * @param mixed $tags
	 * @return array<int, string>
	 */
	private static function ensure_ec_ability_tags( $tags ): array {
		$list = array();
		if ( is_string( $tags ) ) {
			$list = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
		} elseif ( is_array( $tags ) ) {
			$list = array_values( array_filter( array_map( 'strval', $tags ) ) );
		}
		if ( ! in_array( self::ABILITY_TAG, $list, true ) ) {
			$list[] = self::ABILITY_TAG;
		}

		return $list;
	}

	private static function ensure_ec_ability_description( string $description ): string {
		if ( str_contains( strtolower( $description ), self::ABILITY_TAG ) ) {
			return $description;
		}

		return trim( $description . ' [' . self::ABILITY_TAG . ']' );
	}

	/**
	 * @return true|WP_Error
	 */
	private static function validate_ability_snippet_code( string $code ) {
		if ( preg_match( '/^<\?php/', $code ) ) {
			return EC_Errors::invalid_input( 'Remove the opening <?php tag from snippet code.' );
		}

		if ( ! str_contains( $code, 'wp_abilities_api_init' ) ) {
			return EC_Errors::invalid_input( 'Snippet code must hook wp_abilities_api_init.' );
		}

		$uses_helper = str_contains( $code, 'ec_register_agent_ability(' )
			|| str_contains( $code, 'ec_register_agent_abilities(' );
		if ( ! $uses_helper && ! str_contains( $code, 'wp_register_ability(' ) ) {
			return EC_Errors::invalid_input(
				'Snippet code must call ec_register_agent_ability(), ec_register_agent_abilities(), or wp_register_ability().'
			);
		}

		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	private static function require_available() {
		if ( ! self::is_available() ) {
			return EC_Errors::code_snippets_unavailable();
		}

		return true;
	}
}
