<?php
/**
 * Fast discovery for Bing, Copilot and other IndexNow consumers.
 *
 * The public key is derived from the site URL, served from its required key
 * location, and sent only when an indexable page or product changes. Repeated
 * WordPress saves are deduplicated before the request leaves the site.
 *
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_indexnow_key' ) ) {
	function ioulia_indexnow_key() {
		return hash( 'sha256', untrailingslashit( home_url( '/' ) ) . '|ioulia-indexnow' );
	}
}

if ( ! function_exists( 'ioulia_indexnow_key_file' ) ) {
	function ioulia_indexnow_key_file() {
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );

		if ( '/' . ioulia_indexnow_key() . '.txt' !== $path ) {
			return;
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=UTF-8' );
		echo esc_html( ioulia_indexnow_key() );
		exit;
	}
	add_action( 'template_redirect', 'ioulia_indexnow_key_file', 0 );
}

if ( ! function_exists( 'ioulia_indexnow_queue_post' ) ) {
	function ioulia_indexnow_queue_post( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'page', 'product' ), true ) ) {
			return;
		}

		if ( 'page' === $post->post_type && function_exists( 'ioulia_seo_private_page_ids' ) && in_array( (int) $post_id, array_map( 'intval', ioulia_seo_private_page_ids() ), true ) ) {
			return;
		}

		$url = get_permalink( $post_id );

		if ( ! $url ) {
			return;
		}

		$GLOBALS['ioulia_indexnow_urls'][ $url ] = $url;

		if ( function_exists( 'ioulia_url' ) ) {
			$english = ioulia_url( $url, 'en' );
			$GLOBALS['ioulia_indexnow_urls'][ $english ] = $english;
		}
	}
	add_action( 'save_post', 'ioulia_indexnow_queue_post', 30, 3 );
}

if ( ! function_exists( 'ioulia_indexnow_submit' ) ) {
	function ioulia_indexnow_submit() {
		$urls = isset( $GLOBALS['ioulia_indexnow_urls'] ) ? array_values( $GLOBALS['ioulia_indexnow_urls'] ) : array();

		foreach ( $urls as $index => $url ) {
			$lock = 'ioulia_indexnow_' . md5( $url );

			if ( get_transient( $lock ) ) {
				unset( $urls[ $index ] );
				continue;
			}

			set_transient( $lock, 1, 5 * MINUTE_IN_SECONDS );
		}

		$urls = array_values( $urls );

		if ( empty( $urls ) ) {
			return;
		}

		$key = ioulia_indexnow_key();

		wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'timeout' => 5,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode(
					array(
						'host'        => (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
						'key'         => $key,
						'keyLocation' => home_url( '/' . $key . '.txt' ),
						'urlList'     => $urls,
					)
				),
			)
		);
	}
	add_action( 'shutdown', 'ioulia_indexnow_submit', 20 );
}
