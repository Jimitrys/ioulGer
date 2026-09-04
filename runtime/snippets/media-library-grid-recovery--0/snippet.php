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
		var frame = wp.media.frames.browse;

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
		 * tick lets core finish wiring the view, then fetch() starts the collection
		 * through WordPress' own media sync adapter. The collection flag prevents
		 * duplicate requests when core has already started loading normally.
		 */
		window.setTimeout(function () {
			var state = frame && frame.state ? frame.state() : null;
			var library = state && state.get ? state.get('library') : null;

			if (!library || library.length || library._igcInitialFetchStarted) {
				return;
			}

			library._igcInitialFetchStarted = true;

			if (typeof library.fetch === 'function') {
				library.fetch({ reset: true });
			} else if (typeof library.more === 'function') {
				library.more();
			}
		}, 250);
	});
});
JS;

		wp_add_inline_script( 'media', $script, 'after' );
	}

	add_action( 'admin_enqueue_scripts', 'ioulia_media_library_grid_recovery', 100 );
}
