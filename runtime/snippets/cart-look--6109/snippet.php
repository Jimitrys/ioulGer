<?php
/**
 * IOULIA — WooCommerce Cart Block visual layer
 * Paste into Code Snippets as a PHP snippet and run it everywhere.
 * Keeps the native WooCommerce Cart Block functionality intact.
 */

if ( ! function_exists( 'ioulia_cart_visual_layer' ) ) {
	function ioulia_cart_visual_layer() {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<script>document.documentElement.classList.add('ig-cart-motion');</script>
		<style id="ioulia-cart-visual-layer">
			:root {
				--ig-cart-paper: var(--ioulia-cream, #fffef7);
				--ig-cart-ink: var(--ioulia-dark, #2b2b2b);
				--ig-cart-muted: rgba(43, 43, 43, .56);
				--ig-cart-line: rgba(43, 43, 43, .16);
				--ig-cart-soft: rgba(43, 43, 43, .045);
				--ig-cart-x: var(--ioulia-page-x, clamp(22px, 3.05vw, 48px));
				--ig-cart-ease: cubic-bezier(.16, 1, .3, 1);
			}

			body.woocommerce-cart {
				background: var(--ig-cart-paper) !important;
				color: var(--ig-cart-ink);
			}

			body.woocommerce-cart main.site-main,
			body.woocommerce-cart main.site-studio__main {
				width: 100%;
				max-width: none;
				padding: clamp(154px, 19vh, 230px) var(--ig-cart-x) clamp(90px, 12vh, 150px);
				/* On a short viewport the vh part of the clamp lands under the
				   fixed header, so the header height is the floor. */
				padding-top: max(clamp(154px, 19vh, 230px), calc(var(--ioulia-header-h, 176px) + 24px));
				background: var(--ig-cart-paper);
			}

			body.woocommerce-cart .page-header,
			body.woocommerce-cart .page-content {
				width: 100%;
				max-width: var(--ioulia-shell);
				margin-right: auto;
				margin-left: auto;
				padding: 0;
			}

			body.woocommerce-cart .page-header {
				margin-bottom: clamp(64px, 9vh, 118px);
			}

			body.woocommerce-cart .entry-title {
				margin: 0 !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: clamp(64px, 8.4vw, 148px) !important;
				font-weight: 400 !important;
				line-height: .85 !important;
				letter-spacing: -.068em !important;
				text-transform: lowercase;
			}

			body.woocommerce-cart .page-content > .elementor,
			body.woocommerce-cart .page-content .e-con,
			body.woocommerce-cart .page-content .e-con-inner,
			body.woocommerce-cart .elementor-widget-text-editor,
			body.woocommerce-cart .elementor-widget-container {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			body.woocommerce-cart .wp-block-woocommerce-cart.alignwide {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				font-family: inherit;
			}

			body.woocommerce-cart .wc-block-cart {
				display: grid !important;
				grid-template-columns: minmax(0, 1.55fr) minmax(340px, .72fr) !important;
				gap: clamp(50px, 7vw, 126px) !important;
				align-items: start;
			}

			body.woocommerce-cart .wc-block-cart__main,
			body.woocommerce-cart .wc-block-cart__sidebar {
				width: auto !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			body.woocommerce-cart .wc-block-cart__sidebar {
				position: sticky;
				top: 124px;
				border-top: 1px solid var(--ig-cart-ink);
			}

			body.woocommerce-cart .wc-block-cart-items {
				width: 100% !important;
				margin: 0 !important;
				border: 0 !important;
				border-collapse: collapse !important;
			}

			body.woocommerce-cart .wc-block-cart-items thead {
				display: none !important;
			}

			body.woocommerce-cart .wc-block-cart-items__row {
				display: grid !important;
				grid-template-columns: clamp(118px, 13vw, 196px) minmax(0, 1fr) auto;
				gap: clamp(18px, 2.2vw, 34px);
				padding: 22px 0 28px !important;
				border-top: 1px solid var(--ig-cart-line) !important;
				align-items: stretch;
			}

			body.woocommerce-cart .wc-block-cart-items__row:first-child {
				border-top-color: var(--ig-cart-ink) !important;
			}

			body.woocommerce-cart .wc-block-cart-item__image,
			body.woocommerce-cart .wc-block-cart-item__product,
			body.woocommerce-cart .wc-block-cart-item__total {
				display: block !important;
				width: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				vertical-align: top !important;
			}

			body.woocommerce-cart .wc-block-cart-item__image {
				grid-column: 1;
			}

			body.woocommerce-cart .wc-block-cart-item__image a,
			body.woocommerce-cart .wc-block-cart-item__image img {
				display: block;
				width: 100% !important;
				height: 100% !important;
			}

			body.woocommerce-cart .wc-block-cart-item__image a {
				aspect-ratio: 4 / 5;
				overflow: hidden;
				background: #eeeae1;
			}

			body.woocommerce-cart .wc-block-cart-item__image img {
				object-fit: cover !important;
				transition: transform 700ms var(--ig-cart-ease);
			}

			body.woocommerce-cart .wc-block-cart-item__image a:hover img {
				transform: scale(1.025);
			}

			body.woocommerce-cart .wc-block-cart-item__product {
				grid-column: 2;
				display: flex !important;
				min-width: 0;
				flex-direction: column;
				align-items: flex-start;
			}

			body.woocommerce-cart .wc-block-components-product-name {
				margin: 0 0 8px !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: clamp(17px, 1.35vw, 23px) !important;
				font-weight: 400 !important;
				line-height: 1.12 !important;
				letter-spacing: -.035em !important;
				text-decoration: none !important;
			}

			body.woocommerce-cart .wc-block-components-product-name:hover {
				opacity: .55;
			}

			body.woocommerce-cart .wc-block-components-product-metadata,
			body.woocommerce-cart .wc-block-components-product-details {
				margin: 0 0 12px !important;
				color: var(--ig-cart-muted) !important;
				font-size: 11px !important;
				font-weight: 400 !important;
				line-height: 1.45 !important;
			}

			body.woocommerce-cart .wc-block-cart-item__prices,
			body.woocommerce-cart .wc-block-components-product-price {
				margin: 0 0 18px !important;
				color: var(--ig-cart-muted) !important;
				font-size: 12px !important;
				font-weight: 400 !important;
			}

			body.woocommerce-cart .wc-block-components-quantity-selector {
				display: inline-grid !important;
				grid-template-columns: 34px 34px 34px;
				width: auto !important;
				height: 36px !important;
				margin: auto 0 0 !important;
				border: 1px solid var(--ig-cart-line) !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}

			body.woocommerce-cart .wc-block-components-quantity-selector::after {
				display: none !important;
			}

			body.woocommerce-cart .wc-block-components-quantity-selector__button,
			body.woocommerce-cart .wc-block-components-quantity-selector__input {
				width: 34px !important;
				height: 34px !important;
				min-width: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: 12px !important;
				font-weight: 400 !important;
				line-height: 34px !important;
			}

			body.woocommerce-cart .wc-block-cart-item__remove-link {
				margin: 12px 0 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				color: var(--ig-cart-muted) !important;
				font-family: inherit !important;
				font-size: 10px !important;
				font-weight: 400 !important;
				line-height: 1 !important;
				text-decoration: none !important;
				text-transform: lowercase !important;
			}

			body.woocommerce-cart .wc-block-cart-item__remove-link:hover {
				color: var(--ig-cart-ink) !important;
			}

			body.woocommerce-cart .wc-block-cart-item__total {
				grid-column: 3;
				min-width: 84px;
				text-align: right !important;
			}

			body.woocommerce-cart .wc-block-cart-item__total-price-and-sale-badge-wrapper {
				justify-content: flex-end !important;
			}

			body.woocommerce-cart .wc-block-cart-item__total .wc-block-components-product-price {
				color: var(--ig-cart-ink) !important;
				font-size: 14px !important;
				white-space: nowrap;
			}

			body.woocommerce-cart .wc-block-cart__totals-title {
				margin: 0 !important;
				padding: 20px 0 28px !important;
				border: 0 !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: clamp(25px, 2.2vw, 38px) !important;
				font-weight: 400 !important;
				line-height: 1 !important;
				letter-spacing: -.045em !important;
				text-align: left !important;
				text-transform: lowercase !important;
			}

			body.woocommerce-cart .wc-block-components-totals-wrapper {
				padding: 18px 0 !important;
				border-top: 1px solid var(--ig-cart-line) !important;
			}

			body.woocommerce-cart .wc-block-components-totals-item {
				padding: 0 !important;
				color: var(--ig-cart-ink) !important;
				font-size: 12px !important;
				font-weight: 400 !important;
			}

			body.woocommerce-cart .wc-block-components-totals-item__label,
			body.woocommerce-cart .wc-block-components-totals-item__value {
				font-weight: 400 !important;
			}

			body.woocommerce-cart .wc-block-components-totals-footer-item {
				font-size: 17px !important;
			}

			body.woocommerce-cart .wc-block-components-panel__button,
			body.woocommerce-cart .wc-block-components-totals-coupon__button {
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: 11px !important;
				font-weight: 400 !important;
				text-decoration: none !important;
			}

			body.woocommerce-cart .wc-block-components-text-input input {
				min-height: 50px !important;
				border: 1px solid var(--ig-cart-line) !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
			}

			body.woocommerce-cart .wc-block-cart__submit-container {
				margin: 24px 0 0 !important;
				padding: 0 !important;
			}

			body.woocommerce-cart .wc-block-cart__submit-button,
			body.woocommerce-cart .wc-block-components-checkout-place-order-button {
				width: 100% !important;
				min-height: 56px !important;
				padding: 15px 20px !important;
				border: 1px solid var(--ig-cart-ink) !important;
				border-radius: 0 !important;
				background: var(--ig-cart-ink) !important;
				box-shadow: none !important;
				color: var(--ig-cart-paper) !important;
				font-family: inherit !important;
				font-size: 12px !important;
				font-weight: 400 !important;
				line-height: 1 !important;
				text-decoration: none !important;
				text-transform: lowercase !important;
				transition: background-color 260ms ease, color 260ms ease !important;
			}

			body.woocommerce-cart .wc-block-cart__submit-button:hover,
			body.woocommerce-cart .wc-block-cart__submit-button:focus,
			body.woocommerce-cart .wc-block-cart__submit-button:active {
				border-color: var(--ig-cart-ink) !important;
				background: transparent !important;
				color: var(--ig-cart-ink) !important;
				box-shadow: none !important;
			}

			body.woocommerce-cart .wc-block-components-button:focus-visible,
			body.woocommerce-cart button:focus-visible,
			body.woocommerce-cart a:focus-visible {
				outline: 1px solid var(--ig-cart-ink) !important;
				outline-offset: 4px !important;
			}

			body.woocommerce-cart .wc-block-components-notice-banner {
				margin: 0 0 28px !important;
				padding: 16px 18px !important;
				border: 1px solid var(--ig-cart-line) !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				color: var(--ig-cart-ink) !important;
				font-size: 12px !important;
			}

			/* Empty cart */
			body.woocommerce-cart .wp-block-woocommerce-empty-cart-block {
				padding: clamp(20px, 3vh, 42px) 0 0;
			}

			body.woocommerce-cart .wc-block-cart__empty-cart__title {
				margin: 0 0 clamp(74px, 11vh, 130px) !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: clamp(34px, 4.2vw, 72px) !important;
				font-weight: 400 !important;
				line-height: 1 !important;
				letter-spacing: -.052em !important;
				text-align: left !important;
			}

			body.woocommerce-cart .wc-block-cart__empty-cart__title::before,
			body.woocommerce-cart .wp-block-woocommerce-empty-cart-block > .wp-block-separator {
				display: none !important;
			}

			body.woocommerce-cart .wp-block-woocommerce-empty-cart-block > h2:not(.wc-block-cart__empty-cart__title) {
				margin: 0 0 28px !important;
				padding-top: 20px;
				border-top: 1px solid var(--ig-cart-ink);
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: 13px !important;
				font-weight: 400 !important;
				letter-spacing: 0 !important;
				text-align: left !important;
				text-transform: lowercase;
			}

			body.woocommerce-cart .wc-block-grid__products {
				display: grid !important;
				grid-template-columns: repeat(4, minmax(0, 1fr));
				gap: clamp(14px, 2vw, 28px);
				margin: 0 !important;
				padding: 0 !important;
			}

			body.woocommerce-cart .wc-block-grid__product {
				width: auto !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				text-align: left !important;
			}

			body.woocommerce-cart .wc-block-grid__product-image {
				margin: 0 0 10px !important;
				aspect-ratio: 4 / 5;
				overflow: hidden;
				background: #eeeae1;
			}

			body.woocommerce-cart .wc-block-grid__product-image img {
				width: 100% !important;
				height: 100% !important;
				object-fit: cover !important;
			}

			body.woocommerce-cart .wc-block-grid__product-title,
			body.woocommerce-cart .wc-block-grid__product-price {
				margin: 0 0 6px !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: 11px !important;
				font-weight: 400 !important;
				line-height: 1.3 !important;
				text-align: left !important;
			}

			body.woocommerce-cart .wc-block-grid__product-add-to-cart a {
				display: inline-flex !important;
				min-height: 36px;
				margin: 8px 0 0 !important;
				padding: 9px 13px !important;
				align-items: center;
				border: 1px solid var(--ig-cart-line) !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				color: var(--ig-cart-ink) !important;
				font-family: inherit !important;
				font-size: 10px !important;
				font-weight: 400 !important;
				text-decoration: none !important;
				text-transform: lowercase;
			}

			body.woocommerce-cart .wc-block-grid__product-add-to-cart a:hover {
				border-color: var(--ig-cart-ink) !important;
				background: var(--ig-cart-ink) !important;
				color: var(--ig-cart-paper) !important;
			}

			/* Entrance motion: enabled by JS and played only once. */
			html.ig-cart-motion body.woocommerce-cart:not(.ig-cart-ready) .entry-title,
			html.ig-cart-motion body.woocommerce-cart:not(.ig-cart-ready) .wc-block-cart-items__row,
			html.ig-cart-motion body.woocommerce-cart:not(.ig-cart-ready) .wc-block-cart__sidebar,
			html.ig-cart-motion body.woocommerce-cart:not(.ig-cart-ready) .wp-block-woocommerce-empty-cart-block {
				opacity: 0;
				transform: translateY(24px);
			}

			html.ig-cart-motion body.woocommerce-cart .entry-title,
			html.ig-cart-motion body.woocommerce-cart .wc-block-cart-items__row,
			html.ig-cart-motion body.woocommerce-cart .wc-block-cart__sidebar,
			html.ig-cart-motion body.woocommerce-cart .wp-block-woocommerce-empty-cart-block {
				transition: opacity 700ms ease, transform 900ms var(--ig-cart-ease);
			}

			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .entry-title,
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart-items__row,
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart__sidebar,
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wp-block-woocommerce-empty-cart-block {
				opacity: 1;
				transform: translateY(0);
			}

			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .entry-title { transition-delay: 40ms; }
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart-items__row:nth-child(1) { transition-delay: 130ms; }
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart-items__row:nth-child(2) { transition-delay: 200ms; }
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart-items__row:nth-child(3) { transition-delay: 270ms; }
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart-items__row:nth-child(n+4) { transition-delay: 320ms; }
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wc-block-cart__sidebar { transition-delay: 190ms; }
			html.ig-cart-motion body.woocommerce-cart.ig-cart-ready .wp-block-woocommerce-empty-cart-block { transition-delay: 120ms; }

			@media (max-width: 900px) {
				body.woocommerce-cart main.site-main,
				body.woocommerce-cart main.site-studio__main {
					padding: 124px var(--ig-cart-x) 90px;
					padding-top: calc(var(--ioulia-header-h, 139px) + 28px);
				}

				body.woocommerce-cart .page-header {
					margin-bottom: 58px;
				}

				body.woocommerce-cart .entry-title {
					font-size: clamp(58px, 19vw, 92px) !important;
				}

				body.woocommerce-cart .wc-block-cart {
					display: block !important;
				}

				body.woocommerce-cart .wc-block-cart__sidebar {
					position: relative;
					top: auto;
					margin-top: 58px !important;
				}

				body.woocommerce-cart .wc-block-cart-items__row {
					grid-template-columns: 96px minmax(0, 1fr);
					gap: 16px;
					padding: 18px 0 22px !important;
				}

				body.woocommerce-cart .wc-block-cart-item__image {
					grid-column: 1;
					grid-row: 1 / span 2;
				}

				body.woocommerce-cart .wc-block-cart-item__product {
					grid-column: 2;
					grid-row: 1;
				}

				body.woocommerce-cart .wc-block-cart-item__total {
					grid-column: 2;
					grid-row: 2;
					min-width: 0;
					align-self: end;
					text-align: left !important;
				}

				body.woocommerce-cart .wc-block-cart-item__total-price-and-sale-badge-wrapper {
					justify-content: flex-start !important;
				}

				body.woocommerce-cart .wc-block-grid__products {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}
			}

			@media (max-width: 480px) {
				body.woocommerce-cart .wc-block-cart-items__row {
					grid-template-columns: 82px minmax(0, 1fr);
				}

				body.woocommerce-cart .wc-block-components-product-name {
					font-size: 16px !important;
				}

				body.woocommerce-cart .wc-block-grid__product-add-to-cart a {
					width: 100%;
					justify-content: center;
				}
			}

			@media (prefers-reduced-motion: reduce) {
				html.ig-cart-motion body.woocommerce-cart .entry-title,
				html.ig-cart-motion body.woocommerce-cart .wc-block-cart-items__row,
				html.ig-cart-motion body.woocommerce-cart .wc-block-cart__sidebar,
				html.ig-cart-motion body.woocommerce-cart .wp-block-woocommerce-empty-cart-block {
					opacity: 1 !important;
					transform: none !important;
					transition: none !important;
				}
			}
		</style>
		<?php
	}
	add_action( 'wp_head', 'ioulia_cart_visual_layer', 99 );
}

if ( ! function_exists( 'ioulia_cart_visual_layer_motion' ) ) {
	function ioulia_cart_visual_layer_motion() {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<script id="ioulia-cart-visual-layer-motion">
		(function () {
			'use strict';

			var body = document.body;
			if (!body || body.dataset.igCartMotionReady === '1') return;
			body.dataset.igCartMotionReady = '1';

			function revealCart() {
				if (body.classList.contains('ig-cart-ready')) return true;

				var cart = document.querySelector('.wp-block-woocommerce-cart');
				if (!cart || cart.classList.contains('is-loading')) return false;

				window.requestAnimationFrame(function () {
					window.requestAnimationFrame(function () {
						body.classList.add('ig-cart-ready');
					});
				});
				return true;
			}

			if (!revealCart()) {
				var observer = new MutationObserver(function () {
					if (revealCart()) observer.disconnect();
				});

				observer.observe(document.body, {
					subtree: true,
					childList: true,
					attributes: true,
					attributeFilter: ['class']
				});

				window.setTimeout(function () {
					observer.disconnect();
					body.classList.add('ig-cart-ready');
				}, 2400);
			}
		})();
		</script>
		<?php
	}
	add_action( 'wp_footer', 'ioulia_cart_visual_layer_motion', 99 );
}
