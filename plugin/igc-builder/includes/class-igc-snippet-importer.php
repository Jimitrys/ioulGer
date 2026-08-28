<?php
defined( 'ABSPATH' ) || exit;

/**
 * Imports the JSON format produced by the Code Snippets plugin.
 *
 * Imported snippets are deliberately staged as disabled. This prevents the
 * same hooks from running twice while the original plugin is still active.
 */
final class IGC_Snippet_Importer {
	private const MAX_FILE_SIZE = 5242880;

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 30 );
		add_action( 'admin_post_igc_import_code_snippets', array( self::class, 'import' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'igc-builder',
			__( 'Import PHP Snippets', 'igc-builder' ),
			__( 'Import PHP Snippets', 'igc-builder' ),
			'manage_options',
			'igc-snippet-import',
			array( self::class, 'page' )
		);
	}

	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) {
			wp_die( esc_html__( 'You do not have permission to import PHP snippets.', 'igc-builder' ) );
		}

		$result = get_transient( 'igc_snippet_import_' . get_current_user_id() );
		delete_transient( 'igc_snippet_import_' . get_current_user_id() );
		?>
		<div class="wrap igc-admin-wrap">
			<div class="igc-admin-hero">
				<div><span class="igc-eyebrow">SITE STUDIO</span><h1><?php esc_html_e( 'Import PHP Snippets', 'igc-builder' ); ?></h1></div>
			</div>

			<?php if ( is_array( $result ) ) : ?>
				<div class="notice <?php echo empty( $result['error'] ) ? 'notice-success' : 'notice-warning'; ?> inline"><p>
					<strong><?php echo esc_html( (string) $result['message'] ); ?></strong>
				</p></div>
				<?php if ( ! empty( $result['details'] ) && is_array( $result['details'] ) ) : ?>
					<div class="igc-panel"><ul class="ul-disc">
						<?php foreach ( $result['details'] as $detail ) : ?><li><?php echo esc_html( (string) $detail ); ?></li><?php endforeach; ?>
					</ul></div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="igc-panel">
				<div class="igc-panel__heading">
					<div><h2><?php esc_html_e( 'Code Snippets JSON', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Upload one JSON export from the Code Snippets plugin. Every valid, new snippet is imported in a disabled state.', 'igc-builder' ); ?></p></div>
					<code>Max 5 MB</code>
				</div>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="igc_import_code_snippets">
					<?php wp_nonce_field( 'igc_import_code_snippets', 'igc_snippet_import_nonce' ); ?>
					<p><input type="file" name="snippets_json" accept="application/json,.json" required></p>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Validate & Import All', 'igc-builder' ); ?></button></p>
				</form>
				<p class="description"><strong><?php esc_html_e( 'Safe migration:', 'igc-builder' ); ?></strong> <?php esc_html_e( 'Existing code is not overwritten, duplicates are skipped, and nothing is activated automatically. Disable the matching original snippet before activating its Site Studio copy.', 'igc-builder' ); ?></p>
			</div>
		</div>
		<?php
	}

	public static function import(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' )
			|| ! isset( $_POST['igc_snippet_import_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_snippet_import_nonce'] ) ), 'igc_import_code_snippets' )
		) {
			wp_die( esc_html__( 'Snippet import was not authorised.', 'igc-builder' ) );
		}

		$file = isset( $_FILES['snippets_json'] ) && is_array( $_FILES['snippets_json'] ) ? $_FILES['snippets_json'] : array();
		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			self::finish( __( 'The JSON file could not be uploaded.', 'igc-builder' ), true );
		}
		if ( (int) ( $file['size'] ?? 0 ) < 1 || (int) $file['size'] > self::MAX_FILE_SIZE ) {
			self::finish( __( 'The JSON file is empty or larger than 5 MB.', 'igc-builder' ), true );
		}

		$path = (string) ( $file['tmp_name'] ?? '' );
		$raw  = is_uploaded_file( $path ) ? file_get_contents( $path ) : false;
		if ( false === $raw ) {
			self::finish( __( 'The uploaded JSON file could not be read.', 'igc-builder' ), true );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || 'Code Snippets' !== (string) ( $data['generator'] ?? '' ) || ! isset( $data['snippets'] ) || ! is_array( $data['snippets'] ) ) {
			self::finish( __( 'This is not a valid Code Snippets JSON export.', 'igc-builder' ), true );
		}

		$existing_hashes = self::existing_hashes();
		$prepared        = array();
		$details         = array();
		$seen_hashes     = array();

		foreach ( $data['snippets'] as $index => $snippet ) {
			$name = sanitize_text_field( (string) ( $snippet['name'] ?? '' ) );
			$code = self::normalise_code( (string) ( $snippet['code'] ?? '' ) );
			$name = '' !== $name ? $name : sprintf( __( 'Imported snippet %d', 'igc-builder' ), $index + 1 );
			$hash = hash( 'sha256', $code );

			if ( isset( $existing_hashes[ $hash ] ) || isset( $seen_hashes[ $hash ] ) ) {
				$details[] = sprintf( __( 'Skipped duplicate: %s', 'igc-builder' ), $name );
				continue;
			}

			$validation = IGC_Snippet_Validator::validate( $code );
			if ( is_wp_error( $validation ) ) {
				$details[] = sprintf( __( 'Skipped %1$s: %2$s', 'igc-builder' ), $name, $validation->get_error_message() );
				continue;
			}

			$seen_hashes[ $hash ] = true;
			$prepared[] = array(
				'name'       => $name,
				'code'       => $code,
				'source_id'  => absint( $snippet['id'] ?? 0 ),
				'modified'   => sanitize_text_field( (string) ( $snippet['modified'] ?? '' ) ),
			);
		}

		$imported = 0;
		foreach ( $prepared as $snippet ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'igc_php_snippet',
					'post_status' => 'publish',
					'post_title'  => $snippet['name'],
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				$details[] = sprintf( __( 'Could not import %1$s: %2$s', 'igc-builder' ), $snippet['name'], $post_id->get_error_message() );
				continue;
			}

			update_post_meta( $post_id, '_igc_php', $snippet['code'] );
			update_post_meta( $post_id, '_igc_scope', 'everywhere' );
			update_post_meta( $post_id, '_igc_enabled', 0 );
			update_post_meta( $post_id, '_igc_validation_status', 'passed' );
			update_post_meta( $post_id, '_igc_last_error', '' );
			update_post_meta( $post_id, '_igc_last_validated', current_time( 'mysql', true ) );
			update_post_meta( $post_id, '_igc_import_source', 'code-snippets' );
			update_post_meta( $post_id, '_igc_import_source_id', $snippet['source_id'] );
			update_post_meta( $post_id, '_igc_import_source_modified', $snippet['modified'] );
			$imported++;
		}

		self::finish(
			sprintf(
				/* translators: 1: imported count, 2: source count. */
				__( 'Imported %1$d of %2$d snippets. All imported snippets are disabled.', 'igc-builder' ),
				$imported,
				count( $data['snippets'] )
			),
			$imported < 1,
			$details
		);
	}

	private static function existing_hashes(): array {
		$hashes = array();
		$ids    = get_posts(
			array(
				'post_type'      => 'igc_php_snippet',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			$code = trim( (string) get_post_meta( (int) $id, '_igc_php', true ) );
			if ( '' !== $code ) {
				$hashes[ hash( 'sha256', $code ) ] = true;
			}
		}
		return $hashes;
	}

	private static function normalise_code( string $code ): string {
		$code = trim( $code );
		// Code Snippets exports normally omit the leading tag, but older exports
		// may include it. eval() expects the first statement without `<?php`.
		if ( preg_match( '/^<\?php(?:\s|$)/i', $code ) ) {
			$code = preg_replace( '/^<\?php(?:\r?\n|\s)?/i', '', $code, 1 ) ?? $code;
		}

		// Standalone plugin files often contain an ABSPATH + exit guard. A stored
		// WordPress snippet can only execute after WordPress has loaded, so these
		// exact guards are redundant. Removing only this known pattern lets the
		// normal safety rule continue to reject meaningful exit/die statements.
		$code = preg_replace(
			array(
				'/\bdefined\s*\(\s*([\'\"])ABSPATH\1\s*\)\s*\|\|\s*exit\s*;/i',
				'/if\s*\(\s*!\s*defined\s*\(\s*([\'\"])ABSPATH\1\s*\)\s*\)\s*\{\s*exit\s*;\s*\}/i',
			),
			'',
			$code
		) ?? $code;
		return trim( $code );
	}

	private static function finish( string $message, bool $error = false, array $details = array() ): void {
		set_transient(
			'igc_snippet_import_' . get_current_user_id(),
			array( 'message' => $message, 'error' => $error, 'details' => $details ),
			120
		);
		wp_safe_redirect( admin_url( 'admin.php?page=igc-snippet-import' ) );
		exit;
	}
}
