<?php
defined( 'ABSPATH' ) || exit;

final class IGC_Admin {
	private const CODE_TYPES = array( 'igc_code_block', 'igc_theme_template', 'igc_php_snippet' );
	private const TOKEN_GROUPS = array(
		'colors'     => array(
			'background' => array( 'Background', '#f5f2ec' ),
			'text'       => array( 'Text', '#1d1c19' ),
			'accent'     => array( 'Accent', '#9a4d35' ),
			'muted'      => array( 'Muted', '#77736d' ),
			'border'     => array( 'Border', '#d8d2c8' ),
		),
		'typography' => array(
			'body_font'    => array( 'Body font stack', 'Arial, sans-serif' ),
			'heading_font' => array( 'Heading font stack', 'Arial, sans-serif' ),
			'base_size'    => array( 'Base font size', '16px' ),
			'line_height'  => array( 'Body line height', '1.5' ),
		),
		'layout'     => array(
			'content_width' => array( 'Content width', '900px' ),
			'wide_width'    => array( 'Wide width', '1440px' ),
			'space'         => array( 'Base spacing unit', '8px' ),
			'radius'        => array( 'Default radius', '0px' ),
		),
	);

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'add_meta_boxes', array( self::class, 'meta_boxes' ) );
		add_action( 'save_post', array( self::class, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'editor_assets' ) );
		add_action( 'admin_notices', array( self::class, 'admin_notices' ) );
		add_filter( 'admin_body_class', array( self::class, 'body_class' ) );
		add_filter( 'wp_post_revision_meta_keys', array( self::class, 'revision_meta_keys' ) );
		add_action( 'admin_bar_menu', array( self::class, 'admin_bar_clear_cache' ), 100 );
		add_action( 'admin_post_igc_clear_cache', array( self::class, 'clear_cache' ) );
	}

	public static function admin_bar_clear_cache( WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'igc-clear-cache',
				'title' => '<span class="ab-icon dashicons dashicons-image-rotate" style="top:2px;"></span>' . esc_html__( 'Clear Cache', 'igc-builder' ),
				'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=igc_clear_cache' ), 'igc_clear_cache' ),
				'meta'  => array( 'title' => __( 'Flush the object cache, Site Studio transients and any detected caching plugin', 'igc-builder' ) ),
			)
		);
	}

	public static function clear_cache(): void {
		if ( ! current_user_can( 'manage_options' )
			|| ! isset( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'igc_clear_cache' )
		) {
			wp_die( esc_html__( 'Cache clear was not authorised.', 'igc-builder' ) );
		}

		wp_cache_flush();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_igc\_%' OR option_name LIKE '\_transient\_timeout\_igc\_%'" );

		foreach ( array( 'rocket_clean_domain', 'w3tc_flush_all', 'wp_cache_clear_cache', 'sg_cachepress_purge_cache', 'wpo_cache_flush', 'rocket_clean_minify' ) as $function ) {
			if ( function_exists( $function ) ) {
				call_user_func( $function );
			}
		}
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
		}

		do_action( 'igc_clear_cache' );

		wp_safe_redirect( add_query_arg( 'igc_cache_cleared', '1', wp_get_referer() ?: admin_url() ) );
		exit;
	}

	public static function menu(): void {
		add_menu_page(
			__( 'Site Studio', 'igc-builder' ),
			__( 'Site Studio', 'igc-builder' ),
			'manage_options',
			'igc-builder',
			array( self::class, 'dashboard' ),
			'dashicons-layout',
			58
		);

		add_submenu_page(
			'igc-builder',
			__( 'Global Styles', 'igc-builder' ),
			__( 'Global Styles', 'igc-builder' ),
			'manage_options',
			'igc-global-css',
			array( self::class, 'global_css' )
		);

		add_submenu_page(
			'igc-builder',
			__( 'Global Scripts', 'igc-builder' ),
			__( 'Global Scripts', 'igc-builder' ),
			'manage_options',
			'igc-global-scripts',
			array( self::class, 'global_scripts' )
		);

		add_submenu_page(
			'igc-builder',
			__( 'Settings', 'igc-builder' ),
			__( 'Settings', 'igc-builder' ),
			'manage_options',
			'igc-settings',
			array( self::class, 'settings' )
		);
	}

	public static function dashboard(): void {
		$items = array(
			array(
				'label'       => __( 'Visual Pages', 'igc-builder' ),
				'description' => __( 'Live responsive HTML canvas for widgets, shortcodes and complete AI-generated pages.', 'igc-builder' ),
				'count'       => self::published_count( 'igc_canvas' ),
				'url'         => admin_url( 'admin.php?page=igc-visual-builder' ),
				'action'      => __( 'Open visual builder', 'igc-builder' ),
			),
			array(
				'label'       => __( 'Reusable Blocks', 'igc-builder' ),
				'description' => __( 'HTML, CSS and JavaScript components available through a shortcode.', 'igc-builder' ),
				'count'       => self::published_count( 'igc_code_block' ),
				'url'         => admin_url( 'edit.php?post_type=igc_code_block' ),
				'action'      => __( 'Manage blocks', 'igc-builder' ),
			),
			array(
				'label'       => __( 'Theme Templates', 'igc-builder' ),
				'description' => __( 'Headers, footers, pages, archives and WooCommerce layouts.', 'igc-builder' ),
				'count'       => self::published_count( 'igc_theme_template' ),
				'url'         => admin_url( 'edit.php?post_type=igc_theme_template' ),
				'action'      => __( 'Manage templates', 'igc-builder' ),
			),
			array(
				'label'       => __( 'PHP Snippets', 'igc-builder' ),
				'description' => __( 'Small, guarded WordPress customisations with an emergency safe mode.', 'igc-builder' ),
				'count'       => self::published_count( 'igc_php_snippet' ),
				'url'         => admin_url( 'edit.php?post_type=igc_php_snippet' ),
				'action'      => __( 'Manage snippets', 'igc-builder' ),
			),
		);
		$theme = wp_get_theme();
		?>
		<div class="wrap igc-admin">
			<section class="igc-hero">
				<div>
					<span class="igc-eyebrow"><?php esc_html_e( 'LIGHTWEIGHT WORDPRESS WORKSPACE', 'igc-builder' ); ?></span>
					<h1><?php esc_html_e( 'Site Studio', 'igc-builder' ); ?></h1>
					<p><?php esc_html_e( 'Build the visual system once, then compose the site with clean reusable code.', 'igc-builder' ); ?></p>
				</div>
				<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=igc-visual-builder&new=1' ) ); ?>"><?php esc_html_e( 'Create a visual page', 'igc-builder' ); ?></a>
			</section>

			<div class="igc-grid igc-grid--three">
				<?php foreach ( $items as $item ) : ?>
					<article class="igc-card">
						<div class="igc-card__count"><?php echo esc_html( (string) $item['count'] ); ?></div>
						<h2><?php echo esc_html( $item['label'] ); ?></h2>
						<p><?php echo esc_html( $item['description'] ); ?></p>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['action'] ); ?> →</a>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="igc-grid igc-grid--two">
				<section class="igc-panel">
					<h2><?php esc_html_e( 'Start a site', 'igc-builder' ); ?></h2>
					<ol class="igc-checklist">
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=igc-global-css' ) ); ?>"><?php esc_html_e( 'Define the global design system', 'igc-builder' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=igc-global-scripts' ) ); ?>"><?php esc_html_e( 'Add global scripts and external libraries', 'igc-builder' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=igc_theme_template' ) ); ?>"><?php esc_html_e( 'Create header and footer templates', 'igc-builder' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=igc-visual-builder&new=1' ) ); ?>"><?php esc_html_e( 'Build or import the first visual page', 'igc-builder' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=igc_code_block' ) ); ?>"><?php esc_html_e( 'Add reusable site components', 'igc-builder' ); ?></a></li>
						<li><?php esc_html_e( 'Build page-specific templates and test responsive states', 'igc-builder' ); ?></li>
					</ol>
				</section>
				<section class="igc-panel">
					<h2><?php esc_html_e( 'Environment', 'igc-builder' ); ?></h2>
					<dl class="igc-status">
						<div><dt><?php esc_html_e( 'Plugin', 'igc-builder' ); ?></dt><dd>v<?php echo esc_html( IGC_BUILDER_VERSION ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Active theme', 'igc-builder' ); ?></dt><dd><?php echo esc_html( $theme->get( 'Name' ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Site Mode', 'igc-builder' ); ?></dt><dd><?php echo get_option( 'igc_site_mode', false ) ? esc_html__( 'On', 'igc-builder' ) : esc_html__( 'Off', 'igc-builder' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'WooCommerce', 'igc-builder' ); ?></dt><dd><?php echo class_exists( 'WooCommerce' ) ? esc_html__( 'Connected', 'igc-builder' ) : esc_html__( 'Not active', 'igc-builder' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'PHP safe mode', 'igc-builder' ); ?></dt><dd><?php echo defined( 'IGC_SAFE_MODE' ) && IGC_SAFE_MODE ? esc_html__( 'On', 'igc-builder' ) : esc_html__( 'Off', 'igc-builder' ); ?></dd></div>
					</dl>
				</section>
			</div>
		</div>
		<?php
	}

	public static function settings(): void {
		if ( isset( $_POST['igc_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_settings_nonce'] ) ), 'igc_save_settings' ) && current_user_can( 'manage_options' ) ) {
			update_option( 'igc_site_mode', isset( $_POST['igc_site_mode'] ) ? 1 : 0, false );
			update_option( 'igc_remove_emoji', isset( $_POST['igc_remove_emoji'] ) ? 1 : 0, false );
			add_settings_error( 'igc_settings', 'saved', __( 'Site Studio settings saved.', 'igc-builder' ), 'success' );
		}

		settings_errors( 'igc_settings' );
		?>
		<div class="wrap igc-admin">
			<section class="igc-page-heading">
				<div><span class="igc-eyebrow"><?php esc_html_e( 'FRAMEWORK', 'igc-builder' ); ?></span><h1><?php esc_html_e( 'Settings', 'igc-builder' ); ?></h1></div>
				<p><?php esc_html_e( 'Choose how deeply Site Studio integrates with the active WordPress theme.', 'igc-builder' ); ?></p>
			</section>
			<form method="post" class="igc-styles-form">
				<?php wp_nonce_field( 'igc_save_settings', 'igc_settings_nonce' ); ?>
				<section class="igc-panel igc-setting-row">
					<div><h2><?php esc_html_e( 'Site Mode', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Use published Site Studio templates as the complete front-end shell. Pages without a matching template continue to use the active theme.', 'igc-builder' ); ?></p></div>
					<label class="igc-switch"><input type="checkbox" name="igc_site_mode" value="1" <?php checked( (bool) get_option( 'igc_site_mode', false ) ); ?>><span><?php esc_html_e( 'Enable', 'igc-builder' ); ?></span></label>
				</section>
				<section class="igc-panel igc-setting-row">
					<div><h2><?php esc_html_e( 'Remove WordPress emoji assets', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Avoid the extra emoji script and style requests when the site does not need them.', 'igc-builder' ); ?></p></div>
					<label class="igc-switch"><input type="checkbox" name="igc_remove_emoji" value="1" <?php checked( (bool) get_option( 'igc_remove_emoji', false ) ); ?>><span><?php esc_html_e( 'Enable', 'igc-builder' ); ?></span></label>
				</section>
				<div class="igc-notice"><strong><?php esc_html_e( 'Recovery:', 'igc-builder' ); ?></strong> <?php esc_html_e( 'define IGC_SAFE_MODE as true in wp-config.php to bypass Site Mode and all PHP snippets.', 'igc-builder' ); ?></div>
				<div class="igc-savebar"><?php submit_button( __( 'Save Settings', 'igc-builder' ), 'primary', 'submit', false ); ?></div>
			</form>
		</div>
		<?php
	}

	public static function global_css(): void {
		if ( isset( $_POST['igc_global_css_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_global_css_nonce'] ) ), 'igc_save_global_css' ) ) {
			if ( current_user_can( 'unfiltered_html' ) ) {
				$tokens = self::default_tokens();
				foreach ( self::TOKEN_GROUPS as $group => $fields ) {
					foreach ( $fields as $key => $definition ) {
						$tokens[ $group ][ $key ] = self::sanitize_token( wp_unslash( $_POST['igc_tokens'][ $group ][ $key ] ?? $definition[1] ) );
					}
				}
				update_option( 'igc_design_tokens', $tokens, false );
				update_option( 'igc_external_stylesheet', esc_url_raw( wp_unslash( $_POST['igc_external_stylesheet'] ?? '' ) ), false );
				update_option( 'igc_global_css', wp_unslash( $_POST['igc_global_css'] ?? '' ), false );
				add_settings_error( 'igc_global_css', 'saved', __( 'Global styles saved.', 'igc-builder' ), 'success' );
			}
		}

		$tokens = wp_parse_args( (array) get_option( 'igc_design_tokens', array() ), self::default_tokens() );
		settings_errors( 'igc_global_css' );
		?>
		<div class="wrap igc-admin">
			<section class="igc-page-heading">
				<div><span class="igc-eyebrow"><?php esc_html_e( 'DESIGN SYSTEM', 'igc-builder' ); ?></span><h1><?php esc_html_e( 'Global Styles', 'igc-builder' ); ?></h1></div>
				<p><?php esc_html_e( 'These values become CSS custom properties available everywhere on the front end.', 'igc-builder' ); ?></p>
			</section>
			<form method="post" class="igc-styles-form">
				<?php wp_nonce_field( 'igc_save_global_css', 'igc_global_css_nonce' ); ?>
				<div class="igc-grid igc-grid--three igc-token-groups">
					<?php foreach ( self::TOKEN_GROUPS as $group => $fields ) : ?>
						<section class="igc-panel">
							<h2><?php echo esc_html( ucfirst( $group ) ); ?></h2>
							<?php foreach ( $fields as $key => $definition ) : ?>
								<label class="igc-field">
									<span><?php echo esc_html( $definition[0] ); ?></span>
									<input type="text" name="igc_tokens[<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $tokens[ $group ][ $key ] ?? $definition[1] ); ?>">
								</label>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>

				<section class="igc-panel">
					<h2><?php esc_html_e( 'External stylesheet', 'igc-builder' ); ?></h2>
					<p><?php esc_html_e( 'Optional. Load a complete CSS file from your theme, CDN or another public URL.', 'igc-builder' ); ?></p>
					<input type="url" name="igc_external_stylesheet" class="large-text" placeholder="https://example.com/site.css" value="<?php echo esc_attr( (string) get_option( 'igc_external_stylesheet', '' ) ); ?>">
				</section>

				<section class="igc-panel igc-code-panel">
					<div class="igc-panel__heading"><div><h2><?php esc_html_e( 'Custom CSS', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Use the tokens below or paste the full site stylesheet here.', 'igc-builder' ); ?></p></div><code>var(--studio-color-accent)</code></div>
					<textarea id="igc-global-css" name="igc_global_css" class="large-text code" rows="28"><?php echo esc_textarea( (string) get_option( 'igc_global_css', '' ) ); ?></textarea>
				</section>
				<div class="igc-savebar"><?php submit_button( __( 'Save Global Styles', 'igc-builder' ), 'primary', 'submit', false ); ?></div>
			</form>
		</div>
		<?php
	}

	public static function global_scripts(): void {
		if ( isset( $_POST['igc_global_scripts_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_global_scripts_nonce'] ) ), 'igc_save_global_scripts' ) && current_user_can( 'unfiltered_html' ) ) {
			$urls = preg_split( '/\R/', wp_unslash( $_POST['igc_external_scripts'] ?? '' ) ) ?: array();
			$urls = array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', $urls ) ) ) );
			update_option( 'igc_external_scripts', $urls, false );
			update_option( 'igc_bundled_lenis', isset( $_POST['igc_bundled_lenis'] ) ? 1 : 0, false );
			update_option( 'igc_global_js', wp_unslash( $_POST['igc_global_js'] ?? '' ), false );
			add_settings_error( 'igc_global_scripts', 'saved', __( 'Global scripts saved.', 'igc-builder' ), 'success' );
		}

		$urls = (array) get_option( 'igc_external_scripts', array() );
		settings_errors( 'igc_global_scripts' );
		?>
		<div class="wrap igc-admin">
			<section class="igc-page-heading">
				<div><span class="igc-eyebrow"><?php esc_html_e( 'SITE RUNTIME', 'igc-builder' ); ?></span><h1><?php esc_html_e( 'Global Scripts', 'igc-builder' ); ?></h1></div>
				<p><?php esc_html_e( 'Load shared JavaScript once across the front end, without adding script tags to every page.', 'igc-builder' ); ?></p>
			</section>
			<form method="post" class="igc-styles-form">
				<?php wp_nonce_field( 'igc_save_global_scripts', 'igc_global_scripts_nonce' ); ?>
				<section class="igc-panel igc-setting-row">
					<div><h2><?php esc_html_e( 'Bundled Lenis smooth scroll', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Load the pinned Lenis 1.3.26 JavaScript and recommended CSS locally from Site Studio. No CDN dependency.', 'igc-builder' ); ?></p></div>
					<label class="igc-switch"><input type="checkbox" name="igc_bundled_lenis" value="1" <?php checked( (bool) get_option( 'igc_bundled_lenis', false ) ); ?>><span><?php esc_html_e( 'Enable', 'igc-builder' ); ?></span></label>
				</section>
				<section class="igc-panel">
					<h2><?php esc_html_e( 'External script URLs', 'igc-builder' ); ?></h2>
					<p><?php esc_html_e( 'One HTTPS URL per line. Scripts load in the footer with defer enabled.', 'igc-builder' ); ?></p>
					<textarea name="igc_external_scripts" class="large-text code" rows="6" placeholder="https://cdn.example.com/library.min.js"><?php echo esc_textarea( implode( "\n", $urls ) ); ?></textarea>
				</section>
				<section class="igc-panel igc-code-panel">
					<div class="igc-panel__heading"><div><h2><?php esc_html_e( 'Global JavaScript', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Runs after the external scripts at the end of the page. Do not include script tags.', 'igc-builder' ); ?></p></div><code>DOMContentLoaded</code></div>
					<textarea id="igc-global-js" name="igc_global_js" class="large-text code" rows="28"><?php echo esc_textarea( (string) get_option( 'igc_global_js', '' ) ); ?></textarea>
				</section>
				<div class="igc-notice"><strong><?php esc_html_e( 'Safety:', 'igc-builder' ); ?></strong> <?php esc_html_e( 'Test new runtime code in the Visual Builder before removing the previous integration.', 'igc-builder' ); ?></div>
				<div class="igc-savebar"><?php submit_button( __( 'Save Global Scripts', 'igc-builder' ), 'primary', 'submit', false ); ?></div>
			</form>
		</div>
		<?php
	}

	public static function default_tokens(): array {
		$tokens = array();
		foreach ( self::TOKEN_GROUPS as $group => $fields ) {
			foreach ( $fields as $key => $definition ) {
				$tokens[ $group ][ $key ] = $definition[1];
			}
		}
		return $tokens;
	}

	private static function sanitize_token( string $value ): string {
		return trim( str_replace( array( ';', '{', '}' ), '', sanitize_text_field( $value ) ) );
	}

	private static function published_count( string $post_type ): int {
		$count = wp_count_posts( $post_type );
		return isset( $count->publish ) ? (int) $count->publish : 0;
	}

	public static function meta_boxes(): void {
		foreach ( array( 'igc_code_block', 'igc_theme_template' ) as $type ) {
			add_meta_box( 'igc-code', __( 'HTML / CSS / JavaScript', 'igc-builder' ), array( self::class, 'code_box' ), $type, 'normal', 'high' );
		}

		add_meta_box( 'igc-template-settings', __( 'Template Settings', 'igc-builder' ), array( self::class, 'template_box' ), 'igc_theme_template', 'side', 'high' );
		add_meta_box( 'igc-php', __( 'PHP Code', 'igc-builder' ), array( self::class, 'php_box' ), 'igc_php_snippet', 'normal', 'high' );
		add_meta_box( 'igc-php-settings', __( 'Snippet Settings', 'igc-builder' ), array( self::class, 'php_settings_box' ), 'igc_php_snippet', 'side', 'high' );
	}

	public static function code_box( WP_Post $post ): void {
		wp_nonce_field( 'igc_save_code', 'igc_code_nonce' );
		self::textarea( 'igc_html', 'HTML', (string) get_post_meta( $post->ID, '_igc_html', true ), 18 );
		self::textarea( 'igc_css', 'CSS', (string) get_post_meta( $post->ID, '_igc_css', true ), 14 );
		self::textarea( 'igc_js', 'JavaScript (without <script> tags)', (string) get_post_meta( $post->ID, '_igc_js', true ), 14 );
	}

	public static function template_box( WP_Post $post ): void {
		$location = (string) get_post_meta( $post->ID, '_igc_location', true );
		$priority = (int) get_post_meta( $post->ID, '_igc_priority', true );
		$include  = (string) get_post_meta( $post->ID, '_igc_include', true );
		$locations = array(
			'header'          => 'Header',
			'footer'          => 'Footer',
			'front_page'      => 'Front Page',
			'page'            => 'Default Page',
			'single'          => 'Default Post',
			'search'          => 'Search Results',
			'archive'         => 'Archive',
			'single_product'  => 'Single Product',
			'product_archive' => 'Product Archive',
			'cart'            => 'Cart',
			'checkout'        => 'Checkout',
			'404'             => '404',
		);
		?>
		<p><label for="igc-location"><strong><?php esc_html_e( 'Location', 'igc-builder' ); ?></strong></label></p>
		<select id="igc-location" name="igc_location" class="widefat">
			<option value=""><?php esc_html_e( 'Select location', 'igc-builder' ); ?></option>
			<?php foreach ( $locations as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $location, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><label for="igc-priority"><strong><?php esc_html_e( 'Priority', 'igc-builder' ); ?></strong></label></p>
		<input id="igc-priority" name="igc_priority" type="number" class="widefat" value="<?php echo esc_attr( (string) $priority ); ?>">
		<p><label for="igc-include"><strong><?php esc_html_e( 'Only on IDs or slugs', 'igc-builder' ); ?></strong></label></p>
		<input id="igc-include" name="igc_include" type="text" class="widefat" placeholder="contact, 42" value="<?php echo esc_attr( $include ); ?>">
		<p class="description"><?php esc_html_e( 'Optional comma-separated list. Leave empty to apply everywhere in this location.', 'igc-builder' ); ?></p>
		<?php
	}

	public static function php_box( WP_Post $post ): void {
		wp_nonce_field( 'igc_save_code', 'igc_code_nonce' );
		$status = (string) get_post_meta( $post->ID, '_igc_validation_status', true );
		$error  = (string) get_post_meta( $post->ID, '_igc_last_error', true );
		if ( 'passed' === $status ) {
			echo '<div class="notice notice-success inline"><p><strong>' . esc_html__( 'Safety check passed.', 'igc-builder' ) . '</strong> ' . esc_html__( 'This saved version has valid PHP syntax and passed the blocked-function scan.', 'igc-builder' ) . '</p></div>';
		} elseif ( 'failed' === $status && $error ) {
			echo '<div class="notice notice-error inline"><p><strong>' . esc_html__( 'Snippet disabled:', 'igc-builder' ) . '</strong> ' . esc_html( $error ) . '</p></div>';
		}
		self::textarea( 'igc_php', 'PHP (without <?php)', (string) get_post_meta( $post->ID, '_igc_php', true ), 28 );
	}

	public static function php_settings_box( WP_Post $post ): void {
		$enabled = (bool) get_post_meta( $post->ID, '_igc_enabled', true );
		$scope   = (string) get_post_meta( $post->ID, '_igc_scope', true ) ?: 'everywhere';
		?>
		<p><label><input name="igc_enabled" type="checkbox" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Save and activate after validation', 'igc-builder' ); ?></label></p>
		<p><label for="igc-scope"><strong><?php esc_html_e( 'Scope', 'igc-builder' ); ?></strong></label></p>
		<select id="igc-scope" name="igc_scope" class="widefat">
			<option value="everywhere" <?php selected( $scope, 'everywhere' ); ?>>Everywhere</option>
			<option value="frontend" <?php selected( $scope, 'frontend' ); ?>>Frontend only</option>
			<option value="admin" <?php selected( $scope, 'admin' ); ?>>Admin only</option>
		</select>
		<p class="description"><?php esc_html_e( 'Emergency bypass: define IGC_SAFE_MODE as true in wp-config.php.', 'igc-builder' ); ?></p>
		<?php
	}

	private static function textarea( string $name, string $label, string $value, int $rows ): void {
		printf( '<p><label for="%1$s"><strong>%2$s</strong></label></p>', esc_attr( $name ), esc_html( $label ) );
		printf( '<textarea id="%1$s" name="%1$s" class="large-text code" rows="%2$d">%3$s</textarea>', esc_attr( $name ), $rows, esc_textarea( $value ) );
	}

	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! in_array( $post->post_type, self::CODE_TYPES, true ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['igc_code_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_code_nonce'] ) ), 'igc_save_code' ) || ! current_user_can( 'unfiltered_html' ) ) {
			return;
		}

		foreach ( array( 'html', 'css', 'js' ) as $field ) {
			$key = 'igc_' . $field;
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, '_igc_' . $field, wp_unslash( $_POST[ $key ] ) );
			}
		}

		if ( 'igc_theme_template' === $post->post_type ) {
			update_post_meta( $post_id, '_igc_location', sanitize_key( wp_unslash( $_POST['igc_location'] ?? '' ) ) );
			update_post_meta( $post_id, '_igc_priority', (int) ( $_POST['igc_priority'] ?? 0 ) );
			update_post_meta( $post_id, '_igc_include', sanitize_text_field( wp_unslash( $_POST['igc_include'] ?? '' ) ) );
		}

		if ( 'igc_php_snippet' === $post->post_type ) {
			$code       = wp_unslash( $_POST['igc_php'] ?? '' );
			$old_code   = (string) get_post_meta( $post_id, '_igc_php', true );
			$changed    = ! hash_equals( hash( 'sha256', $old_code ), hash( 'sha256', $code ) );
			$validation = IGC_Snippet_Validator::validate( $code );
			if ( is_wp_error( $validation ) ) {
				update_post_meta( $post_id, '_igc_enabled', 0 );
				update_post_meta( $post_id, '_igc_validation_status', 'failed' );
				update_post_meta( $post_id, '_igc_last_error', $validation->get_error_message() );
				set_transient( 'igc_snippet_notice_' . get_current_user_id(), $validation->get_error_message(), 60 );
				return;
			}

			update_post_meta( $post_id, '_igc_php', $code );
			update_post_meta( $post_id, '_igc_scope', sanitize_key( wp_unslash( $_POST['igc_scope'] ?? 'everywhere' ) ) );
			$enable = isset( $_POST['igc_enabled'] ) && ! $changed;
			update_post_meta( $post_id, '_igc_enabled', $enable ? 1 : 0 );
			update_post_meta( $post_id, '_igc_validation_status', 'passed' );
			update_post_meta( $post_id, '_igc_last_error', '' );
			update_post_meta( $post_id, '_igc_last_validated', current_time( 'mysql', true ) );
			if ( $changed && isset( $_POST['igc_enabled'] ) ) {
				set_transient( 'igc_snippet_stage_' . get_current_user_id(), __( 'The new code passed validation and was saved disabled. Review it, then save once more to activate the unchanged version.', 'igc-builder' ), 60 );
			}
			wp_save_post_revision( $post_id );
		}
	}

	public static function admin_notices(): void {
		if ( isset( $_GET['igc_cache_cleared'] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Cache cleared.', 'igc-builder' ) );
		}

		$key     = 'igc_snippet_notice_' . get_current_user_id();
		$message = get_transient( $key );
		if ( $message ) {
			delete_transient( $key );
			printf( '<div class="notice notice-error is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>', esc_html__( 'Snippet was not saved or activated.', 'igc-builder' ), esc_html( (string) $message ) );
		}

		$stage_key = 'igc_snippet_stage_' . get_current_user_id();
		$stage     = get_transient( $stage_key );
		if ( $stage ) {
			delete_transient( $stage_key );
			printf( '<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>', esc_html__( 'Staged activation.', 'igc-builder' ), esc_html( (string) $stage ) );
		}
	}

	public static function revision_meta_keys( array $keys ): array {
		return array_values(
			array_unique(
				array_merge(
					$keys,
					array( '_igc_html', '_igc_css', '_igc_js', '_igc_php', '_igc_scope', '_igc_enabled', '_igc_validation_status' )
				)
			)
		);
	}

	public static function editor_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( str_starts_with( (string) ( $_GET['page'] ?? '' ), 'igc-' ) || in_array( $screen->post_type, self::CODE_TYPES, true ) ) {
			wp_enqueue_style( 'igc-admin', IGC_BUILDER_URL . 'assets/admin.css', array(), IGC_BUILDER_VERSION );
		}

		if ( 'igc-global-css' === ( $_GET['page'] ?? '' ) ) {
			self::init_editor( 'igc-global-css', 'text/css' );
			return;
		}

		if ( 'igc-global-scripts' === ( $_GET['page'] ?? '' ) ) {
			self::init_editor( 'igc-global-js', 'application/javascript' );
			return;
		}

		if ( ! in_array( $screen->post_type, self::CODE_TYPES, true ) ) {
			return;
		}

		self::init_editor( 'igc_html', 'text/html' );
		self::init_editor( 'igc_css', 'text/css' );
		self::init_editor( 'igc_js', 'application/javascript' );
		self::init_editor( 'igc_php', 'application/x-httpd-php' );
	}

	public static function body_class( string $classes ): string {
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
		return str_starts_with( $page, 'igc-' ) ? $classes . ' igc-studio-admin' : $classes;
	}

	private static function init_editor( string $id, string $type ): void {
		$settings = wp_enqueue_code_editor( array( 'type' => $type ) );
		if ( ! $settings ) {
			return;
		}

		wp_add_inline_script(
			'code-editor',
			'jQuery(function(){var el=document.getElementById(' . wp_json_encode( $id ) . ');if(el){wp.codeEditor.initialize(el,' . wp_json_encode( $settings ) . ');}});'
		);
	}
}
