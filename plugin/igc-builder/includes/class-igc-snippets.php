<?php
defined( 'ABSPATH' ) || exit;

final class IGC_Snippets {
	private static int $running_id = 0;

	public static function init(): void {
		// Run after our post types exist but before normal `init` callbacks are fired.
		add_action( 'init', array( self::class, 'run' ), 1 );
		register_shutdown_function( array( self::class, 'shutdown_guard' ) );
	}

	public static function run(): void {
		if ( defined( 'IGC_SAFE_MODE' ) && IGC_SAFE_MODE ) {
			return;
		}

		$snippets = get_posts(
			array(
				'post_type'      => 'igc_php_snippet',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_igc_enabled',
						'value' => '1',
					),
				),
			)
		);

		foreach ( $snippets as $snippet ) {
			$scope = (string) get_post_meta( $snippet->ID, '_igc_scope', true );
			if ( ( 'admin' === $scope && ! is_admin() ) || ( 'frontend' === $scope && is_admin() ) ) {
				continue;
			}

			$code = trim( (string) get_post_meta( $snippet->ID, '_igc_php', true ) );
			if ( '' === $code ) {
				continue;
			}

			if ( 'passed' !== get_post_meta( $snippet->ID, '_igc_validation_status', true ) ) {
				$validation = IGC_Snippet_Validator::validate( $code );
				if ( is_wp_error( $validation ) ) {
					self::disable( $snippet->ID, $validation->get_error_message() );
					continue;
				}
				update_post_meta( $snippet->ID, '_igc_validation_status', 'passed' );
			}

			try {
				// Snippets are administrator-authored PHP and intentionally execute in WordPress scope.
				self::$running_id = $snippet->ID;
				eval( $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
				self::$running_id = 0;
			} catch ( Throwable $error ) {
				self::$running_id = 0;
				self::disable( $snippet->ID, $error->getMessage() );
			}
		}
	}

	public static function shutdown_guard(): void {
		if ( ! self::$running_id ) {
			return;
		}
		$error = error_get_last();
		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			return;
		}
		self::disable( self::$running_id, (string) $error['message'] );
	}

	private static function disable( int $snippet_id, string $message ): void {
		update_post_meta( $snippet_id, '_igc_enabled', 0 );
		update_post_meta( $snippet_id, '_igc_validation_status', 'failed' );
		update_post_meta( $snippet_id, '_igc_last_error', $message );
		error_log( sprintf( 'Site Studio disabled snippet %d: %s', $snippet_id, $message ) );
	}
}
