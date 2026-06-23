<?php
/**
 * Snippet provider facade — FluentSnippets or Code Snippets.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Snippets {

	public static function is_available(): bool {
		return EC_Fluent_Snippets::is_available() || EC_Code_Snippets::is_available();
	}

	/**
	 * @return 'fluent'|'code_snippets'|''
	 */
	public static function active_provider( array $input = array() ): string {
		if ( ! empty( $input['provider'] ) ) {
			$provider = sanitize_key( (string) $input['provider'] );
			if ( 'fluent' === $provider && EC_Fluent_Snippets::is_available() ) {
				return 'fluent';
			}
			if ( 'code_snippets' === $provider && EC_Code_Snippets::is_available() ) {
				return 'code_snippets';
			}
		}

		if ( EC_Fluent_Snippets::is_available() ) {
			return 'fluent';
		}

		if ( EC_Code_Snippets::is_available() ) {
			return 'code_snippets';
		}

		return '';
	}

	/**
	 * @return array<int, string>
	 */
	public static function available_providers(): array {
		$providers = array();
		if ( EC_Fluent_Snippets::is_available() ) {
			$providers[] = 'fluent';
		}
		if ( EC_Code_Snippets::is_available() ) {
			$providers[] = 'code_snippets';
		}

		return $providers;
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function list_snippets( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::list_snippets( $input );
		}

		return EC_Fluent_Snippets::list_snippets( $input );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_snippet( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::get_snippet( $input );
		}

		return EC_Fluent_Snippets::get_snippet( $input );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_snippet( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::create_snippet( $input );
		}

		return EC_Fluent_Snippets::create_snippet( $input );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update_snippet( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::update_snippet( $input );
		}

		return EC_Fluent_Snippets::update_snippet( $input );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function activate_snippet( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::activate_snippet( $input );
		}

		return EC_Fluent_Snippets::activate_snippet( $input );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function deactivate_snippet( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::deactivate_snippet( $input );
		}

		return EC_Fluent_Snippets::deactivate_snippet( $input );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function verify_snippet( array $input ) {
		$provider = self::active_provider( $input );
		if ( '' === $provider ) {
			return EC_Errors::snippets_unavailable();
		}

		if ( 'code_snippets' === $provider ) {
			return EC_Code_Snippets::verify_snippet( $input );
		}

		return EC_Fluent_Snippets::verify_snippet( $input );
	}
}
