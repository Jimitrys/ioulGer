<?php
defined( 'ABSPATH' ) || exit;

final class IGC_Visual_Builder {
	private static bool $rendering_post_content = false;
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_post_igc_save_canvas', array( self::class, 'save' ) );
		add_action( 'wp_ajax_igc_render_canvas_preview', array( self::class, 'preview' ) );
		add_shortcode( 'studio_canvas', array( self::class, 'shortcode' ) );
		add_shortcode( 'studio_post_content', array( self::class, 'post_content_shortcode' ) );
		add_filter( 'the_content', array( self::class, 'route_content' ), 9999 );
	}

	public static function menu(): void {
		add_submenu_page(
			'igc-builder',
			__( 'Visual Builder', 'igc-builder' ),
			__( 'Visual Builder', 'igc-builder' ),
			'manage_options',
			'igc-visual-builder',
			array( self::class, 'screen' )
		);
	}

	public static function assets(): void {
		if ( 'igc-visual-builder' !== ( $_GET['page'] ?? '' ) ) {
			return;
		}

		wp_enqueue_style( 'igc-builder-ui', IGC_BUILDER_URL . 'assets/visual-builder.css', array(), IGC_BUILDER_VERSION );
		wp_enqueue_script( 'igc-builder-ui', IGC_BUILDER_URL . 'assets/visual-builder.js', array( 'jquery', 'wp-codemirror' ), IGC_BUILDER_VERSION, true );

		$editors = array(
			'html' => wp_enqueue_code_editor( array( 'type' => 'text/html' ) ),
			'css'  => wp_enqueue_code_editor( array( 'type' => 'text/css' ) ),
			'js'   => wp_enqueue_code_editor( array( 'type' => 'application/javascript' ) ),
		);
		$tokens = wp_parse_args( (array) get_option( 'igc_design_tokens', array() ), IGC_Admin::default_tokens() );
		wp_add_inline_script(
			'igc-builder-ui',
			'window.SiteStudioBuilder=' . wp_json_encode(
				array(
					'editors'      => $editors,
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'previewNonce' => wp_create_nonce( 'igc_canvas_preview' ),
					'globalCss'    => (string) get_option( 'igc_global_css', '' ),
					'externalCss'  => (string) get_option( 'igc_external_stylesheet', '' ),
					'tokens'       => $tokens,
					'widgets'      => self::widgets(),
					'previewTitle' => __( 'Rendered securely by WordPress', 'igc-builder' ),
					'previewError' => __( 'WordPress preview failed. Showing the local HTML preview instead.', 'igc-builder' ),
				)
			) . ';',
			'before'
		);
	}

	private static function widgets(): array {
		return array(
			array( 'group' => 'Layout', 'label' => 'Section', 'icon' => '▭', 'html' => "<section class=\"studio-section\">\n  <div class=\"studio-container\">\n    <h2>Section title</h2>\n    <p>Add your content here.</p>\n  </div>\n</section>" ),
			array( 'group' => 'Layout', 'label' => 'Stack', 'icon' => '☷', 'html' => "<div class=\"studio-stack\">\n  <div>First item</div>\n  <div>Second item</div>\n</div>" ),
			array( 'group' => 'Layout', 'label' => 'Grid', 'icon' => '⊞', 'html' => "<div class=\"studio-grid\">\n  <article>Grid item</article>\n  <article>Grid item</article>\n  <article>Grid item</article>\n</div>" ),
			array( 'group' => 'Content', 'label' => 'Heading', 'icon' => 'H', 'html' => '<h2 class="studio-heading">Your heading</h2>' ),
			array( 'group' => 'Content', 'label' => 'Text', 'icon' => '¶', 'html' => '<p class="studio-text">Write your content here.</p>' ),
			array( 'group' => 'Content', 'label' => 'Button', 'icon' => '↗', 'html' => '<a class="studio-button" href="#">Button label</a>' ),
			array( 'group' => 'Content', 'label' => 'Image', 'icon' => '◫', 'html' => '<img class="studio-image" src="https://placehold.co/1200x800" alt="">' ),
			array( 'group' => 'Content', 'label' => 'Divider', 'icon' => '—', 'html' => '<hr class="studio-divider">' ),
			array( 'group' => 'Dynamic', 'label' => 'Shortcode', 'icon' => '[ ]', 'html' => '[your_shortcode]' ),
			array( 'group' => 'Dynamic', 'label' => 'Studio Block', 'icon' => '◇', 'html' => '[igc_block id="block-slug"]' ),
			array( 'group' => 'Dynamic', 'label' => 'Post Content', 'icon' => 'WP', 'html' => '[studio_post_content]' ),
			array( 'group' => 'Commerce', 'label' => 'Products', 'icon' => '◎', 'html' => '[products limit="4" columns="4"]' ),
			array( 'group' => 'Commerce', 'label' => 'Cart', 'icon' => '⌁', 'html' => '[woocommerce_cart]' ),
			array( 'group' => 'Commerce', 'label' => 'Checkout', 'icon' => '✓', 'html' => '[woocommerce_checkout]' ),
			array( 'group' => 'Commerce', 'label' => 'Product Archive', 'icon' => '▦', 'html' => '[studio_woo_archive]' ),
			array( 'group' => 'Commerce', 'label' => 'Single Product', 'icon' => '◉', 'html' => '[studio_woo_product]' ),
		);
	}

	public static function screen(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to use Site Studio.', 'igc-builder' ) );
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id && ! isset( $_GET['new'] ) ) {
			self::library_screen();
			return;
		}

		$post = $id ? get_post( $id ) : null;
		if ( $id && ( ! $post || 'igc_canvas' !== $post->post_type ) ) {
			wp_die( esc_html__( 'Visual page not found.', 'igc-builder' ) );
		}

		$title  = $post ? $post->post_title : __( 'Untitled visual page', 'igc-builder' );
		$status = $post ? $post->post_status : 'draft';
		$html   = $post ? (string) get_post_meta( $id, '_igc_html', true ) : self::starter_html();
		$css    = $post ? (string) get_post_meta( $id, '_igc_css', true ) : self::starter_css();
		$js     = $post ? (string) get_post_meta( $id, '_igc_js', true ) : '';
		$target_page_id = $post ? absint( get_post_meta( $id, '_igc_target_page_id', true ) ) : 0;
		$route_active   = $post ? (bool) get_post_meta( $id, '_igc_route_active', true ) : false;
		$site_pages     = get_pages( array( 'post_status' => array( 'publish', 'private', 'draft' ), 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
		?>
		<div class="studio-builder" data-canvas-id="<?php echo esc_attr( (string) $id ); ?>">
			<form id="studio-builder-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="igc_save_canvas">
				<input type="hidden" name="canvas_id" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" id="studio-save-status" name="canvas_status" value="<?php echo esc_attr( $status ); ?>">
				<?php wp_nonce_field( 'igc_save_canvas', 'igc_canvas_nonce' ); ?>
				<header class="studio-topbar">
					<a class="studio-back" href="<?php echo esc_url( admin_url( 'admin.php?page=igc-visual-builder' ) ); ?>" aria-label="<?php esc_attr_e( 'Back to visual pages', 'igc-builder' ); ?>">←</a>
					<input class="studio-title" name="canvas_title" value="<?php echo esc_attr( $title ); ?>" aria-label="<?php esc_attr_e( 'Page title', 'igc-builder' ); ?>">
					<span id="studio-dirty-state" class="studio-state"><?php echo 'publish' === $status ? esc_html__( 'Published', 'igc-builder' ) : esc_html__( 'Draft', 'igc-builder' ); ?></span>
					<div class="studio-topbar__actions">
						<button type="button" class="studio-button-ui" data-action="import-html"><?php esc_html_e( 'Import HTML', 'igc-builder' ); ?></button>
						<button type="submit" class="studio-button-ui" data-save="draft"><?php esc_html_e( 'Save draft', 'igc-builder' ); ?></button>
						<button type="submit" class="studio-button-ui studio-button-ui--primary" data-save="publish"><?php esc_html_e( 'Publish', 'igc-builder' ); ?></button>
					</div>
				</header>
				<div class="studio-routebar">
					<div>
						<label for="studio-target-page"><strong><?php esc_html_e( 'WordPress page', 'igc-builder' ); ?></strong></label>
						<select id="studio-target-page" name="canvas_target_page">
							<option value="0"><?php esc_html_e( 'Not assigned', 'igc-builder' ); ?></option>
							<?php foreach ( $site_pages as $site_page ) : ?>
								<option value="<?php echo esc_attr( (string) $site_page->ID ); ?>" <?php selected( $target_page_id, $site_page->ID ); ?>><?php echo esc_html( $site_page->post_title . ' — /' . $site_page->post_name . '/' ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<label class="studio-route-toggle"><input type="checkbox" name="canvas_route_active" value="1" <?php checked( $route_active ); ?>> <?php esc_html_e( 'Use this canvas on the assigned page', 'igc-builder' ); ?></label>
					<?php if ( $id && $target_page_id ) : ?>
						<a class="studio-button-ui" href="<?php echo esc_url( add_query_arg( 'studio_preview', $id, get_permalink( $target_page_id ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview on page ↗', 'igc-builder' ); ?></a>
					<?php endif; ?>
					<small><?php esc_html_e( 'Preview is admin-only. The live page changes only when the canvas is published and the route is enabled.', 'igc-builder' ); ?></small>
				</div>

				<div class="studio-workspace">
					<aside class="studio-library">
						<div class="studio-panel-title"><strong><?php esc_html_e( 'Insert', 'igc-builder' ); ?></strong><span>+</span></div>
						<input id="studio-widget-search" class="studio-search" type="search" placeholder="<?php esc_attr_e( 'Search widgets…', 'igc-builder' ); ?>">
						<div id="studio-widget-library" class="studio-widget-library"></div>
					</aside>

					<section class="studio-stage">
						<div class="studio-stagebar">
							<div class="studio-devices" role="group" aria-label="<?php esc_attr_e( 'Preview size', 'igc-builder' ); ?>">
								<button type="button" class="is-active" data-width="100%">Desktop</button>
								<button type="button" data-width="768px">Tablet</button>
								<button type="button" data-width="390px">Mobile</button>
							</div>
							<div class="studio-preview-options">
								<label class="studio-run-js"><input id="studio-render-wp" type="checkbox" checked> <?php esc_html_e( 'Render WordPress', 'igc-builder' ); ?></label>
								<label class="studio-run-js"><input id="studio-run-js" type="checkbox"> <?php esc_html_e( 'Run custom JS', 'igc-builder' ); ?></label>
							</div>
						</div>
						<div class="studio-canvas-wrap"><iframe id="studio-preview" sandbox="allow-scripts" title="<?php esc_attr_e( 'Live page preview', 'igc-builder' ); ?>"></iframe></div>
					</section>

					<aside class="studio-code">
						<div class="studio-code-tabs" role="tablist">
							<button type="button" class="is-active" data-editor="html">HTML</button>
							<button type="button" data-editor="css">CSS</button>
							<button type="button" data-editor="js">JS</button>
						</div>
						<div class="studio-editor is-active" data-panel="html"><textarea id="studio-html" name="canvas_html"><?php echo esc_textarea( $html ); ?></textarea></div>
						<div class="studio-editor" data-panel="css"><textarea id="studio-css" name="canvas_css"><?php echo esc_textarea( $css ); ?></textarea></div>
						<div class="studio-editor" data-panel="js"><textarea id="studio-js" name="canvas_js"><?php echo esc_textarea( $js ); ?></textarea></div>
						<footer class="studio-code-footer">
							<?php if ( $id ) : ?><code>[studio_canvas id="<?php echo esc_html( $post->post_name ?: (string) $post->ID ); ?>"]</code><?php else : ?><span><?php esc_html_e( 'A shortcode is created on first save.', 'igc-builder' ); ?></span><?php endif; ?>
						</footer>
					</aside>
				</div>
			</form>
		</div>

		<dialog id="studio-import-dialog" class="studio-dialog">
			<form method="dialog">
				<div class="studio-dialog__heading"><div><strong><?php esc_html_e( 'Import full HTML', 'igc-builder' ); ?></strong><p><?php esc_html_e( 'Paste output from an AI editor. Styles and scripts are separated automatically. The current editor content is replaced only after you click Import.', 'igc-builder' ); ?></p></div><button value="cancel" aria-label="<?php esc_attr_e( 'Close', 'igc-builder' ); ?>">×</button></div>
				<textarea id="studio-full-html" placeholder="<!doctype html>…"></textarea>
				<div class="studio-dialog__actions"><button value="cancel" class="studio-button-ui"><?php esc_html_e( 'Cancel', 'igc-builder' ); ?></button><button id="studio-import-confirm" value="default" class="studio-button-ui studio-button-ui--primary"><?php esc_html_e( 'Import to canvas', 'igc-builder' ); ?></button></div>
			</form>
		</dialog>
		<?php
	}

	private static function library_screen(): void {
		$pages = get_posts(
			array(
				'post_type'      => 'igc_canvas',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="wrap igc-admin studio-library-screen">
			<section class="igc-page-heading"><div><span class="igc-eyebrow">VISUAL HTML CANVAS</span><h1><?php esc_html_e( 'Visual Builder', 'igc-builder' ); ?></h1></div><a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=igc-visual-builder&new=1' ) ); ?>"><?php esc_html_e( 'New visual page', 'igc-builder' ); ?></a></section>
			<div class="studio-page-grid">
				<?php if ( ! $pages ) : ?><div class="studio-empty"><h2><?php esc_html_e( 'Start with a blank canvas', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Compose with widgets or paste a complete HTML page from your AI editor.', 'igc-builder' ); ?></p></div><?php endif; ?>
				<?php foreach ( $pages as $page ) : ?>
					<?php $assigned_page = absint( get_post_meta( $page->ID, '_igc_target_page_id', true ) ); ?>
					<a class="studio-page-card" href="<?php echo esc_url( admin_url( 'admin.php?page=igc-visual-builder&id=' . $page->ID ) ); ?>">
						<span class="studio-page-card__status"><?php echo esc_html( ucfirst( $page->post_status ) ); ?></span>
						<h2><?php echo esc_html( $page->post_title ); ?></h2>
						<code>[studio_canvas id="<?php echo esc_html( $page->post_name ?: (string) $page->ID ); ?>"]</code>
						<?php if ( $assigned_page ) : ?><small><?php echo esc_html( get_the_title( $assigned_page ) ); ?><?php echo get_post_meta( $page->ID, '_igc_route_active', true ) ? ' · Active route' : ' · Preview only'; ?></small><?php endif; ?>
						<small><?php echo esc_html( get_the_modified_date( '', $page ) ); ?></small>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public static function save(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) || ! isset( $_POST['igc_canvas_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_canvas_nonce'] ) ), 'igc_save_canvas' ) ) {
			wp_die( esc_html__( 'Canvas save was not authorised.', 'igc-builder' ) );
		}

		$id     = absint( $_POST['canvas_id'] ?? 0 );
		if ( $id && 'igc_canvas' !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'The requested visual page is invalid.', 'igc-builder' ) );
		}
		$status = 'publish' === ( $_POST['canvas_status'] ?? '' ) ? 'publish' : 'draft';
		$title  = sanitize_text_field( wp_unslash( $_POST['canvas_title'] ?? 'Untitled visual page' ) );
		$data   = array(
			'post_type'   => 'igc_canvas',
			'post_title'  => $title,
			'post_status' => $status,
		);
		if ( ! $id || '' === (string) get_post_field( 'post_name', $id ) ) {
			$data['post_name'] = sanitize_title( $title ) ?: 'studio-canvas';
		}
		if ( $id ) {
			$data['ID'] = $id;
		}

		$saved_id = wp_insert_post( $data, true );
		if ( is_wp_error( $saved_id ) ) {
			wp_die( esc_html( $saved_id->get_error_message() ) );
		}

		update_post_meta( $saved_id, '_igc_html', wp_unslash( $_POST['canvas_html'] ?? '' ) );
		update_post_meta( $saved_id, '_igc_css', wp_unslash( $_POST['canvas_css'] ?? '' ) );
		update_post_meta( $saved_id, '_igc_js', wp_unslash( $_POST['canvas_js'] ?? '' ) );
		$target_page_id = absint( $_POST['canvas_target_page'] ?? 0 );
		if ( $target_page_id && 'page' !== get_post_type( $target_page_id ) ) {
			$target_page_id = 0;
		}
		$route_active = 'publish' === $status && $target_page_id && ! empty( $_POST['canvas_route_active'] );
		update_post_meta( $saved_id, '_igc_target_page_id', $target_page_id );
		update_post_meta( $saved_id, '_igc_route_active', $route_active ? '1' : '0' );
		if ( $route_active ) {
			$duplicates = get_posts(
				array(
					'post_type'      => 'igc_canvas',
					'post_status'    => array( 'publish', 'draft' ),
					'posts_per_page' => -1,
					'post__not_in'   => array( $saved_id ),
					'meta_key'       => '_igc_target_page_id',
					'meta_value'     => (string) $target_page_id,
					'fields'         => 'ids',
				)
			);
			foreach ( $duplicates as $duplicate_id ) {
				update_post_meta( $duplicate_id, '_igc_route_active', '0' );
			}
		}
		wp_save_post_revision( $saved_id );
		wp_safe_redirect( admin_url( 'admin.php?page=igc-visual-builder&id=' . $saved_id . '&saved=1' ) );
		exit;
	}

	public static function preview(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) {
			wp_send_json_error( array( 'message' => __( 'Preview was not authorised.', 'igc-builder' ) ), 403 );
		}
		check_ajax_referer( 'igc_canvas_preview', 'nonce' );

		$html = wp_unslash( $_POST['html'] ?? '' );
		$css  = wp_unslash( $_POST['css'] ?? '' );
		$js   = wp_unslash( $_POST['js'] ?? '' );
		if ( strlen( $html ) > 1000000 || strlen( $css ) > 1000000 || strlen( $js ) > 1000000 ) {
			wp_send_json_error( array( 'message' => __( 'Preview source is too large.', 'igc-builder' ) ), 413 );
		}

		$buffer_level = ob_get_level();
		try {
			do_action( 'wp_enqueue_scripts' );
			ob_start();
			$shortcode_output = do_shortcode( $html );
			$echoed_output    = ob_get_clean();
			$body             = $echoed_output . $shortcode_output;

			ob_start();
			wp_head();
			$head = preg_replace( '#<script\b[^>]*>.*?</script>\s*#is', '', ob_get_clean() ) ?: '';
		} catch ( Throwable $error ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			wp_send_json_error( array( 'message' => $error->getMessage() ), 500 );
		}

		$document  = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
		$document .= '<base href="' . esc_url( home_url( '/' ) ) . '">' . $head;
		$document .= '' !== trim( $css ) ? '<style data-studio-preview-css>' . $css . '</style>' : '';
		$document .= '</head><body class="site-studio-preview">' . $body;
		if ( ! empty( $_POST['run_js'] ) ) {
			if ( get_option( 'igc_bundled_lenis', false ) ) {
				$document .= '<script src="' . esc_url( IGC_BUILDER_URL . 'assets/vendor/smooth-scroll/engine.min.js' ) . '"></script>';
			}
			foreach ( (array) get_option( 'igc_external_scripts', array() ) as $url ) {
				$url = esc_url( (string) $url );
				if ( '' !== $url ) {
					$document .= '<script src="' . $url . '"></script>';
				}
			}
			$global_js = (string) get_option( 'igc_global_js', '' );
			if ( '' !== trim( $global_js ) ) {
				$document .= '<script data-studio-global-preview-js>' . str_ireplace( '</script', '<\/script', $global_js ) . '</script>';
			}
			if ( '' !== trim( $js ) ) {
				$document .= '<script data-studio-preview-js>' . str_ireplace( '</script', '<\/script', $js ) . '</script>';
			}
		}
		$document .= '</body></html>';
		wp_send_json_success( array( 'document' => $document ) );
	}

	public static function shortcode( array $atts ): string {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'studio_canvas' );
		$post = is_numeric( $atts['id'] )
			? get_post( (int) $atts['id'] )
			: get_page_by_path( sanitize_title( (string) $atts['id'] ), OBJECT, 'igc_canvas' );
		if ( ! $post || 'igc_canvas' !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}
		return self::render_canvas( $post );
	}

	public static function route_content( string $content ): string {
		if ( is_admin() || is_feed() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || self::$rendering_post_content ) {
			return $content;
		}

		$page_id    = get_queried_object_id();
		$preview_id = isset( $_GET['studio_preview'] ) ? absint( $_GET['studio_preview'] ) : 0;
		if ( $preview_id && current_user_can( 'manage_options' ) ) {
			$preview = get_post( $preview_id );
			if ( $preview && 'igc_canvas' === $preview->post_type && $page_id === absint( get_post_meta( $preview->ID, '_igc_target_page_id', true ) ) ) {
				return self::render_canvas( $preview, true );
			}
		}

		$canvases = get_posts(
			array(
				'post_type'      => 'igc_canvas',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array( 'key' => '_igc_target_page_id', 'value' => (string) $page_id ),
					array( 'key' => '_igc_route_active', 'value' => '1' ),
				),
			)
		);
		return $canvases ? self::render_canvas( $canvases[0] ) : $content;
	}

	private static function render_canvas( WP_Post $post, bool $allow_draft = false ): string {
		if ( 'igc_canvas' !== $post->post_type || ( ! $allow_draft && 'publish' !== $post->post_status ) ) {
			return '';
		}

		$html = (string) get_post_meta( $post->ID, '_igc_html', true );
		$css  = (string) get_post_meta( $post->ID, '_igc_css', true );
		$js   = (string) get_post_meta( $post->ID, '_igc_js', true );
		$out  = '<div class="studio-canvas studio-canvas-' . esc_attr( $post->post_name ) . '" data-studio-canvas="' . esc_attr( (string) $post->ID ) . '">';
		$out .= '' !== trim( $css ) ? '<style>' . $css . '</style>' : '';
		$out .= do_shortcode( $html );
		$out .= '' !== trim( $js ) ? '<script>' . $js . '</script>' : '';
		return $out . '</div>';
	}

	public static function post_content_shortcode(): string {
		if ( ! is_singular() || self::$rendering_post_content ) {
			return '';
		}
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		self::$rendering_post_content = true;
		$content = apply_filters( 'the_content', $post->post_content );
		self::$rendering_post_content = false;
		return $content;
	}

	private static function starter_html(): string {
		return "<section class=\"studio-hero\">\n  <div class=\"studio-container\">\n    <p class=\"studio-kicker\">NEW PROJECT</p>\n    <h1>Build something clear and memorable.</h1>\n    <p>Drop a widget, edit the source, or import a complete page from your AI editor.</p>\n    <a class=\"studio-button\" href=\"#\">Explore</a>\n  </div>\n</section>";
	}

	private static function starter_css(): string {
		return ".studio-hero {\n  min-height: 72vh;\n  display: grid;\n  place-items: center;\n  padding: calc(var(--studio-space) * 8) 24px;\n  background: var(--studio-color-background);\n  color: var(--studio-color-text);\n}\n\n.studio-container { width: min(100%, var(--studio-wide-width)); margin-inline: auto; }\n.studio-hero h1 { max-width: 900px; margin: 12px 0 24px; font: 500 clamp(48px, 8vw, 118px)/.92 var(--studio-font-heading); letter-spacing: -.055em; }\n.studio-kicker { color: var(--studio-color-accent); font-size: 12px; letter-spacing: .16em; }\n.studio-button { display: inline-block; margin-top: 20px; padding: 13px 20px; background: var(--studio-color-text); color: var(--studio-color-background); text-decoration: none; border-radius: var(--studio-radius); }";
	}
}
