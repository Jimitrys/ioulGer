<?php
/**
 * Low-risk performance and backend hardening.
 *
 * This keeps every commerce and Site Studio asset intact. It removes only core
 * compatibility payloads the site does not use, limits public user discovery,
 * reduces editor heartbeat traffic and hides marketplace advertising.
 */

if ( ! function_exists( 'ioulia_remove_legacy_head_links' ) ) {
	function ioulia_remove_legacy_head_links() {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'embed_head', 'print_emoji_detection_script' );
	}
	add_action( 'init', 'ioulia_remove_legacy_head_links' );
}

if ( ! function_exists( 'ioulia_disable_unused_embeds' ) ) {
	function ioulia_disable_unused_embeds() {
		wp_deregister_script( 'wp-embed' );
	}
	add_action( 'wp_footer', 'ioulia_disable_unused_embeds', 1 );
}

if ( ! function_exists( 'ioulia_disable_xmlrpc' ) ) {
	function ioulia_disable_xmlrpc() {
		return false;
	}
	add_filter( 'xmlrpc_enabled', 'ioulia_disable_xmlrpc' );
	add_filter( 'pings_open', 'ioulia_disable_xmlrpc' );
}

if ( ! function_exists( 'ioulia_protect_rest_users' ) ) {
	function ioulia_protect_rest_users( $result, $server, $request ) {
		$route = $request->get_route();

		if ( 0 === strpos( $route, '/wp/v2/users' ) && ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_found', 'Not found.', array( 'status' => 404 ) );
		}

		return $result;
	}
	add_filter( 'rest_pre_dispatch', 'ioulia_protect_rest_users', 10, 3 );
}

if ( ! function_exists( 'ioulia_heartbeat_frequency' ) ) {
	function ioulia_heartbeat_frequency( $settings ) {
		$settings['interval'] = 120;

		return $settings;
	}
	add_filter( 'heartbeat_settings', 'ioulia_heartbeat_frequency' );
}

if ( ! function_exists( 'ioulia_resource_hints' ) ) {
	function ioulia_resource_hints( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array(
				'href'        => 'https://cdn-cookieyes.com',
				'crossorigin' => 'anonymous',
			);
		}

		return $urls;
	}
	add_filter( 'wp_resource_hints', 'ioulia_resource_hints', 10, 2 );
}

add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );
