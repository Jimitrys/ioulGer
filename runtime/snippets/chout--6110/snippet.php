<?php
/**
 * IOULIA — Single custom WooCommerce checkout snippet v5.2 (Luxury Minimalist Line Edition - Cart Matching Container)
 *
 * Add as ONE PHP snippet in Code Snippets and run everywhere.
 * Do not add an opening <?php tag.
 * Disable every previous checkout styling snippet first.
 */

if ( ! function_exists( 'ig_single_checkout_active' ) ) {
	function ig_single_checkout_active() {
		return function_exists( 'is_checkout' )
			&& is_checkout()
			&& ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) );
	}
}

/* Replace the Checkout Block with WooCommerce's native shortcode checkout.
 * WooCommerce still handles totals, stock, shipping, validation and gateways.
 */
add_filter( 'the_content', function ( $content ) {
	if (
		! ig_single_checkout_active()
		|| is_admin()
		|| ! is_main_query()
		|| ! in_the_loop()
	) {
		return $content;
	}

	static $rendered = false;
	if ( $rendered ) return $content;
	$rendered = true;

	$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

	ob_start();
	?>
	<main class="ig-checkout-app" aria-labelledby="ig-checkout-title">
		<header class="ig-checkout-app__header">
			<div>
				<p class="ig-checkout-app__eyebrow">secure checkout</p>
				<h1 id="ig-checkout-title">checkout</h1>
			</div>

			<a class="ig-checkout-app__back" href="<?php echo esc_url( $cart_url ); ?>">
				← back to cart
			</a>
		</header>

		<nav class="ig-checkout-progress" aria-label="Checkout sections">
			<button type="button" data-ig-target="customer_details" class="is-current">
				<span>01</span> information
			</button>
			<button type="button" data-ig-target="order_review">
				<span>02</span> delivery &amp; payment
			</button>
		</nav>

		<div class="ig-checkout-app__native">
			<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</main>
	<?php

	return ob_get_clean();
}, 999 );

/* Add a real product image to the classic checkout review. */
add_filter( 'woocommerce_cart_item_name', function ( $name, $cart_item, $cart_item_key ) {
	if ( ! ig_single_checkout_active() || empty( $cart_item['data'] ) ) return $name;

	$product = $cart_item['data'];
	$image   = $product->get_image( 'woocommerce_thumbnail', array(
		'class'   => 'ig-checkout-product__image',
		'loading' => 'lazy',
	) );

	return '<span class="ig-checkout-product">'
		. '<span class="ig-checkout-product__media">' . $image . '</span>'
		. '<span class="ig-checkout-product__name">' . $name . '</span>'
		. '</span>';
}, 20, 3 );

add_action( 'wp_head', function () {
	if ( ! ig_single_checkout_active() ) return;
	?>
	<script>document.documentElement.classList.add('ig-checkout-has-js');</script>
	<style id="ig-single-custom-checkout">
		@supports (interpolate-size: allow-keywords) {
			:root {
				interpolate-size: allow-keywords;
			}
		}

		:root {
			--igc-paper: var(--ioulia-cream, #FAF8F5);
			--igc-ink: var(--ioulia-dark, #1A1A18);
			--igc-muted: rgba(26, 26, 24, 0.48);
			--igc-line: rgba(26, 26, 24, 0.15);
			--igc-line-strong: #1A1A18;
			--igc-x: var(--ioulia-page-x, clamp(28px, 3.05vw, 46px));
			--igc-ease-out: cubic-bezier(0.16, 1, 0.3, 1);
			--igc-ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
			--igc-shadow-focus: 0 8px 24px rgba(0, 0, 0, 0.04);
		}

		/* Reset & Canvas - Cart Matching Container */
		body.woocommerce-checkout:not(.woocommerce-order-received) {
			background: var(--igc-paper) !important;
			color: var(--igc-ink) !important;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}

		body.woocommerce-checkout:not(.woocommerce-order-received) .page-header,
		body.woocommerce-checkout:not(.woocommerce-order-received) .entry-header,
		body.woocommerce-checkout:not(.woocommerce-order-received) .entry-title {
			display: none !important;
		}

		body.woocommerce-checkout:not(.woocommerce-order-received) .page-content,
		body.woocommerce-checkout:not(.woocommerce-order-received) .entry-content,
		body.woocommerce-checkout:not(.woocommerce-order-received) main.site-main {
			width: 100% !important;
			max-width: var(--ioulia-shell) !important;
			margin-right: auto !important;
			margin-left: auto !important;
			padding: 0 !important;
		}

		.ig-checkout-app,
		.ig-checkout-app * {
			box-sizing: border-box;
		}

		/* App Container - Exact Cart Matching Padding & Bounds */
		.ig-checkout-app {
			position: relative;
			width: 100%;
			max-width: var(--ioulia-shell);
			margin-right: auto !important;
			margin-left: auto !important;
			padding: 11em var(--igc-x) !important;
			background: transparent;
			color: var(--igc-ink);
			font-family: inherit;
		}

		.ig-checkout-app__header,
		.ig-checkout-progress,
		.ig-checkout-app__native {
			width: 100%;
			margin-right: auto;
			margin-left: auto;
		}

		/* Header Section */
		.ig-checkout-app__header {
			display: flex;
			align-items: flex-end;
			justify-content: space-between;
			gap: 30px;
		}

		.ig-checkout-app__eyebrow {
			margin: 0 0 16px;
			color: var(--igc-muted);
			font-size: var(--ioulia-micro);
			font-weight: 500;
			line-height: 1;
			letter-spacing: 0.12em;
			text-transform: uppercase;
		}

		.ig-checkout-app h1 {
			margin: 0;
			color: var(--igc-ink);
			font-family: inherit;
			font-size: clamp(64px, 8.4vw, 148px);
			font-weight: 300;
			line-height: 0.85;
			letter-spacing: -0.068em;
			text-transform: lowercase;
		}

		.ig-checkout-app__back {
			margin-bottom: 6px;
			padding: 0;
			border: 0;
			background: transparent;
			color: var(--igc-muted) !important;
			font-size: var(--ioulia-small);
			font-weight: 400;
			line-height: 1;
			text-decoration: none !important;
			transition: color 250ms ease, transform 250ms var(--igc-ease-out);
		}

		.ig-checkout-app__back:hover {
			color: var(--igc-ink) !important;
			transform: translateX(-3px);
		}

		/* Navigation Bar */
		.ig-checkout-progress {
			display: flex;
			gap: 32px;
			margin-top: clamp(60px, 9vh, 118px);
			margin-bottom: 48px;
			border-bottom: 1px solid var(--igc-line);
		}

		.ig-checkout-progress button {
			position: relative;
			margin: 0;
			padding: 0 0 14px;
			border: 0 !important;
			border-bottom: 2px solid transparent !important;
			border-radius: 0 !important;
			outline: 0;
			background: transparent !important;
			box-shadow: none !important;
			color: var(--igc-muted) !important;
			font-family: inherit;
			font-size: var(--ioulia-small);
			font-weight: 400;
			line-height: 1;
			text-transform: lowercase;
			cursor: pointer;
			transition: color 300ms ease, border-color 300ms var(--igc-ease-out), transform 300ms var(--igc-ease-out);
		}

		.ig-checkout-progress button span {
			margin-right: 8px;
			font-size: var(--ioulia-micro);
			font-weight: 500;
			letter-spacing: 0.08em;
			transition: transform 300ms var(--igc-ease-spring);
		}

		.ig-checkout-progress button:hover {
			color: var(--igc-ink) !important;
			transform: translateY(-1px);
		}

		.ig-checkout-progress button.is-current {
			border-bottom-color: var(--igc-ink) !important;
			color: var(--igc-ink) !important;
		}

		.ig-checkout-progress button.is-current span {
			display: inline-block;
			transform: scale(1.1);
		}

		/* Main Form Grid */
		.ig-checkout-app .woocommerce {
			width: 100%;
			margin: 0;
		}

		.ig-checkout-app form.checkout {
			display: grid !important;
			grid-template-columns: minmax(0, 1.45fr) minmax(380px, 0.78fr);
			gap: clamp(52px, 7vw, 140px);
			align-items: start;
			width: 100%;
			margin: 0 !important;
			padding: 0 !important;
		}

		.ig-checkout-app #customer_details,
		.ig-checkout-app #order_review {
			float: none !important;
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		.ig-checkout-app #customer_details {
			display: block !important;
			grid-column: 1;
		}

		.ig-checkout-app #customer_details .col-1,
		.ig-checkout-app #customer_details .col-2 {
			float: none !important;
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		.ig-checkout-app #customer_details .col-2 {
			margin-top: clamp(60px, 8vh, 100px) !important;
		}

		.ig-checkout-app #order_review_heading { display: none !important; }

		.ig-checkout-app #order_review {
			position: sticky;
			top: 110px;
			grid-column: 2;
			padding-top: 2px !important;
		}

		.ig-checkout-app #order_review::before {
			content: "your order";
			display: block;
			margin: 0 0 clamp(44px, 6vh, 76px);
			color: var(--igc-ink);
			font-size: clamp(36px, 3.8vw, 62px);
			font-weight: 300;
			line-height: 0.88;
			letter-spacing: -0.055em;
			text-transform: lowercase;
		}

		/* Section Titles */
		.ig-checkout-app .woocommerce-billing-fields > h3,
		.ig-checkout-app .woocommerce-shipping-fields > h3,
		.ig-checkout-app .woocommerce-additional-fields > h3 {
			margin: 0 0 38px !important;
			color: var(--igc-ink) !important;
			font-family: inherit !important;
			font-size: clamp(28px, 2.6vw, 44px) !important;
			font-weight: 300 !important;
			line-height: 0.95 !important;
			letter-spacing: -0.048em !important;
			text-transform: lowercase;
		}

		.ig-checkout-app .woocommerce-shipping-fields > h3 label {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 20px;
			cursor: pointer;
		}

		/* ----------------------------------------------------
		   NO MORE BORING GREY BOXES!
		   Sleek Minimalist Hairline / Bottom Line Inputs
		   ---------------------------------------------------- */
		.ig-checkout-app .woocommerce-billing-fields__field-wrapper,
		.ig-checkout-app .woocommerce-shipping-fields__field-wrapper,
		.ig-checkout-app .woocommerce-additional-fields__field-wrapper {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 24px 16px;
		}

		.ig-checkout-app .form-row {
			float: none !important;
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			position: relative;
		}

		.ig-checkout-app .form-row-wide,
		.ig-checkout-app #billing_company_field,
		.ig-checkout-app #billing_address_1_field,
		.ig-checkout-app #billing_address_2_field,
		.ig-checkout-app #billing_phone_field,
		.ig-checkout-app #billing_email_field,
		.ig-checkout-app #shipping_company_field,
		.ig-checkout-app #shipping_address_1_field,
		.ig-checkout-app #shipping_address_2_field,
		.ig-checkout-app #order_comments_field {
			grid-column: 1 / -1;
		}

		.ig-checkout-app .form-row label {
			display: block !important;
			margin: 0 0 6px !important;
			color: var(--igc-muted) !important;
			font-size: 10px !important;
			font-weight: 500 !important;
			line-height: 1.2 !important;
			letter-spacing: 0.08em !important;
			text-transform: uppercase !important;
			transition: color 250ms ease, transform 250ms var(--igc-ease-out);
		}

		.ig-checkout-app .form-row:focus-within label {
			color: var(--igc-ink) !important;
		}

		.ig-checkout-app .required { color: inherit !important; }

		/* Clean Transparent Inputs with Expanding Ink Line */
		.ig-checkout-app .input-text,
		.ig-checkout-app input[type="text"],
		.ig-checkout-app input[type="email"],
		.ig-checkout-app input[type="tel"],
		.ig-checkout-app input[type="number"],
		.ig-checkout-app textarea,
		.ig-checkout-app select,
		.ig-checkout-app .select2-selection {
			width: 100% !important;
			height: 54px !important;
			min-height: 54px !important;
			margin: 0 !important;
			padding: 12px 14px !important;
			border: 0 !important;
			border-bottom: 1.5px solid var(--igc-line) !important;
			border-radius: 0 !important;
			outline: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
			color: var(--igc-ink) !important;
			font-family: inherit !important;
			font-size: 14px !important;
			font-weight: 400 !important;
			line-height: 1.3 !important;
			transition: border-color 300ms var(--igc-ease-out), background-color 300ms var(--igc-ease-out), transform 300ms var(--igc-ease-out), box-shadow 300ms var(--igc-ease-out) !important;
		}

		.ig-checkout-app textarea {
			height: 100px !important;
			padding-top: 12px !important;
			line-height: 1.45 !important;
			resize: vertical;
		}

		.ig-checkout-app .input-text:hover,
		.ig-checkout-app select:hover,
		.ig-checkout-app textarea:hover {
			border-bottom-color: rgba(26, 26, 24, 0.45) !important;
			background: rgba(255, 255, 255, 0.25) !important;
		}

		.ig-checkout-app .input-text:focus,
		.ig-checkout-app input:focus,
		.ig-checkout-app textarea:focus,
		.ig-checkout-app select:focus,
		.ig-checkout-app .select2-container--focus .select2-selection,
		.ig-checkout-app .select2-container--open .select2-selection {
			border-bottom-color: var(--igc-ink) !important;
			background: var(--igc-paper) !important;
			box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03) !important;
			transform: translateY(-1px);
		}

		.ig-checkout-app .select2-container { width: 100% !important; }
		.ig-checkout-app .select2-selection__rendered {
			padding: 0 !important;
			line-height: 32px !important;
			color: var(--igc-ink) !important;
		}
		.ig-checkout-app .select2-selection__arrow {
			top: 14px !important;
			right: 10px !important;
		}

		/* ----------------------------------------------------
		   KILL BORING GREY COUPON BANNERS & NOTICES
		   ---------------------------------------------------- */
		.ig-checkout-app .woocommerce-form-coupon-toggle,
		.ig-checkout-app .woocommerce-info,
		.ig-checkout-app .woocommerce-message,
		.ig-checkout-app .checkout_coupon {
			margin: 0 0 36px !important;
			padding: 14px 0 !important;
			border: 0 !important;
			border-bottom: 1px dashed var(--igc-line) !important;
			border-radius: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
			color: var(--igc-muted) !important;
			font-size: 13px !important;
			line-height: 1.4 !important;
			list-style: none !important;
		}

		.ig-checkout-app .woocommerce-info a,
		.ig-checkout-app .woocommerce-form-coupon-toggle a {
			color: var(--igc-ink) !important;
			font-weight: 500 !important;
			text-decoration: underline !important;
			text-underline-offset: 4px;
			transition: opacity 200ms ease;
		}

		.ig-checkout-app .woocommerce-info a:hover {
			opacity: 0.7;
		}

		.ig-checkout-app .woocommerce-info::before,
		.ig-checkout-app .woocommerce-message::before {
			display: none !important;
		}

		.ig-checkout-app .checkout_coupon {
			display: flex;
			gap: 12px;
			align-items: flex-end;
			padding: 20px 0 !important;
		}

		.ig-checkout-app .checkout_coupon input.input-text {
			max-width: 280px;
		}

		.ig-checkout-app .checkout_coupon button.button {
			height: 54px;
			padding: 0 24px;
			border: 1px solid var(--igc-ink) !important;
			background: var(--igc-ink) !important;
			color: var(--igc-paper) !important;
			font-size: var(--ioulia-small);
			font-weight: 500;
			text-transform: lowercase;
			cursor: pointer;
			transition: background-color 250ms ease, color 250ms ease;
		}

		.ig-checkout-app .checkout_coupon button.button:hover {
			background: transparent !important;
			color: var(--igc-ink) !important;
		}

		/* Order Review: Clean Line Layout */
		.ig-checkout-app table.shop_table,
		.ig-checkout-app table.shop_table tbody,
		.ig-checkout-app table.shop_table tfoot,
		.ig-checkout-app table.shop_table tr,
		.ig-checkout-app table.shop_table th,
		.ig-checkout-app table.shop_table td {
			border: 0 !important;
			border-radius: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
		}

		.ig-checkout-app table.shop_table {
			display: block;
			width: 100%;
			margin: 0 !important;
			padding: 0 !important;
		}

		.ig-checkout-app table.shop_table > thead { display: none; }
		.ig-checkout-app table.shop_table > tbody,
		.ig-checkout-app table.shop_table > tfoot { display: block; }
		
		.ig-checkout-app table.shop_table > tbody > tr,
		.ig-checkout-app table.shop_table > tfoot > tr {
			display: grid;
			grid-template-columns: minmax(0, 1fr) auto;
			gap: 22px;
			align-items: start;
			transition: transform 300ms var(--igc-ease-out);
		}

		.ig-checkout-app table.shop_table > tbody > tr {
			padding: 0 0 28px;
			border-bottom: 1px solid var(--igc-line) !important;
			margin-bottom: 24px;
		}

		.ig-checkout-app table.shop_table > tbody > tr:hover {
			transform: translateX(4px);
		}

		.ig-checkout-app table.shop_table > tfoot > tr {
			padding: 10px 0;
		}

		.ig-checkout-app table.shop_table th,
		.ig-checkout-app table.shop_table td {
			display: block;
			width: auto !important;
			margin: 0 !important;
			padding: 0 !important;
			color: var(--igc-ink) !important;
			font-family: inherit !important;
			font-size: 13px !important;
			font-weight: 400 !important;
			line-height: 1.4 !important;
		}

		.ig-checkout-product {
			display: grid;
			grid-template-columns: clamp(90px, 8vw, 120px) minmax(0, 1fr);
			gap: 20px;
			align-items: start;
		}

		.ig-checkout-product__media {
			display: block;
			width: clamp(90px, 8vw, 120px);
			height: clamp(118px, 10vw, 156px);
			overflow: hidden;
			border: 1px solid var(--igc-line);
			background: transparent;
		}

		.ig-checkout-product__image {
			width: 100% !important;
			height: 100% !important;
			margin: 0 !important;
			object-fit: cover !important;
			transition: transform 500ms var(--igc-ease-out) !important;
		}

		.ig-checkout-product:hover .ig-checkout-product__image {
			transform: scale(1.08) !important;
		}

		.ig-checkout-product__name {
			display: block;
			padding-top: 2px;
			font-size: 14px;
			font-weight: 400;
			line-height: 1.35;
		}

		.ig-checkout-app .product-quantity {
			display: block;
			margin-top: 8px;
			color: var(--igc-muted);
			font-size: var(--ioulia-micro);
		}

		.ig-checkout-app table.shop_table .order-total {
			margin-top: 24px;
			padding-top: 24px !important;
			border-top: 1px solid var(--igc-ink) !important;
		}

		.ig-checkout-app table.shop_table .order-total th,
		.ig-checkout-app table.shop_table .order-total td,
		.ig-checkout-app table.shop_table .order-total .amount {
			font-size: clamp(22px, 1.8vw, 30px) !important;
			font-weight: 400 !important;
			letter-spacing: -0.035em;
		}

		/* Shipping Options - Clean Hairline Rows */
		.ig-checkout-app #shipping_method,
		.ig-checkout-app #shipping_method li {
			margin: 0 !important;
			padding: 0 !important;
			list-style: none !important;
		}

		.ig-checkout-app #shipping_method {
			display: grid;
			gap: 12px;
			min-width: min(320px, 42vw);
		}

		.ig-checkout-app #shipping_method li {
			display: grid;
			grid-template-columns: 18px minmax(0, 1fr);
			gap: 12px;
			align-items: center;
			padding: 12px 0;
			border: 0;
			border-bottom: 1px solid var(--igc-line);
			background: transparent;
			transition: border-color 250ms ease, transform 250ms var(--igc-ease-out);
			cursor: pointer;
		}

		.ig-checkout-app #shipping_method li:hover {
			border-bottom-color: var(--igc-ink);
			transform: translateX(3px);
		}

		.ig-checkout-app #shipping_method li:has(input:checked) {
			border-bottom-color: var(--igc-ink);
		}

		.ig-checkout-app input[type="radio"],
		.ig-checkout-app input[type="checkbox"] {
			width: 17px !important;
			height: 17px !important;
			margin: 0 !important;
			accent-color: var(--igc-ink) !important;
			cursor: pointer;
			transition: transform 200ms var(--igc-ease-spring);
		}

		.ig-checkout-app input[type="radio"]:checked,
		.ig-checkout-app input[type="checkbox"]:checked {
			transform: scale(1.15);
		}

		.ig-checkout-app #shipping_method label {
			color: var(--igc-ink) !important;
			font-size: 13px !important;
			font-weight: 400 !important;
			line-height: 1.4 !important;
			cursor: pointer;
		}

		/* Payment Methods - Modern Open List */
		.ig-checkout-app #payment {
			margin-top: clamp(54px, 7vh, 86px) !important;
			padding: 0 !important;
			border: 0 !important;
			border-radius: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
		}

		.ig-checkout-app #payment::before {
			content: "payment";
			display: block;
			margin-bottom: 28px;
			color: var(--igc-ink);
			font-size: clamp(26px, 2.3vw, 40px);
			font-weight: 300;
			line-height: 0.95;
			letter-spacing: -0.045em;
		}

		.ig-checkout-app #payment ul.payment_methods,
		.ig-checkout-app #payment ul.payment_methods li {
			margin: 0 !important;
			padding: 0 !important;
			border: 0 !important;
			background: transparent !important;
			list-style: none !important;
		}

		.ig-checkout-app #payment ul.payment_methods {
			display: grid;
			gap: 4px;
		}

		.ig-checkout-app #payment ul.payment_methods li {
			position: relative;
			padding: 16px 0 !important;
			border-bottom: 1px solid var(--igc-line) !important;
			transition: border-color 300ms var(--igc-ease-out), transform 300ms var(--igc-ease-out) !important;
		}

		.ig-checkout-app #payment ul.payment_methods li:hover {
			border-bottom-color: var(--igc-ink) !important;
			transform: translateX(3px);
		}

		.ig-checkout-app #payment ul.payment_methods li:has(input:checked) {
			border-bottom-color: var(--igc-ink) !important;
		}

		.ig-checkout-app #payment ul.payment_methods li > label {
			margin-left: 10px;
			color: var(--igc-ink) !important;
			font-size: 13.5px !important;
			font-weight: 400 !important;
			cursor: pointer;
		}

		.ig-checkout-app #payment div.payment_box {
			margin: 14px 0 4px 28px !important;
			padding: 0 !important;
			border: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
			color: var(--igc-muted) !important;
			font-size: 12px !important;
			line-height: 1.55 !important;
			transition: opacity 300ms ease;
		}

		.ig-checkout-app #payment div.payment_box::before { display: none !important; }

		.ig-checkout-app #payment .place-order {
			margin: 40px 0 0 !important;
			padding: 0 !important;
		}

		/* Animated Place Order Button */
		.ig-checkout-app #place_order {
			position: relative !important;
			float: none !important;
			width: 100% !important;
			min-height: 68px !important;
			margin: 20px 0 0 !important;
			padding: 18px 60px 18px 24px !important;
			border: 1px solid var(--igc-ink) !important;
			border-radius: 0 !important;
			background: var(--igc-ink) !important;
			box-shadow: none !important;
			color: var(--igc-paper) !important;
			font-family: inherit !important;
			font-size: 14px !important;
			font-weight: 500 !important;
			line-height: 1 !important;
			text-transform: lowercase !important;
			letter-spacing: 0.02em !important;
			cursor: pointer !important;
			overflow: hidden !important;
			transition: background-color 300ms var(--igc-ease-out), color 300ms var(--igc-ease-out), border-color 300ms var(--igc-ease-out), transform 150ms ease !important;
		}

		.ig-checkout-app #place_order::after {
			content: "→";
			position: absolute;
			right: 24px;
			top: 50%;
			font-size: 20px;
			font-weight: 400;
			transform: translateY(-52%);
			transition: transform 300ms var(--igc-ease-out);
		}

		.ig-checkout-app #place_order:hover,
		.ig-checkout-app #place_order:focus {
			border-color: var(--igc-ink) !important;
			background: transparent !important;
			color: var(--igc-ink) !important;
			box-shadow: none !important;
		}

		.ig-checkout-app #place_order:hover::after {
			transform: translate(6px, -52%);
		}

		.ig-checkout-app #place_order:active {
			transform: scale(0.985) !important;
		}

		.ig-checkout-app .woocommerce-privacy-policy-text,
		.ig-checkout-app .woocommerce-terms-and-conditions-wrapper {
			color: var(--igc-muted) !important;
			font-size: 11px !important;
			line-height: 1.55 !important;
		}

		.ig-checkout-app .woocommerce-privacy-policy-text a,
		.ig-checkout-app .woocommerce-terms-and-conditions-wrapper a {
			color: inherit !important;
			text-decoration: underline !important;
			text-underline-offset: 3px;
		}

		/* Validation Errors Shake */
		.ig-checkout-app .woocommerce-error {
			margin: 0 0 32px !important;
			padding: 16px 0 !important;
			border: 0 !important;
			border-bottom: 1px solid var(--igc-ink) !important;
			border-radius: 0 !important;
			background: transparent !important;
			box-shadow: none !important;
			color: var(--igc-ink) !important;
			font-size: 12px !important;
			list-style: none !important;
			animation: igcShake 400ms var(--igc-ease-out);
		}

		@keyframes igcShake {
			0%, 100% { transform: translateX(0); }
			20%, 60% { transform: translateX(-6px); }
			40%, 80% { transform: translateX(6px); }
		}

		.ig-checkout-app .woocommerce-error::before { display: none !important; }

		.ig-checkout-app .woocommerce-invalid .input-text,
		.ig-checkout-app .woocommerce-invalid select,
		.ig-checkout-app .woocommerce-invalid .select2-selection {
			border-bottom-color: var(--igc-ink) !important;
		}

		/* Smooth Loading Overlay */
		.ig-checkout-app .blockUI.blockOverlay {
			background: var(--igc-paper) !important;
			opacity: 0.65 !important;
		}

		.ig-checkout-app .blockUI.blockOverlay::before {
			display: none !important;
		}

		/* Staggered Reveal Animation */
		html.ig-checkout-has-js body:not(.ig-checkout-ready) .ig-checkout-app__header,
		html.ig-checkout-has-js body:not(.ig-checkout-ready) .ig-checkout-progress,
		html.ig-checkout-has-js body:not(.ig-checkout-ready) #customer_details,
		html.ig-checkout-has-js body:not(.ig-checkout-ready) #order_review {
			opacity: 0;
			transform: translateY(26px);
		}

		.ig-checkout-app__header,
		.ig-checkout-progress,
		.ig-checkout-app #customer_details,
		.ig-checkout-app #order_review {
			transition: opacity 750ms ease, transform 900ms var(--igc-ease-out);
		}

		body.ig-checkout-ready .ig-checkout-app__header,
		body.ig-checkout-ready .ig-checkout-progress,
		body.ig-checkout-ready #customer_details,
		body.ig-checkout-ready #order_review {
			opacity: 1;
			transform: none;
		}

		body.ig-checkout-ready .ig-checkout-progress { transition-delay: 80ms; }
		body.ig-checkout-ready #customer_details { transition-delay: 140ms; }
		body.ig-checkout-ready #order_review { transition-delay: 200ms; }

		/* Responsive Breakdown */
		@media (max-width: 900px) {
			.ig-checkout-app {
				padding-top: 110px !important;
			}

			.ig-checkout-app form.checkout {
				display: block !important;
			}

			.ig-checkout-app #order_review {
				position: relative;
				top: auto;
				margin-top: 80px !important;
			}

			.ig-checkout-app #order_review::before {
				margin-bottom: 40px;
			}
		}

		@media (max-width: 620px) {
			.ig-checkout-app__header {
				align-items: flex-start;
			}

			.ig-checkout-app__eyebrow { margin-bottom: 14px; }

			.ig-checkout-progress {
				gap: 20px;
				overflow-x: auto;
				margin-top: 50px;
				padding-bottom: 4px;
				scrollbar-width: none;
			}

			.ig-checkout-progress::-webkit-scrollbar { display: none; }
			.ig-checkout-progress button { flex: 0 0 auto; }

			.ig-checkout-app .woocommerce-billing-fields__field-wrapper,
			.ig-checkout-app .woocommerce-shipping-fields__field-wrapper,
			.ig-checkout-app .woocommerce-additional-fields__field-wrapper {
				grid-template-columns: 1fr;
			}

			.ig-checkout-app .form-row { grid-column: 1 !important; }

			.ig-checkout-product {
				grid-template-columns: 84px minmax(0, 1fr);
				gap: 14px;
			}

			.ig-checkout-product__media {
				width: 84px;
				height: 108px;
			}

			.ig-checkout-app #shipping_method {
				min-width: 0;
			}
		}

		@media (prefers-reduced-motion: reduce) {
			.ig-checkout-app__header,
			.ig-checkout-progress,
			.ig-checkout-app #customer_details,
			.ig-checkout-app #order_review {
				opacity: 1 !important;
				transform: none !important;
				transition: none !important;
			}
		}
	</style>
	<?php
}, 999 );

add_action( 'wp_footer', function () {
	if ( ! ig_single_checkout_active() ) return;
	?>
	<script id="ig-single-custom-checkout-js">
	(function () {
		function ready() {
			requestAnimationFrame(function () {
				requestAnimationFrame(function () {
					document.body.classList.add('ig-checkout-ready');
				});
			});
		}

		function scrollToSection(id) {
			var target = document.getElementById(id);
			if (!target) return;

			var top = target.getBoundingClientRect().top + window.pageYOffset - 110;
			window.scrollTo({ top: top, behavior: 'smooth' });
		}

		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-ig-target]');
			if (!button) return;

			event.preventDefault();
			document.querySelectorAll('[data-ig-target]').forEach(function (item) {
				item.classList.toggle('is-current', item === button);
			});
			scrollToSection(button.getAttribute('data-ig-target'));
		});

		function updateProgress() {
			var review = document.getElementById('order_review');
			var buttons = document.querySelectorAll('[data-ig-target]');
			if (!review || !buttons.length) return;

			var activeTarget = review.getBoundingClientRect().top < window.innerHeight * 0.55
				? 'order_review'
				: 'customer_details';

			buttons.forEach(function (button) {
				button.classList.toggle(
					'is-current',
					button.getAttribute('data-ig-target') === activeTarget
				);
			});
		}

		window.addEventListener('scroll', updateProgress, { passive: true });
		window.addEventListener('load', ready, { once: true });

		if (document.readyState === 'complete') ready();
		else document.addEventListener('DOMContentLoaded', ready, { once: true });
	})();
	</script>
	<?php
}, 999 );
