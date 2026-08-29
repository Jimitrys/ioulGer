<?php
defined( 'ABSPATH' ) || exit;

/**
 * The Translations screen.
 *
 * Deliberately small. The translating itself happens on the site, by clicking
 * the words; this screen is for the decisions around it — which languages exist,
 * whether the layer is on at all — and for getting the dictionary in and out as
 * JSON, so a set of translations can live in version control alongside the code
 * it belongs to.
 */
final class IGC_I18N_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 20 );
		add_action( 'admin_post_igc_i18n_settings', array( self::class, 'handle_settings' ) );
		add_action( 'admin_post_igc_i18n_import', array( self::class, 'handle_import' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'igc-builder',
			__( 'Translations', 'igc-builder' ),
			__( 'Translations', 'igc-builder' ),
			'manage_options',
			'igc-i18n',
			array( self::class, 'render' )
		);
	}

	public static function handle_settings(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'igc_i18n_settings' ) ) {
			wp_die( esc_html__( 'Translation settings change was not authorised.', 'igc-builder' ) );
		}

		$languages = array();

		foreach ( (array) ( $_POST['languages'] ?? array() ) as $row ) {
			$code = sanitize_key( $row['code'] ?? '' );

			if ( '' === $code ) {
				continue;
			}

			$languages[] = array(
				'code'   => $code,
				'label'  => sanitize_text_field( wp_unslash( $row['label'] ?? strtoupper( $code ) ) ),
				'name'   => sanitize_text_field( wp_unslash( $row['name'] ?? $code ) ),
				'locale' => sanitize_text_field( wp_unslash( $row['locale'] ?? $code ) ),
			);
		}

		IGC_I18N::save_settings(
			array(
				'enabled'   => ! empty( $_POST['enabled'] ),
				'default'   => sanitize_key( wp_unslash( $_POST['default'] ?? 'el' ) ),
				'languages' => $languages,
			)
		);

		// Prefixed URLs are a routing change, so stale rules would 404 them.
		flush_rewrite_rules( false );

		wp_safe_redirect( add_query_arg( 'igc_i18n', 'saved', admin_url( 'admin.php?page=igc-i18n' ) ) );
		exit;
	}

	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'igc_i18n_import' ) ) {
			wp_die( esc_html__( 'Translation import was not authorised.', 'igc-builder' ) );
		}

		$language = sanitize_key( wp_unslash( $_POST['language'] ?? '' ) );
		$decoded  = json_decode( (string) wp_unslash( $_POST['payload'] ?? '' ), true );
		$notice   = 'import-failed';

		if ( is_array( $decoded ) && isset( IGC_I18N::secondary_languages()[ $language ] ) ) {
			$notice = 'imported-' . IGC_I18N_Store::import( $language, $decoded );
		}

		wp_safe_redirect( add_query_arg( 'igc_i18n', $notice, admin_url( 'admin.php?page=igc-i18n' ) ) );
		exit;
	}

	public static function render(): void {
		$settings = IGC_I18N::settings();
		$notice   = isset( $_GET['igc_i18n'] ) ? sanitize_text_field( wp_unslash( $_GET['igc_i18n'] ) ) : '';
		?>
		<div class="wrap igc-wrap">
			<h1><?php esc_html_e( 'Translations', 'igc-builder' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'igc-builder' ); ?></p></div>
			<?php elseif ( str_starts_with( $notice, 'imported-' ) ) : ?>
				<div class="notice notice-success"><p>
					<?php
					printf(
						/* translators: %d: number of strings imported. */
						esc_html__( 'Imported %d strings.', 'igc-builder' ),
						(int) substr( $notice, 9 )
					);
					?>
				</p></div>
			<?php elseif ( 'import-failed' === $notice ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'That did not look like a JSON object of source to translation.', 'igc-builder' ); ?></p></div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'The first language is served from the site root. Every other language is served from its own prefix, by the same pages and products, with the words swapped as the page is rendered.', 'igc-builder' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="igc_i18n_settings">
				<?php wp_nonce_field( 'igc_i18n_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Multilingual', 'igc-builder' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enabled" value="1" <?php checked( (bool) $settings['enabled'] ); ?>>
								<?php esc_html_e( 'Serve the site in more than one language', 'igc-builder' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'With this off nothing changes: no prefix, no rewriting, no extra tags.', 'igc-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default language', 'igc-builder' ); ?></th>
						<td>
							<select name="default">
								<?php foreach ( IGC_I18N::languages() as $code => $language ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, IGC_I18N::default_language() ); ?>>
										<?php echo esc_html( $language['name'] . ' (' . $code . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Served from the root, with no prefix, and used as x-default.', 'igc-builder' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Languages', 'igc-builder' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Code', 'igc-builder' ); ?></th>
							<th><?php esc_html_e( 'Short label', 'igc-builder' ); ?></th>
							<th><?php esc_html_e( 'Name', 'igc-builder' ); ?></th>
							<th><?php esc_html_e( 'WordPress locale', 'igc-builder' ); ?></th>
							<th><?php esc_html_e( 'Stored strings', 'igc-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$rows = array_values( IGC_I18N::languages() );
						$rows[] = array( 'code' => '', 'label' => '', 'name' => '', 'locale' => '' );

						foreach ( $rows as $index => $language ) :
							?>
							<tr>
								<td><input type="text" name="languages[<?php echo (int) $index; ?>][code]" value="<?php echo esc_attr( $language['code'] ); ?>" size="6"></td>
								<td><input type="text" name="languages[<?php echo (int) $index; ?>][label]" value="<?php echo esc_attr( $language['label'] ); ?>" size="6"></td>
								<td><input type="text" name="languages[<?php echo (int) $index; ?>][name]" value="<?php echo esc_attr( $language['name'] ); ?>"></td>
								<td><input type="text" name="languages[<?php echo (int) $index; ?>][locale]" value="<?php echo esc_attr( $language['locale'] ); ?>" size="10"></td>
								<td><?php echo '' === $language['code'] ? '&mdash;' : esc_html( (string) IGC_I18N_Store::count( $language['code'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description">
					<?php esc_html_e( 'The locale is what WordPress and WooCommerce translate themselves with, from their own language packs. Clearing a code removes that language.', 'igc-builder' ); ?>
				</p>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Dictionary', 'igc-builder' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Translations are made on the site itself: open a page and pick a language from the admin bar. Export is here so a set of translations can be committed alongside the code, and imported into another environment.', 'igc-builder' ); ?>
			</p>

			<?php foreach ( IGC_I18N::secondary_languages() as $code => $language ) : ?>
				<h3><?php echo esc_html( $language['name'] ); ?></h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="igc_i18n_import">
					<input type="hidden" name="language" value="<?php echo esc_attr( $code ); ?>">
					<?php wp_nonce_field( 'igc_i18n_import' ); ?>
					<textarea name="payload" rows="10" class="large-text code" spellcheck="false"><?php
						echo esc_textarea( (string) wp_json_encode( IGC_I18N_Store::export( $code ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
					?></textarea>
					<?php submit_button( __( 'Import this JSON', 'igc-builder' ), 'secondary' ); ?>
				</form>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
