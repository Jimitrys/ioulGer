<?php
defined( 'ABSPATH' ) || exit;

final class IGC_Renderer {
	private static array $render_stack = array();

	public static function init(): void {
		add_shortcode( 'igc_block', array( self::class, 'block_shortcode' ) );
		add_shortcode( 'igc_template', array( self::class, 'template_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_global_css' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_global_scripts' ), 30 );
		add_filter( 'template_include', array( self::class, 'template_include' ), 99 );
		add_action( 'init', array( self::class, 'performance_options' ), 20 );
	}

	public static function performance_options(): void {
		if ( ! get_option( 'igc_remove_emoji', false ) ) {
			return;
		}
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	public static function enqueue_global_css(): void {
		if ( get_option( 'igc_bundled_lenis', false ) ) {
			wp_enqueue_style( 'igc-smooth-engine', IGC_BUILDER_URL . 'assets/vendor/smooth-scroll/styles.css', array(), '1.3.26' );
		}

		$external = (string) get_option( 'igc_external_stylesheet', '' );
		if ( '' !== $external ) {
			wp_enqueue_style( 'igc-external', esc_url( $external ), array(), null );
		}

		$tokens    = wp_parse_args( (array) get_option( 'igc_design_tokens', array() ), IGC_Admin::default_tokens() );
		$variables = self::token_css( $tokens );
		$css = (string) get_option( 'igc_global_css', '' );
		if ( '' === trim( $css ) && '' === $variables ) {
			return;
		}

		wp_register_style( 'igc-global', false, array(), IGC_BUILDER_VERSION );
		wp_enqueue_style( 'igc-global' );
		wp_add_inline_style( 'igc-global', $variables . "\n" . $css );
	}

	public static function enqueue_global_scripts(): void {
		$dependencies = array();
		if ( get_option( 'igc_bundled_lenis', false ) ) {
			wp_enqueue_script( 'igc-smooth-engine', IGC_BUILDER_URL . 'assets/vendor/smooth-scroll/engine.min.js', array(), '1.3.26', array( 'in_footer' => true ) );
			$dependencies[] = 'igc-smooth-engine';
		}
		foreach ( (array) get_option( 'igc_external_scripts', array() ) as $index => $url ) {
			$url = esc_url_raw( (string) $url );
			if ( '' === $url ) {
				continue;
			}
			$handle = 'igc-external-script-' . (int) $index;
			wp_enqueue_script( $handle, $url, $dependencies, null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
			$dependencies[] = $handle;
		}

		$javascript = (string) get_option( 'igc_global_js', '' );
		if ( '' === trim( $javascript ) ) {
			return;
		}
		wp_register_script( 'igc-global-runtime', false, $dependencies, IGC_BUILDER_VERSION, array( 'in_footer' => true ) );
		wp_enqueue_script( 'igc-global-runtime' );
		wp_add_inline_script( 'igc-global-runtime', $javascript, 'after' );
	}

	private static function token_css( array $tokens ): string {
		$map = array(
			'--studio-color-background' => $tokens['colors']['background'] ?? '',
			'--studio-color-text'       => $tokens['colors']['text'] ?? '',
			'--studio-color-accent'     => $tokens['colors']['accent'] ?? '',
			'--studio-color-muted'      => $tokens['colors']['muted'] ?? '',
			'--studio-color-border'     => $tokens['colors']['border'] ?? '',
			'--studio-font-body'        => $tokens['typography']['body_font'] ?? '',
			'--studio-font-heading'     => $tokens['typography']['heading_font'] ?? '',
			'--studio-font-size'        => $tokens['typography']['base_size'] ?? '',
			'--studio-line-height'      => $tokens['typography']['line_height'] ?? '',
			'--studio-content-width'    => $tokens['layout']['content_width'] ?? '',
			'--studio-wide-width'       => $tokens['layout']['wide_width'] ?? '',
			'--studio-space'            => $tokens['layout']['space'] ?? '',
			'--studio-radius'           => $tokens['layout']['radius'] ?? '',
		);
		$lines = array();
		foreach ( $map as $property => $value ) {
			$value = trim( str_replace( array( ';', '{', '}' ), '', (string) $value ) );
			if ( '' !== $value ) {
				$lines[] = "\t{$property}: {$value};";
			}
		}
		return $lines ? ":root {\n" . implode( "\n", $lines ) . "\n}" : '';
	}

	public static function block_shortcode( array $atts ): string {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'igc_block' );
		return self::render_entity( $atts['id'], 'igc_code_block' );
	}

	public static function template_shortcode( array $atts ): string {
		$atts = shortcode_atts( array( 'location' => '' ), $atts, 'igc_template' );
		return self::render_location( sanitize_key( $atts['location'] ) );
	}

	public static function render_location( string $location ): string {
		if ( '' === $location ) {
			return '';
		}

		$template = self::find_template( $location );
		return $template ? self::render_entity( $template->ID, 'igc_theme_template' ) : '';
	}

	private static function find_template( string $location ): ?WP_Post {
		$templates = get_posts(
			array(
				'post_type'      => 'igc_theme_template',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => '_igc_priority',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => '_igc_location',
						'value' => $location,
					),
				),
			)
		);

		foreach ( $templates as $template ) {
			if ( self::matches_context( $template ) ) {
				return $template;
			}
		}

		return null;
	}

	public static function current_location(): string {
		if ( is_404() ) {
			return '404';
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'single_product';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return 'checkout';
		}
		if ( ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) {
			return 'product_archive';
		}
		if ( is_front_page() ) {
			return 'front_page';
		}
		if ( is_search() ) {
			return 'search';
		}
		if ( is_page() ) {
			return 'page';
		}
		if ( is_single() ) {
			return 'single';
		}
		if ( is_archive() || is_home() ) {
			return 'archive';
		}
		return '';
	}

	public static function template_include( string $template ): string {
		$admin_preview = isset( $_GET['studio_shell_preview'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['studio_shell_preview'] ) ) && current_user_can( 'manage_options' );
		if ( ( ! get_option( 'igc_site_mode', false ) && ! $admin_preview ) || ( defined( 'IGC_SAFE_MODE' ) && IGC_SAFE_MODE ) ) {
			return $template;
		}

		$location = self::current_location();
		if ( '' === $location || null === self::find_template( $location ) ) {
			return $template;
		}

		return IGC_BUILDER_DIR . 'templates/studio-shell.php';
	}

	private static function matches_context( WP_Post $template ): bool {
		$include = trim( (string) get_post_meta( $template->ID, '_igc_include', true ) );
		if ( '' === $include ) {
			return true;
		}

		$targets = array_filter( array_map( 'trim', explode( ',', strtolower( $include ) ) ) );
		$object  = get_queried_object();
		$id      = isset( $object->ID ) ? (string) $object->ID : ( isset( $object->term_id ) ? (string) $object->term_id : '' );
		$slug    = isset( $object->post_name ) ? strtolower( (string) $object->post_name ) : ( isset( $object->slug ) ? strtolower( (string) $object->slug ) : '' );
		return in_array( $id, $targets, true ) || in_array( $slug, $targets, true );
	}

	private static function render_entity( int|string $identifier, string $post_type ): string {
		$post = is_numeric( $identifier )
			? get_post( (int) $identifier )
			: get_page_by_path( sanitize_title( (string) $identifier ), OBJECT, $post_type );

		if ( ! $post || $post_type !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		if ( isset( self::$render_stack[ $post->ID ] ) ) {
			return '<!-- IGC Builder: recursive block prevented -->';
		}

		self::$render_stack[ $post->ID ] = true;
		$html                            = (string) get_post_meta( $post->ID, '_igc_html', true );
		$css                             = (string) get_post_meta( $post->ID, '_igc_css', true );
		$js                              = (string) get_post_meta( $post->ID, '_igc_js', true );

		$output = '<div class="igc-rendered igc-rendered-' . esc_attr( $post->post_name ) . '" data-igc-id="' . esc_attr( (string) $post->ID ) . '">';
		if ( '' !== trim( $css ) ) {
			$output .= '<style data-igc-style="' . esc_attr( (string) $post->ID ) . '">' . $css . '</style>';
		}
		$output .= do_shortcode( $html );
		if ( '' !== trim( $js ) ) {
			$output .= '<script data-igc-script="' . esc_attr( (string) $post->ID ) . '">' . $js . '</script>';
		}
		$output .= '</div>';

		unset( self::$render_stack[ $post->ID ] );
		return $output;
	}
}
