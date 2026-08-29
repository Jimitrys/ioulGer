<?php
defined( 'ABSPATH' ) || exit;

/**
 * Translate by clicking the text on the site.
 *
 * Open any page in the site's own language as an editor and pick a target from
 * the admin bar. Every readable string becomes clickable and a panel holds the
 * original beside its translation.
 *
 * The editor never modifies the page markup. It finds text with a TreeWalker and
 * draws a highlight over it, so the layout, the CSS selectors and the animations
 * are exactly as a visitor sees them while you work. Wrapping each string in a
 * span — the usual approach — changes sibling selectors and flex layouts, and
 * the page you are editing stops being the page you are shipping.
 */
final class IGC_I18N_Editor {

	private const PARAM = 'igc_translate';
	private const NONCE = 'igc_i18n_editor';

	public static function init(): void {
		add_action( 'admin_bar_menu', array( self::class, 'admin_bar' ), 100 );
		add_action( 'wp_footer', array( self::class, 'render' ), 999 );
		add_action( 'wp_ajax_igc_i18n_lookup', array( self::class, 'lookup' ) );
		add_action( 'wp_ajax_igc_i18n_save', array( self::class, 'save' ) );
	}

	public static function can_edit(): bool {
		/**
		 * Who may translate. Editors and administrators by default, so running the
		 * site's words does not require a full administrator account.
		 */
		return current_user_can( apply_filters( 'igc_i18n_capability', 'edit_others_posts' ) );
	}

	/**
	 * Language being translated into, or '' when the editor is not active. The
	 * editor only ever runs on a default-language page: the text on it is the
	 * source you are translating from.
	 */
	public static function target(): string {
		static $target = null;

		if ( null !== $target ) {
			return $target;
		}

		$target = '';

		if ( ! IGC_I18N::is_enabled() || is_admin() || ! IGC_I18N::is_default() || ! self::can_edit() ) {
			return $target;
		}

		$requested = isset( $_GET[ self::PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ self::PARAM ] ) ) : '';

		if ( isset( IGC_I18N::secondary_languages()[ $requested ] ) ) {
			$target = $requested;
		}

		return $target;
	}

	public static function admin_bar( $bar ): void {
		if ( is_admin() || ! IGC_I18N::is_enabled() || ! IGC_I18N::is_default() || ! self::can_edit() ) {
			return;
		}

		foreach ( IGC_I18N::secondary_languages() as $code => $language ) {
			$bar->add_node(
				array(
					'id'    => 'igc-translate-' . $code,
					'title' => sprintf(
						/* translators: %s: language label. */
						__( 'Translate page to %s', 'igc-builder' ),
						$language['label']
					),
					'href'  => add_query_arg( self::PARAM, $code ),
					'meta'  => array( 'title' => $language['name'] ),
				)
			);
		}
	}

	private static function guard(): void {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! self::can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to edit translations.', 'igc-builder' ) ), 403 );
		}
	}

	private static function request_language(): string {
		$language = isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : '';

		if ( ! isset( IGC_I18N::secondary_languages()[ $language ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown target language.', 'igc-builder' ) ), 400 );
		}

		return $language;
	}

	/**
	 * The browser reports every string it found on the page; we answer with the
	 * translation each one currently has.
	 */
	public static function lookup(): void {
		self::guard();

		$language = self::request_language();
		$sources  = isset( $_POST['sources'] ) ? (array) wp_unslash( $_POST['sources'] ) : array();
		$result   = array();

		foreach ( array_slice( $sources, 0, 2000 ) as $source ) {
			$source = IGC_I18N_Store::normalize( (string) $source );

			if ( '' !== $source ) {
				$result[ $source ] = IGC_I18N_Store::lookup( $language, $source );
			}
		}

		wp_send_json_success( array( 'translations' => $result ) );
	}

	public static function save(): void {
		self::guard();

		$language    = self::request_language();
		$source      = isset( $_POST['source'] ) ? (string) wp_unslash( $_POST['source'] ) : '';
		$translation = isset( $_POST['translation'] ) ? (string) wp_unslash( $_POST['translation'] ) : '';
		$saved       = IGC_I18N_Store::save( $language, $source, $translation );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'source' => IGC_I18N_Store::normalize( $source ),
				'text'   => IGC_I18N_Store::lookup( $language, $source ),
			)
		);
	}

	public static function render(): void {
		$target = self::target();

		if ( '' === $target ) {
			return;
		}

		$languages = IGC_I18N::languages();

		wp_enqueue_style( 'igc-i18n-editor', IGC_BUILDER_URL . 'assets/i18n-editor.css', array(), IGC_BUILDER_VERSION );
		wp_enqueue_script( 'igc-i18n-editor', IGC_BUILDER_URL . 'assets/i18n-editor.js', array(), IGC_BUILDER_VERSION, true );

		wp_localize_script(
			'igc-i18n-editor',
			'igcI18nEditor',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( self::NONCE ),
				'language'   => $target,
				'name'       => $languages[ $target ]['name'] ?? $target,
				'skipTags'   => array( 'SCRIPT', 'STYLE', 'SVG', 'CANVAS', 'TEXTAREA', 'CODE', 'PRE', 'NOSCRIPT' ),
				'attributes' => array( 'aria-label', 'alt', 'placeholder', 'title' ),
				'previewUrl' => IGC_I18N::alternate_url( $target ),
				'exitUrl'    => remove_query_arg( self::PARAM ),
				'strings'    => array(
					'panel'       => __( 'Translation', 'igc-builder' ),
					'search'      => __( 'Search this page', 'igc-builder' ),
					'source'      => __( 'Original', 'igc-builder' ),
					'save'        => __( 'Save', 'igc-builder' ),
					'clear'       => __( 'Clear', 'igc-builder' ),
					'exit'        => __( 'Exit', 'igc-builder' ),
					'preview'     => __( 'Preview', 'igc-builder' ),
					'saving'      => __( 'Saving...', 'igc-builder' ),
					'saved'       => __( 'Saved.', 'igc-builder' ),
					'failed'      => __( 'Could not save.', 'igc-builder' ),
					'offline'     => __( 'No connection. Try again.', 'igc-builder' ),
					'untranslated'=> __( 'not translated', 'igc-builder' ),
					'empty'       => __( 'No text found.', 'igc-builder' ),
				),
			)
		);
	}
}
