<?php
/**
 * Quick view — open a product over the shop instead of leaving it.
 *
 * A card opens the product in a sheet, and left and right move through the
 * products on the page like a catalogue. Closing puts you back exactly where
 * you were in the grid, which is the thing a normal product page cannot do.
 *
 * What it costs the site
 * ----------------------
 * Nothing until the first click. No product is rendered into the page ahead of
 * time and no extra query runs on load: what ships is this stylesheet and this
 * script, and they sit idle behind one delegated listener.
 *
 * Opening a product is one request, which returns exactly the markup the real
 * product page uses, because it calls the same shortcode with an id. What has
 * been opened is kept, so going back to it is free, and the neighbours are
 * fetched only once the browser is idle and only while a sheet is already
 * open — the visitor has shown intent by then.
 *
 * It is an enhancement, never a requirement. The cards stay real links: a
 * middle click, a new tab, a crawler, or a browser with the script blocked all
 * get the ordinary product page. The address bar follows along, so the URL is
 * still shareable and Back closes the sheet rather than leaving the site.
 *
 * Requires the "Product Page" and "Products archive" snippets.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_quickview_enabled' ) ) {
	function ioulia_quickview_enabled() {
		$on = function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );

		return (bool) apply_filters( 'ioulia_quickview_enabled', $on );
	}
}

/* -------------------------------------------------------------------------
 * The product, on request
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_quickview_ajax' ) ) {
	function ioulia_quickview_ajax() {
		check_ajax_referer( 'ioulia_quickview', 'nonce' );

		$product_id = isset( $_POST['product'] ) ? absint( $_POST['product'] ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product || ! $product->is_visible() ) {
			wp_send_json_error( array( 'message' => 'Δεν βρήκαμε αυτό το προϊόν.' ), 404 );
		}

		/* The same shortcode the product page itself renders, so the sheet cannot
		   drift away from the page it stands in for.

		   Wrapped, because this markup runs whatever any plugin has hooked around
		   a product, and one of those failing here would otherwise send an HTML
		   error page down a channel the browser is parsing as JSON. */
		try {
			$html = do_shortcode( '[ioulia_single_product id="' . $product_id . '"]' );
		} catch ( Throwable $error ) {
			wp_send_json_error(
				array(
					'message' => 'Δεν μπορέσαμε να δείξουμε αυτό το προϊόν εδώ.',
					'fallback' => get_permalink( $product_id ),
				),
				500
			);
		}

		if ( '' === trim( $html ) ) {
			wp_send_json_error( array( 'message' => 'Δεν βρήκαμε αυτό το προϊόν.' ), 404 );
		}

		wp_send_json_success(
			array(
				'html'  => $html,
				'name'  => $product->get_name(),
				'url'   => get_permalink( $product_id ),
			)
		);
	}
	add_action( 'wp_ajax_ioulia_quickview', 'ioulia_quickview_ajax' );
	add_action( 'wp_ajax_nopriv_ioulia_quickview', 'ioulia_quickview_ajax' );
}

/* -------------------------------------------------------------------------
 * The sheet
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_quickview_render' ) ) {
	function ioulia_quickview_render() {
		if ( is_admin() || ! ioulia_quickview_enabled() ) {
			return;
		}
		?>
<div class="iqv" data-iqv hidden
	data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_quickview' ) ); ?>">

	<div class="iqv__backdrop" data-iqv-close></div>

	<div class="iqv__dialog" role="dialog" aria-modal="true" aria-label="Προϊόν">
		<span class="iqv__grab" aria-hidden="true"></span>

		<header class="iqv__head">
			<p class="iqv__count" data-iqv-count></p>

			<span class="iqv__nav">
				<button type="button" class="iqv__arrow" data-iqv-prev aria-label="Προηγούμενο προϊόν">
					<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3 L5 8 L10 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="iqv__arrow" data-iqv-next aria-label="Επόμενο προϊόν">
					<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6 3 L11 8 L6 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="iqv__arrow iqv__arrow--close" data-iqv-close aria-label="Κλείσιμο">
					<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 3 L13 13 M13 3 L3 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
				</button>
			</span>
		</header>

		<div class="iqv__body" data-iqv-scroll>
			<div class="iqv__slot" data-iqv-slot></div>
			<p class="iqv__error" data-iqv-error hidden role="alert"></p>
		</div>
	</div>
</div>
		<?php
		ioulia_quickview_assets();
	}
	add_action( 'wp_footer', 'ioulia_quickview_render', 20 );
}

/* -------------------------------------------------------------------------
 * Styles and behaviour
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_quickview_assets' ) ) {
	function ioulia_quickview_assets() {
		static $printed = false;

		if ( $printed ) {
			return;
		}

		$printed = true;
		?>
<style id="ioulia-quickview-css">
	/* Same shape as the booking sheet: a sheet on a phone, a card above it,
	   24px corners, and an entrance that animates from hidden rather than
	   towards visible, so a skipped animation leaves it on screen. */
	.iqv {
		--iqv-ink: var(--ioulia-ink, #2B2B2B);
		--iqv-paper: var(--ioulia-paper, #FFFEF7);
		--iqv-line: var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		--iqv-muted: var(--ioulia-ink-80, rgba(43, 43, 43, .8));
		--iqv-ease: cubic-bezier(.16, 1, .3, 1);

		position: fixed;
		inset: 0;
		z-index: 99998;
		display: flex;
		align-items: flex-end;
		justify-content: center;
		font-family: var(--ioulia-font, sans-serif);
		color: var(--iqv-ink);
	}
	.iqv[hidden] { display: none; }
	.iqv *, .iqv *::before, .iqv *::after { box-sizing: border-box; }

	.iqv__backdrop { position: absolute; inset: 0; background: rgba(43, 43, 43, .4); }
	.iqv.is-open .iqv__backdrop { animation: iqv-fade .35s ease both; }

	.iqv__dialog {
		position: relative;
		display: flex;
		flex-direction: column;
		width: min(980px, 100%);
		max-height: 92svh;
		border-radius: 24px 24px 0 0;
		background: var(--iqv-paper);
		box-shadow: 0 -8px 40px rgba(43, 43, 43, .14);
	}
	.iqv.is-open .iqv__dialog { animation: iqv-rise .45s var(--iqv-ease) both; }

	@keyframes iqv-fade { from { opacity: 0; } }
	@keyframes iqv-rise { from { opacity: 0; transform: translateY(28px); } }

	.iqv__grab { display: block; width: 40px; height: 4px; margin: 10px auto 0; border-radius: 999px; background: var(--iqv-line); }
	.iqv__grab, .iqv__head { touch-action: none; }

	.iqv__head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem clamp(1.25rem, 4vw, 2rem) 0; }
	.iqv__count { margin: 0; color: var(--iqv-muted); font-size: var(--ioulia-micro); font-weight: 500; letter-spacing: .14em; font-variant-numeric: tabular-nums; }

	.iqv__nav { display: flex; gap: .4rem; }
	.iqv__arrow {
		display: grid;
		place-items: center;
		width: 42px;
		height: 42px;
		padding: 0;
		border: 1px solid var(--iqv-line);
		border-radius: 999px;
		background: transparent;
		color: inherit;
		cursor: pointer;
		transition: border-color .2s ease, background-color .2s ease, opacity .2s ease;
	}
	.iqv__arrow svg { width: 15px; height: 15px; }
	.iqv__arrow:hover:not(:disabled) { border-color: var(--iqv-ink); background: rgba(43, 43, 43, .05); }
	.iqv__arrow:disabled { opacity: .25; cursor: default; }
	.iqv__arrow--close { margin-left: .4rem; }

	.iqv__body {
		flex: 1 1 auto;
		/* A flex child will not shrink below its content without this, and the
		   dialog would overflow while the page behind scrolled instead. */
		min-height: 0;
		overflow-y: auto;
		overscroll-behavior: contain;
		-webkit-overflow-scrolling: touch;
		padding: 0 clamp(1.25rem, 4vw, 2rem) calc(1.5rem + env(safe-area-inset-bottom));
	}

	/* The product arrives, rather than appearing, and the direction says which
	   way through the catalogue you moved. */
	.iqv__slot { animation: iqv-in .38s var(--iqv-ease); }
	.iqv.is-back .iqv__slot { animation-name: iqv-back; }
	@keyframes iqv-in { from { opacity: 0; transform: translateX(18px); } }
	@keyframes iqv-back { from { opacity: 0; transform: translateX(-18px); } }

	.iqv.is-busy .iqv__slot { opacity: .45; transition: opacity .2s ease; }

	.iqv__error { margin: 2rem 0; padding: .9rem 1rem; background: rgba(43, 43, 43, .06); font-size: var(--ioulia-small); font-weight: 500; }
	.iqv__error[hidden] { display: none; }

	/* The product markup was written for a page of its own, so the parts that
	   assume one are trimmed back inside the sheet. */
	.iqv__slot .igsp { padding-block: 1rem; }
	.iqv__slot .igsp__lightbox { z-index: 99999; }

	@media (min-width: 700px) {
		.iqv { align-items: center; padding: 2rem; }
		.iqv__dialog { max-height: 88vh; border-radius: 24px; box-shadow: 0 24px 70px rgba(43, 43, 43, .2); }
		.iqv__grab { display: none; }
	}

	@media (prefers-reduced-motion: reduce) {
		.iqv__dialog, .iqv__backdrop, .iqv__slot { animation: none; }
	}
</style>

<script id="ioulia-quickview-js">
(function () {
	var root = document.querySelector('[data-iqv]');
	if (!root || root.dataset.iqvReady) { return; }

	var grid = document.querySelector('[data-product]');
	if (!grid) { return; }

	root.dataset.iqvReady = '1';

	var dialog = root.querySelector('.iqv__dialog');
	var slot = root.querySelector('[data-iqv-slot]');
	var errorEl = root.querySelector('[data-iqv-error]');
	var countEl = root.querySelector('[data-iqv-count]');
	var scroller = root.querySelector('[data-iqv-scroll]');
	var prevBtn = root.querySelector('[data-iqv-prev]');
	var nextBtn = root.querySelector('[data-iqv-next]');

	var cache = new Map();
	var cards = [];
	var index = -1;
	var entryUrl = window.location.href;
	var lastFocused = null;

	function visibleCards() {
		return [].slice.call(document.querySelectorAll('[data-product]'))
			.filter(function (card) { return !card.hidden && card.offsetParent !== null; });
	}

	/* ---- Page lock. Never moves the page: the offset technique needs the body
	   out of flow, and getting that half-right is what shifts a layout. ---- */

	function lockPage() {
		var page = document.documentElement;
		var bar = window.innerWidth - page.clientWidth;

		page.style.overflow = 'hidden';
		if (bar > 0) { page.style.paddingRight = bar + 'px'; }
	}

	function unlockPage() {
		document.documentElement.style.overflow = '';
		document.documentElement.style.paddingRight = '';
	}

	/* ---- Fetching ---- */

	function load(productId) {
		if (cache.has(productId)) { return Promise.resolve(cache.get(productId)); }

		var body = new FormData();
		body.append('action', 'ioulia_quickview');
		body.append('nonce', root.dataset.nonce);
		body.append('product', productId);

		return fetch(root.dataset.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (response) { return response.text(); })
			.then(function (text) {
				var result;

				/* A fatal anywhere in the product markup arrives as an HTML error
				   page. Reading it as JSON turns a server problem into a parse
				   error in the visitor's face, so it is treated as what it is: a
				   sign that this product needs its own page. */
				try {
					result = JSON.parse(text);
				} catch (error) {
					throw new Error('__fallback__');
				}

				if (!result || !result.success) {
					throw new Error((result && result.data && result.data.message) || '__fallback__');
				}

				cache.set(productId, result.data);
				return result.data;
			});
	}

	/* Markup injected as HTML never runs its own scripts, and the gallery lives
	   in one, so each is recreated to make it execute. */
	function runScripts(container) {
		[].slice.call(container.querySelectorAll('script')).forEach(function (old) {
			var fresh = document.createElement('script');

			[].slice.call(old.attributes).forEach(function (attr) { fresh.setAttribute(attr.name, attr.value); });
			fresh.textContent = old.textContent;
			old.parentNode.replaceChild(fresh, old);
		});
	}

	function paint(data, back) {
		root.classList.toggle('is-back', !!back);
		slot.innerHTML = data.html;
		runScripts(slot);

		countEl.textContent = (index + 1) + ' / ' + cards.length;
		prevBtn.disabled = index <= 0;
		nextBtn.disabled = index >= cards.length - 1;
		errorEl.hidden = true;
		scroller.scrollTop = 0;

		if (data.url) {
			window.history.replaceState({ iqv: true }, '', data.url);
		}

		// Only once a sheet is open and the browser has nothing better to do.
		var idle = window.requestIdleCallback || function (fn) { return window.setTimeout(fn, 400); };
		idle(function () {
			[index - 1, index + 1].forEach(function (i) {
				var neighbour = cards[i];
				if (neighbour) { load(neighbour.getAttribute('data-product')).catch(function () {}); }
			});
		});
	}

	function show(next, back) {
		if (next < 0 || next >= cards.length) { return; }

		index = next;
		root.classList.add('is-busy');

		load(cards[index].getAttribute('data-product'))
			.then(function (data) {
				root.classList.remove('is-busy');
				paint(data, back);
			})
			.catch(function (error) {
				root.classList.remove('is-busy');

				/* If the sheet cannot show it, the product page still can. Sending
				   the visitor there is a better answer than an apology. */
				var url = cards[index] && cards[index].getAttribute('data-url');

				if (url) {
					window.location.href = url;
					return;
				}

				slot.textContent = '';
				errorEl.textContent = '__fallback__' === error.message ? 'Δεν μπορέσαμε να το ανοίξουμε εδώ.' : error.message;
				errorEl.hidden = false;
			});
	}

	/* ---- Opening and closing ---- */

	function open(card) {
		cards = visibleCards();
		var at = cards.indexOf(card);
		if (at < 0) { return; }

		lastFocused = document.activeElement;
		entryUrl = window.location.href;

		root.hidden = false;
		lockPage();
		void root.offsetHeight;
		root.classList.add('is-open');

		show(at, false);
		root.querySelector('[data-iqv-close]').focus();
	}

	function close() {
		root.classList.remove('is-open');
		unlockPage();
		window.history.replaceState({}, '', entryUrl);

		window.setTimeout(function () {
			root.hidden = true;
			slot.textContent = '';
			if (lastFocused) { lastFocused.focus(); }
		}, 300);
	}

	/* A card stays a real link. Only a plain left click is taken over, so a new
	   tab, a middle click and a crawler all reach the product page itself. */
	document.addEventListener('click', function (event) {
		if (event.defaultPrevented || event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) { return; }

		var link = event.target.closest('[data-product] a[href]');
		if (!link) { return; }

		var card = link.closest('[data-product]');
		if (!card || !card.getAttribute('data-product')) { return; }

		event.preventDefault();
		open(card);
	});

	prevBtn.addEventListener('click', function () { show(index - 1, true); });
	nextBtn.addEventListener('click', function () { show(index + 1, false); });

	root.querySelectorAll('[data-iqv-close]').forEach(function (node) {
		node.addEventListener('click', close);
	});

	document.addEventListener('keydown', function (event) {
		if (root.hidden) { return; }

		if ('Escape' === event.key) { close(); }
		else if ('ArrowLeft' === event.key) { show(index - 1, true); }
		else if ('ArrowRight' === event.key) { show(index + 1, false); }
	});

	// Back closes the sheet rather than leaving the shop.
	window.addEventListener('popstate', function () {
		if (!root.hidden) { close(); }
	});

	/* Swipe: sideways moves through the catalogue, down pushes the sheet away.
	   Whichever axis leads decides, so neither fights the other. */
	(function () {
		var startX = 0, startY = 0, axis = '', dragging = false;

		dialog.addEventListener('pointerdown', function (event) {
			if (event.button || event.target.closest('button, a, input, select, textarea')) { return; }
			dragging = true; axis = '';
			startX = event.clientX; startY = event.clientY;
		});

		dialog.addEventListener('pointermove', function (event) {
			if (!dragging) { return; }

			var dx = event.clientX - startX;
			var dy = event.clientY - startY;

			if (!axis && (Math.abs(dx) > 12 || Math.abs(dy) > 12)) {
				axis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
			}

			if ('y' === axis && dy > 0 && window.matchMedia('(max-width: 699px)').matches) {
				dialog.style.transform = 'translateY(' + dy + 'px)';
			}
		});

		dialog.addEventListener('pointerup', function (event) {
			if (!dragging) { return; }
			dragging = false;

			var dx = event.clientX - startX;
			var dy = event.clientY - startY;

			dialog.style.transform = '';

			if ('x' === axis && Math.abs(dx) > 60) {
				show(dx < 0 ? index + 1 : index - 1, dx > 0);
			} else if ('y' === axis && dy > dialog.offsetHeight * 0.25) {
				close();
			}
		});

		dialog.addEventListener('pointercancel', function () { dragging = false; dialog.style.transform = ''; });
	}());
}());
</script>
		<?php
	}
}
