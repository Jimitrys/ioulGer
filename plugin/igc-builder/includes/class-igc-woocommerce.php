<?php
defined( 'ABSPATH' ) || exit;

final class IGC_WooCommerce {
	public static function init(): void {
		add_shortcode( 'studio_woo_archive', array( self::class, 'archive' ) );
		add_shortcode( 'studio_woo_product', array( self::class, 'product' ) );
		add_shortcode( 'studio_assigned_canvas', array( self::class, 'assigned_canvas' ) );
	}

	public static function archive(): string {
		if ( ! function_exists( 'woocommerce_product_loop' ) || ! function_exists( 'wc_get_template_part' ) ) {
			return '<!-- Site Studio: WooCommerce is not active -->';
		}

		ob_start();
		echo '<section class="studio-woo-archive" aria-label="' . esc_attr__( 'Products', 'igc-builder' ) . '">';
		do_action( 'woocommerce_shop_loop_header' );
		if ( woocommerce_product_loop() ) {
			do_action( 'woocommerce_before_shop_loop' );
			woocommerce_product_loop_start();
			while ( have_posts() ) {
				the_post();
				wc_get_template_part( 'content', 'product' );
			}
			woocommerce_product_loop_end();
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			do_action( 'woocommerce_no_products_found' );
		}
		echo '</section>';
		return (string) ob_get_clean();
	}

	public static function product(): string {
		if ( ! function_exists( 'wc_get_template_part' ) || ! is_singular( 'product' ) ) {
			return '';
		}
		ob_start();
		echo '<section class="studio-woo-product">';
		while ( have_posts() ) {
			the_post();
			wc_get_template_part( 'content', 'single-product' );
		}
		echo '</section>';
		return (string) ob_get_clean();
	}

	public static function assigned_canvas( array $atts ): string {
		$atts = shortcode_atts( array( 'page' => '' ), $atts, 'studio_assigned_canvas' );
		if ( 'current' === $atts['page'] ) {
			$page = get_queried_object();
		} else {
			$page = is_numeric( $atts['page'] ) ? get_post( absint( $atts['page'] ) ) : get_page_by_path( sanitize_title( (string) $atts['page'] ), OBJECT, 'page' );
		}
		if ( ! $page || 'page' !== $page->post_type ) {
			return '';
		}
		$canvases = get_posts(
			array(
				'post_type'      => 'igc_canvas',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_igc_target_page_id',
				'meta_value'     => (string) $page->ID,
			)
		);
		return $canvases ? do_shortcode( '[studio_canvas id="' . (int) $canvases[0]->ID . '"]' ) : '';
	}
}
