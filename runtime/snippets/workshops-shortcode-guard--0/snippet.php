<?php
/**
 * Keep [ioulia_workshops] from printing itself as text.
 *
 * The shortcode is used by the home canvas and by the Book Workshop page. While
 * the Ioulia Workshop Bookings plugin is deactivated nothing answers it, so
 * WordPress prints the raw shortcode to visitors. This renders nothing instead.
 *
 * It only claims the shortcode if no one else has: reactivating the plugin, or
 * shipping our own booking form, takes precedence with no change here. Delete
 * this snippet once the replacement form is live.
 */

if ( ! function_exists( 'ioulia_guard_workshops_shortcode' ) ) {
	function ioulia_guard_workshops_shortcode() {
		if ( ! shortcode_exists( 'ioulia_workshops' ) ) {
			add_shortcode( 'ioulia_workshops', '__return_empty_string' );
		}
	}
	// Late, so a real implementation registered during init always wins.
	add_action( 'init', 'ioulia_guard_workshops_shortcode', 99 );
}
