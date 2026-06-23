<?php
/**
 * Lightweight post-change site checks.
 *
 * @package EntCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EC_Site_Smoke_Test {

	/**
	 * @return array{ok: bool, http_status: int, message: string}
	 */
	public static function run_default(): array {
		$url = home_url( '/' );

		if ( self::dispatch_async_http_probe( $url ) ) {
			for ( $i = 0; $i < 24; $i++ ) {
				usleep( 250000 );
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'          => false,
				'http_status' => 0,
				'message'     => $response->get_error_message(),
			);
		}

		$http_status = (int) wp_remote_retrieve_response_code( $response );

		if ( $http_status >= 500 ) {
			return array(
				'ok'          => false,
				'http_status' => $http_status,
				'message'     => sprintf( 'Homepage returned HTTP %d.', $http_status ),
			);
		}

		return array(
			'ok'          => true,
			'http_status' => $http_status,
			'message'     => 'Homepage responded successfully.',
		);
	}

	private static function dispatch_async_http_probe( string $url ): bool {
		if ( ! function_exists( 'popen' ) ) {
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
}
