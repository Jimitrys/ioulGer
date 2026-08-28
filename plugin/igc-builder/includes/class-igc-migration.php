<?php
defined( 'ABSPATH' ) || exit;

final class IGC_Migration {
	private const PAGE_SLUGS = array( 'home', 'about', 'workshops', 'book-workshop', 'contact', 'shop', 'cart', 'checkout' );

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 25 );
		add_action( 'admin_post_igc_import_elementor_pages', array( self::class, 'import' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'igc-builder',
			__( 'Migration Pack', 'igc-builder' ),
			__( 'Migration Pack', 'igc-builder' ),
			'manage_options',
			'igc-migration',
			array( self::class, 'screen' )
		);
	}

	public static function screen(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to run migrations.', 'igc-builder' ) );
		}
		$results = get_transient( 'igc_migration_results_' . get_current_user_id() );
		delete_transient( 'igc_migration_results_' . get_current_user_id() );
		?>
		<div class="wrap igc-admin">
			<section class="igc-page-heading">
				<div><span class="igc-eyebrow">IOULIA MIGRATION PACK</span><h1><?php esc_html_e( 'Elementor page importer', 'igc-builder' ); ?></h1></div>
			</section>
			<?php if ( is_array( $results ) ) : ?>
				<div class="notice notice-success inline"><p><strong><?php esc_html_e( 'Import finished.', 'igc-builder' ); ?></strong></p><ul>
					<?php foreach ( $results as $result ) : ?><li><?php echo esc_html( $result ); ?></li><?php endforeach; ?>
				</ul></div>
			<?php endif; ?>
			<div class="igc-panel">
				<div class="igc-panel__heading"><div><h2><?php esc_html_e( 'Create safe migration drafts', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Reads HTML and Shortcode widgets from the existing Elementor pages, separates their CSS and JavaScript, and assigns each new canvas to its WordPress page.', 'igc-builder' ); ?></p></div><code>Draft only</code></div>
				<ul class="igc-checklist">
					<li><?php esc_html_e( 'Existing assigned canvases are skipped and never overwritten.', 'igc-builder' ); ?></li>
					<li><?php esc_html_e( 'Imported canvases are saved as Draft with routing disabled.', 'igc-builder' ); ?></li>
					<li><?php esc_html_e( 'Elementor and the original page data remain unchanged.', 'igc-builder' ); ?></li>
					<li><?php esc_html_e( 'Cart and Checkout receive native WooCommerce shortcode fallbacks when no widget source is found.', 'igc-builder' ); ?></li>
				</ul>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="igc_import_elementor_pages">
					<?php wp_nonce_field( 'igc_import_elementor_pages', 'igc_migration_nonce' ); ?>
					<p><button class="button button-primary button-hero" type="submit"><?php esc_html_e( 'Import remaining pages as drafts', 'igc-builder' ); ?></button></p>
				</form>
			</div>
		</div>
		<?php
	}

	public static function import(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) || ! isset( $_POST['igc_migration_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_migration_nonce'] ) ), 'igc_import_elementor_pages' ) ) {
			wp_die( esc_html__( 'Migration was not authorised.', 'igc-builder' ) );
		}

		$results = array();
		foreach ( self::PAGE_SLUGS as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( ! $page && 'home' === $slug ) {
				$page = get_post( (int) get_option( 'page_on_front' ) );
			}
			if ( ! $page ) {
				$results[] = sprintf( '%s: page not found.', $slug );
				continue;
			}
			if ( self::assigned_canvas( $page->ID ) ) {
				$results[] = sprintf( '%s: skipped, an assigned canvas already exists.', $page->post_title );
				continue;
			}

			$source = self::elementor_source( $page->ID );
			if ( '' === trim( $source ) ) {
				$source = self::fallback_source( $slug );
			}
			if ( '' === trim( $source ) ) {
				$results[] = sprintf( '%s: no HTML or Shortcode widgets found.', $page->post_title );
				continue;
			}

			$parts = self::separate_source( $source );
			$canvas_id = wp_insert_post(
				array(
					'post_type'   => 'igc_canvas',
					'post_title'  => $page->post_title,
					'post_name'   => 'migration-' . $page->post_name,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $canvas_id ) ) {
				$results[] = sprintf( '%s: %s', $page->post_title, $canvas_id->get_error_message() );
				continue;
			}
			update_post_meta( $canvas_id, '_igc_html', $parts['html'] );
			update_post_meta( $canvas_id, '_igc_css', $parts['css'] );
			update_post_meta( $canvas_id, '_igc_js', $parts['js'] );
			update_post_meta( $canvas_id, '_igc_target_page_id', $page->ID );
			update_post_meta( $canvas_id, '_igc_route_active', '0' );
			$results[] = sprintf( '%s: draft canvas #%d created.', $page->post_title, $canvas_id );
		}
		self::create_woocommerce_templates( $results );

		set_transient( 'igc_migration_results_' . get_current_user_id(), $results, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=igc-migration' ) );
		exit;
	}

	private static function assigned_canvas( int $page_id ): bool {
		return (bool) get_posts(
			array(
				'post_type'      => 'igc_canvas',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'meta_key'       => '_igc_target_page_id',
				'meta_value'     => (string) $page_id,
				'fields'         => 'ids',
			)
		);
	}

	private static function create_woocommerce_templates( array &$results ): void {
		$definitions = array(
			array( 'title' => 'Home — Assigned Canvas', 'location' => 'front_page', 'include' => '', 'priority' => 10, 'html' => '[studio_assigned_canvas page="home"]' ),
			array( 'title' => 'Migrated Pages', 'location' => 'page', 'include' => 'about,workshops,book-workshop,contact', 'priority' => 10, 'html' => '[studio_assigned_canvas page="current"]' ),
			array( 'title' => 'Shop — Assigned Canvas', 'location' => 'product_archive', 'include' => 'shop', 'priority' => 20, 'html' => '[studio_assigned_canvas page="shop"]' ),
			array( 'title' => 'Product Archives', 'location' => 'product_archive', 'include' => '', 'priority' => 10, 'html' => '[studio_woo_archive]' ),
			array( 'title' => 'Single Product', 'location' => 'single_product', 'include' => '', 'priority' => 10, 'html' => '[studio_woo_product]' ),
			array( 'title' => 'Cart — Assigned Canvas', 'location' => 'cart', 'include' => '', 'priority' => 10, 'html' => '[studio_assigned_canvas page="cart"]' ),
			array( 'title' => 'Checkout — Assigned Canvas', 'location' => 'checkout', 'include' => '', 'priority' => 10, 'html' => '[studio_assigned_canvas page="checkout"]' ),
		);

		foreach ( $definitions as $definition ) {
			$existing = get_page_by_path( sanitize_title( $definition['title'] ), OBJECT, 'igc_theme_template' );
			if ( $existing ) {
				$results[] = sprintf( '%s: template already exists.', $definition['title'] );
				continue;
			}
			$template_id = wp_insert_post(
				array(
					'post_type'   => 'igc_theme_template',
					'post_title'  => $definition['title'],
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $template_id ) ) {
				$results[] = sprintf( '%s: %s', $definition['title'], $template_id->get_error_message() );
				continue;
			}
			update_post_meta( $template_id, '_igc_html', $definition['html'] );
			update_post_meta( $template_id, '_igc_css', '' );
			update_post_meta( $template_id, '_igc_js', '' );
			update_post_meta( $template_id, '_igc_location', $definition['location'] );
			update_post_meta( $template_id, '_igc_include', $definition['include'] );
			update_post_meta( $template_id, '_igc_priority', (string) $definition['priority'] );
			$results[] = sprintf( '%s: draft template #%d created.', $definition['title'], $template_id );
		}
	}

	private static function elementor_source( int $page_id ): string {
		$raw  = (string) get_post_meta( $page_id, '_elementor_data', true );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return '';
		}
		$chunks = array();
		self::walk_elements( $data, $chunks );
		return implode( "\n\n", array_filter( $chunks ) );
	}

	private static function walk_elements( array $elements, array &$chunks ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$widget   = (string) ( $element['widgetType'] ?? '' );
			$settings = is_array( $element['settings'] ?? null ) ? $element['settings'] : array();
			if ( 'html' === $widget && isset( $settings['html'] ) ) {
				$chunks[] = (string) $settings['html'];
			} elseif ( 'shortcode' === $widget && isset( $settings['shortcode'] ) ) {
				$chunks[] = (string) $settings['shortcode'];
			}
			if ( is_array( $element['elements'] ?? null ) ) {
				self::walk_elements( $element['elements'], $chunks );
			}
		}
	}

	private static function fallback_source( string $slug ): string {
		return match ( $slug ) {
			'book-workshop' => '[ioulia_workshops]',
			'cart'          => '[woocommerce_cart]',
			'checkout'      => '[woocommerce_checkout]',
			default         => '',
		};
	}

	private static function separate_source( string $source ): array {
		$source = str_replace( 'http://' . wp_parse_url( home_url(), PHP_URL_HOST ), 'https://' . wp_parse_url( home_url(), PHP_URL_HOST ), $source );
		if ( str_contains( $source, 'document.currentScript' ) ) {
			return array( 'html' => trim( $source ), 'css' => '', 'js' => '' );
		}
		$css = array();
		$js  = array();
		$html = preg_replace_callback( '#<style\b[^>]*>(.*?)</style>#is', static function ( array $match ) use ( &$css ): string { $css[] = trim( $match[1] ); return ''; }, $source ) ?? $source;
		$html = preg_replace_callback( '#<script\b(?![^>]*\bsrc=)[^>]*>(.*?)</script>#is', static function ( array $match ) use ( &$js ): string { $js[] = trim( $match[1] ); return ''; }, $html ) ?? $html;
		return array( 'html' => trim( $html ), 'css' => implode( "\n\n", $css ), 'js' => implode( "\n\n", $js ) );
	}
}
