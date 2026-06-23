<?php
/**
 * Internal WP Rollback step runner (avoids REST nonce requirements).
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Wp_Rollback {

	public static function is_available(): bool {
		return class_exists( 'WpRollback\SharedCore\Core\SharedCore' )
			&& class_exists( 'WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer' )
			&& class_exists( 'WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO' );
	}

	/**
	 * @return array{ok: bool, steps: array<int, array<string, mixed>>}|WP_Error
	 */
	public static function rollback_plugin( string $slug, string $version ) {
		if ( ! self::is_available() ) {
			return EC_Errors::wp_rollback_unavailable();
		}

		if ( '' === trim( $slug ) || '' === trim( $version ) ) {
			return EC_Errors::invalid_input( 'Plugin slug and rollback version are required.' );
		}

		try {
			$registerer = \WpRollback\SharedCore\Core\SharedCore::container()->make(
				\WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer::class
			);

			$request = \WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO::fromApiRequestData(
				array(
					'assetType'    => 'plugin',
					'assetSlug'    => $slug,
					'assetVersion' => $version,
					'meta'         => array(
						'source' => 'ent-companion',
					),
				)
			);

			$steps_log = array();

			foreach ( $registerer->getAllRollbackStepsIds() as $step_id ) {
				$step = $registerer->getRollbackStepById( $step_id );
				if ( null === $step ) {
					return EC_Errors::plugin_rollback_failed(
						sprintf( 'Unknown WP Rollback step "%s".', $step_id )
					);
				}

				$result = $step->execute( $request );

				$steps_log[] = array(
					'step'    => $step_id,
					'ok'      => $result->isSuccess(),
					'message' => $result->getMessage(),
				);

				if ( ! $result->isSuccess() ) {
					return array(
						'ok'    => false,
						'steps' => $steps_log,
					);
				}

				$request = $result->getRollbackApiRequestDTO();
			}

			return array(
				'ok'    => true,
				'steps' => $steps_log,
			);
		} catch ( \Throwable $throwable ) {
			return EC_Errors::plugin_rollback_failed( $throwable->getMessage() );
		}
	}
}
