<?php
/**
 * Recover the WordPress Media Library grid when the core admin bootstrap stops
 * before creating its media frame. The guard keeps this inert when core works.
 */

if ( ! function_exists( 'ioulia_media_library_grid_recovery' ) ) {
	function ioulia_media_library_grid_recovery(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'upload' !== $screen->base ) {
			return;
		}

		$script = <<<'JS'
jQuery(function ($) {
	var $grid = $('#wp-media-grid');
	if (!$grid.length || !window.wp || !wp.media) {
		return;
	}

	window.requestAnimationFrame(function () {
		var settings = window._wpMediaGridSettings || {};
		var frame = wp.media.frame || wp.media.frames.browse;

		if (!frame) {
			frame = wp.media({
				frame: 'manage',
				container: $grid,
				library: settings.queryVars || {}
			}).open();
		}

		/*
		 * A few admin/plugin combinations create the manage frame but leave its
		 * attachment collection idle. In that state the grid keeps its spinner
		 * forever and no query-attachments request reaches WordPress. Waiting one
		 * tick lets core finish wiring the view, then reconnecting its query mirror
		 * lets more() use WordPress' own media sync adapter. The collection flag prevents
		 * duplicate requests when core has already started loading normally.
		 */
		window.setTimeout(function () {
			var state = frame && frame.state ? frame.state() : null;
			var library = state && state.get ? state.get('library') : null;
			var request;

			if (!library || library.length || library._igcInitialFetchStarted) {
				return;
			}

			library._igcInitialFetchStarted = true;

			if (!library.mirroring && window.wp.media.model.Query && typeof library.mirror === 'function') {
				var props = library.props && typeof library.props.toJSON === 'function'
					? library.props.toJSON()
					: {};

				props.query = true;
				library.mirror(wp.media.model.Query.get(props));
			}

			if (typeof library.more === 'function') {
				request = library.more();
				if (request && typeof request.done === 'function') {
					window.setTimeout(function () {
						if (library.length || (typeof request.state === 'function' && request.state() !== 'pending')) {
							return;
						}

						var query = $.extend({
							posts_per_page: 80,
							paged: 1,
							orderby: 'date',
							order: 'DESC'
						}, settings.queryVars || {});
						var body = new window.URLSearchParams();

						body.set('action', 'query-attachments');
						body.set('_ajax_nonce', __IGC_MEDIA_NONCE__);
						body.set('post_id', '0');
						Object.keys(query).forEach(function (key) {
							body.set('query[' + key + ']', query[key]);
						});

						window.fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
							method: 'POST',
							credentials: 'same-origin',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
							body: body.toString()
						}).then(function (result) {
							return result.json();
						}).then(function (response) {
							if (response && response.success && Array.isArray(response.data)) {
								library.reset(response.data, { parse: true });
								library.trigger('attachments:received', library);
							}
						}).catch(function (error) {
							window.console.error('IGC media recovery failed', error);
						});
					}, 2000);
				}
			}
		}, 250);
	});
});
JS;
		$script = str_replace( '__IGC_MEDIA_NONCE__', wp_json_encode( wp_create_nonce( 'media-form' ) ), $script );

		wp_add_inline_script( 'media', $script, 'after' );
	}

	add_action( 'admin_enqueue_scripts', 'ioulia_media_library_grid_recovery', 100 );
}
