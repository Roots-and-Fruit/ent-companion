<?php
/**
 * Composite plugin update workflow with smoke test + rollback.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Plugin_Update_Safe {

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function run( array $input ) {
		$slug = sanitize_key( (string) ( $input['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return RF_Errors::invalid_input( 'slug is required.' );
		}

		$rollback_on_failure = ! isset( $input['rollback_on_failure'] )
			|| filter_var( $input['rollback_on_failure'], FILTER_VALIDATE_BOOLEAN );

		$target_version = isset( $input['target_version'] ) ? trim( (string) $input['target_version'] ) : '';

		$phases = array();

		$pre = RF_Plugin_Updater::get_installed_version( $slug );
		if ( is_wp_error( $pre ) ) {
			return $pre;
		}

		$phases[] = self::phase(
			'capture-baseline',
			true,
			'Recorded pre-update version.',
			array( 'version' => $pre )
		);

		$update = RF_Plugin_Updater::update_from_wordpress_org( $slug, $target_version );
		if ( is_wp_error( $update ) ) {
			$phases[] = self::phase( 'update', false, $update->get_error_message(), array() );

			return self::result(
				false,
				$slug,
				$pre,
				$pre,
				false,
				'',
				$phases,
				'Plugin update failed.'
			);
		}

		$post = (string) $update['version'];
		$phases[] = self::phase(
			'update',
			true,
			! empty( $update['skipped'] ) ? 'Plugin already at target version.' : 'Plugin updated.',
			$update
		);

		$smoke = RF_Site_Smoke_Test::run_default();
		if ( empty( $smoke['ok'] ) ) {
			$phases[] = self::phase(
				'smoke-test',
				false,
				(string) ( $smoke['message'] ?? 'Smoke test failed.' ),
				$smoke
			);

			if ( ! $rollback_on_failure ) {
				return self::result(
					false,
					$slug,
					$pre,
					$post,
					false,
					'',
					$phases,
					'Smoke test failed; rollback was disabled.'
				);
			}

			$rollback = RF_Wp_Rollback::rollback_plugin( $slug, $pre );
			if ( is_wp_error( $rollback ) ) {
				$phases[] = self::phase( 'rollback', false, $rollback->get_error_message(), array() );

				return self::result(
					false,
					$slug,
					$pre,
					$post,
					false,
					$pre,
					$phases,
					'Smoke test failed and rollback could not complete.'
				);
			}

			$rollback_ok = ! empty( $rollback['ok'] );
			$phases[] = self::phase(
				'rollback',
				$rollback_ok,
				$rollback_ok ? 'Rolled back to pre-update version.' : 'Rollback failed.',
				$rollback
			);

			$final = RF_Plugin_Updater::get_installed_version( $slug );
			$final_version = is_wp_error( $final ) ? $pre : $final;

			return self::result(
				false,
				$slug,
				$pre,
				$final_version,
				$rollback_ok,
				$pre,
				$phases,
				$rollback_ok
					? sprintf( 'Smoke test failed; rolled back to %s.', $pre )
					: 'Smoke test failed; rollback did not complete successfully.'
			);
		}

		$phases[] = self::phase(
			'smoke-test',
			true,
			(string) ( $smoke['message'] ?? 'Smoke test passed.' ),
			$smoke
		);

		return self::result(
			true,
			$slug,
			$pre,
			$post,
			false,
			'',
			$phases,
			'Plugin update succeeded.'
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $phases
	 * @return array<string, mixed>
	 */
	private static function result(
		bool $ok,
		string $slug,
		string $pre,
		string $post,
		bool $rolled_back,
		string $rollback_version,
		array $phases,
		string $message
	): array {
		return array(
			'ok'               => $ok,
			'slug'             => $slug,
			'pre_version'      => $pre,
			'post_version'     => $post,
			'rolled_back'      => $rolled_back,
			'rollback_version' => $rollback_version,
			'message'          => $message,
			'phases'           => $phases,
		);
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private static function phase( string $phase, bool $ok, string $message, array $data ): array {
		return array(
			'phase'   => $phase,
			'ok'      => $ok,
			'message' => $message,
			'data'    => $data,
		);
	}
}
