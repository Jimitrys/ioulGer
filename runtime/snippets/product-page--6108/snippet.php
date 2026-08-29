<?php
/**
 * Ioulia Geraskli — Single Product Page
 *
 * Paste into Code Snippets and activate.
 * Add [ioulia_single_product] to an Elementor Single Product template.
 *
 * Optional:
 * [ioulia_single_product id="123"]
 */



/**
 * Two optional editorial fields in Product data > General.
 */
add_action( 'woocommerce_product_options_general_product_data', 'igsp_add_editorial_product_fields' );
function igsp_add_editorial_product_fields() {
	woocommerce_wp_textarea_input(
		array(
			'id'          => '_igsp_materials_care',
			'label'       => 'Materials & care',
			'description' => 'Displayed as an accordion on the custom product page.',
			'desc_tip'    => true,
			'rows'        => 5,
		)
	);

	woocommerce_wp_textarea_input(
		array(
			'id'          => '_igsp_shipping_returns',
			'label'       => 'Shipping & returns',
			'description' => 'Displayed as an accordion on the custom product page.',
			'desc_tip'    => true,
			'rows'        => 5,
		)
	);
}

add_action( 'woocommerce_process_product_meta', 'igsp_save_editorial_product_fields' );
function igsp_save_editorial_product_fields( $product_id ) {
	$field_ids = array(
		'_igsp_materials_care',
		'_igsp_shipping_returns',
	);

	foreach ( $field_ids as $field_id ) {
		if ( isset( $_POST[ $field_id ] ) ) {
			update_post_meta(
				$product_id,
				$field_id,
				wp_kses_post( wp_unslash( $_POST[ $field_id ] ) )
			);
		}
	}
}

add_shortcode( 'ioulia_single_product', 'igsp_render_single_product' );
function igsp_render_single_product( $atts = array() ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'id' => '',
		),
		$atts,
		'ioulia_single_product'
	);

	$product_id = absint( $atts['id'] );

	if ( ! $product_id && is_singular( 'product' ) ) {
		$product_id = get_the_ID();
	}

	$product = wc_get_product( $product_id );

	if ( ! $product || ! $product->is_visible() ) {
		return '';
	}

	$instance_id = wp_unique_id( 'igsp-' );
	$product_url = get_permalink( $product_id );
	$product_name = $product->get_name();

	$image_ids = array_values(
		array_unique(
			array_filter(
				array_merge(
					array( absint( $product->get_image_id() ) ),
					array_map( 'absint', $product->get_gallery_image_ids() )
				)
			)
		)
	);

	if ( empty( $image_ids ) ) {
		$image_ids = array( 0 );
	}

	$short_description = $product->get_short_description();
	$full_description  = $product->get_description();
	$materials_care    = get_post_meta( $product_id, '_igsp_materials_care', true );
	$shipping_returns  = get_post_meta( $product_id, '_igsp_shipping_returns', true );

	$categories = get_the_terms( $product_id, 'product_cat' );
	$categories = is_array( $categories ) ? $categories : array();

	$collections = taxonomy_exists( 'product_collection' )
		? get_the_terms( $product_id, 'product_collection' )
		: array();
	$collections = is_array( $collections ) ? $collections : array();

	$collection_names = wp_list_pluck( $collections, 'name' );
	$category_names   = wp_list_pluck( $categories, 'name' );

	$collection_description = '';
	$collection_title       = '';

	if ( ! empty( $collections ) ) {
		$first_collection       = $collections[0];
		$collection_title       = 'collection — ' . $first_collection->name;
		$collection_description = term_description(
			$first_collection->term_id,
			'product_collection'
		);
	}

	$detail_rows = array();

	if ( $product->has_dimensions() ) {
		$detail_rows['dimensions'] = wc_format_dimensions( $product->get_dimensions( false ) );
	}

	if ( $product->has_weight() ) {
		$detail_rows['weight'] = wc_format_weight( $product->get_weight() );
	}

	if ( wc_product_sku_enabled() && $product->get_sku() ) {
		$detail_rows['sku'] = $product->get_sku();
	}

	if ( ! empty( $category_names ) ) {
		$detail_rows['category'] = implode( ', ', array_slice( $category_names, 0, 3 ) );
	}

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() ) {
			continue;
		}

		$attribute_name = wc_attribute_label( $attribute->get_name() );

		if ( $attribute->is_taxonomy() ) {
			$attribute_values = wc_get_product_terms(
				$product_id,
				$attribute->get_name(),
				array( 'fields' => 'names' )
			);
		} else {
			$attribute_values = $attribute->get_options();
		}

		if ( ! empty( $attribute_values ) ) {
			$detail_rows[ $attribute_name ] = implode(
				', ',
				array_map( 'wp_strip_all_tags', $attribute_values )
			);
		}
	}

	/**
	 * Use WooCommerce's own add-to-cart form so stock, variations,
	 * quantities and validation continue to work normally.
	 */
	wp_enqueue_script( 'wc-add-to-cart-variation' );

	$previous_product    = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
	$GLOBALS['product'] = $product;

	ob_start();
	woocommerce_template_single_add_to_cart();
	$add_to_cart_html = ob_get_clean();

	if ( $previous_product ) {
		$GLOBALS['product'] = $previous_product;
	} else {
		unset( $GLOBALS['product'] );
	}

	ob_start();
	?>
	<section
		id="<?php echo esc_attr( $instance_id ); ?>"
		class="igsp"
		data-igsp-root
		data-product-url="<?php echo esc_url( $product_url ); ?>"
		aria-label="<?php echo esc_attr( $product_name ); ?>"
	>
		<style>
			#<?php echo esc_attr( $instance_id ); ?> {
				--igsp-paper: var(--ioulia-cream, #fffef7);
				--igsp-ink: var(--ioulia-dark, #2b2b2b);
				--igsp-accent: var(--ioulia-bg-dark, #7c3737);
				--igsp-muted: rgba(43, 43, 43, .58);
				--igsp-line: rgba(43, 43, 43, .2);
				--igsp-soft: #f2efe7;
				--igsp-x: var(--ioulia-page-x, clamp(18px, 2.8vw, 38px));
				--igsp-nav-bottom: 170px;
				position: relative;
				width: 100%;
				background: var(--igsp-paper);
				color: var(--igsp-ink);
				font-family: var(--ioulia-font, "Montserrat", Arial, sans-serif);
			}

			#<?php echo esc_attr( $instance_id ); ?> *,
			#<?php echo esc_attr( $instance_id ); ?> *::before,
			#<?php echo esc_attr( $instance_id ); ?> *::after {
				box-sizing: border-box;
			}

			#<?php echo esc_attr( $instance_id ); ?> button,
			#<?php echo esc_attr( $instance_id ); ?> input,
			#<?php echo esc_attr( $instance_id ); ?> select {
				font: inherit;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__layout {
				display: grid;
				grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
				align-items: start;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__gallery {
				position: relative;
				min-width: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__media-list {
				display: grid;
				margin: 0;
				padding: 0;
				list-style: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__media-item {
				position: relative;
				width: 100%;
				height: 100vh;
				min-height: 650px;
				overflow: hidden;
				background: var(--igsp-soft);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__media-button {
				appearance: none;
				position: relative;
				display: block;
				width: 100%;
				height: 100%;
				margin: 0;
				padding: 0;
				overflow: hidden;
				border: 0;
				border-radius: 0;
				background: transparent;
				box-shadow: none;
				cursor: pointer;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__image {
				position: absolute;
				inset: 0;
				width: 100%;
				height: 100%;
				object-fit: cover;
				opacity: 0;
				transform: scale(1.025);
				transition:
					opacity 680ms ease,
					transform 1100ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__media-item.is-visible .igsp__image {
				opacity: 1;
				transform: scale(1.001);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__media-item:first-child.is-visible .igsp__image {
				transition-delay: 80ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__gallery-ui {
				z-index: 6;
				display: none;
				width: 100%;
				padding: 0 var(--igsp-x);
				color: var(--igsp-ink);
				align-items: center;
				justify-content: space-between;
				pointer-events: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__count {
				font-size: var(--ioulia-micro);
				font-weight: 400;
				line-height: 1;
				letter-spacing: .035em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__mobile-arrows {
				display: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel {
				position: sticky;
				top: 0;
				height: 100vh;
				min-height: 650px;
				padding:
					var(--igsp-nav-bottom)
					var(--igsp-x)
					clamp(26px, 3vw, 46px);
				overflow-y: auto;
				background: var(--igsp-paper);
				scrollbar-width: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel::-webkit-scrollbar {
				display: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner {
				display: flex;
				width: 100%;
				height: 100%;
				max-width: 520px;
				margin: 0 auto;
				flex-direction: column;
				justify-content: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > * {
				opacity: 0;
				transform: translateY(18px);
				animation: igsp-element-in 820ms cubic-bezier(.16, 1, .3, 1) forwards;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > *:nth-child(1) {
				animation-delay: 120ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > *:nth-child(2) {
				animation-delay: 190ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > *:nth-child(3) {
				animation-delay: 260ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > *:nth-child(4) {
				animation-delay: 330ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > *:nth-child(5) {
				animation-delay: 400ms;
			}

			@keyframes igsp-element-in {
				to {
					opacity: 1;
					transform: translateY(0);
				}
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__heading-row {
				display: block;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__title,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__price {
				margin: 0;
				color: var(--igsp-ink);
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__title {
				max-width: 15ch;
				font-size: clamp(29px, 2.25vw, 40px);
				line-height: .98;
				letter-spacing: -.045em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__price {
				margin-top: 13px;
				font-size: var(--ioulia-small);
				line-height: 1.35;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__price del {
				color: var(--igsp-muted);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__price ins {
				color: var(--igsp-accent);
				text-decoration: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__stock {
				margin-top: 8px;
				color: var(--igsp-muted);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__stock .stock {
				margin: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__intro {
				max-width: 44ch;
				margin-top: clamp(25px, 2.3vw, 34px);
				color: var(--igsp-ink);
				font-size: var(--ioulia-small);
				font-weight: 400;
				line-height: 1.58;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__intro > *:first-child {
				margin-top: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__intro > *:last-child {
				margin-bottom: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase {
				margin-top: clamp(26px, 2.5vw, 38px);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart {
				margin: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart:not(.variations_form),
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-add-to-cart {
				display: grid !important;
				width: 100% !important;
				grid-template-columns: 96px minmax(0, 1fr) !important;
				gap: 10px !important;
				align-items: stretch !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart > input[type="hidden"],
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-add-to-cart > input[type="hidden"] {
				display: none !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .quantity {
				position: relative;
				display: grid;
				float: none !important;
				min-width: 0;
				width: 96px !important;
				height: 52px;
				margin: 0 !important;
				border: 0;
				background: var(--igsp-soft);
				grid-template-columns: 29px minmax(30px, 1fr) 29px;
				align-items: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart:not(.variations_form) > .quantity,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-add-to-cart > .quantity {
				grid-column: 1 !important;
				grid-row: 1 !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .quantity .screen-reader-text {
				position: absolute;
				width: 1px;
				height: 1px;
				padding: 0;
				margin: -1px;
				overflow: hidden;
				clip: rect(0, 0, 0, 0);
				white-space: nowrap;
				border: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .qty {
				appearance: textfield;
				width: 100%;
				height: 52px;
				margin: 0;
				padding: 0;
				border: 0;
				border-radius: 0;
				background: transparent;
				color: var(--igsp-ink);
				box-shadow: none;
				font-size: var(--ioulia-small);
				font-weight: 400;
				text-align: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .qty::-webkit-inner-spin-button,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .qty::-webkit-outer-spin-button {
				appearance: none;
				margin: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__qty-button {
				appearance: none;
				display: grid;
				width: 29px;
				height: 52px;
				margin: 0;
				padding: 0;
				border: 0;
				border-radius: 0;
				background: transparent;
				color: var(--igsp-ink);
				box-shadow: none;
				cursor: pointer;
				font-size: 16px;
				font-weight: 300;
				place-items: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .single_add_to_cart_button,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .button {
				appearance: none;
				float: none !important;
				display: flex !important;
				width: 100% !important;
				min-height: 52px;
				margin: 0 !important;
				padding: 8px 18px !important;
				border: 1px solid var(--igsp-ink) !important;
				border-radius: 0 !important;
				background: var(--igsp-ink) !important;
				color: var(--igsp-paper) !important;
				box-shadow: none !important;
				cursor: pointer;
				align-items: center;
				justify-content: center;
				font-size: 12px !important;
				font-weight: 400 !important;
				line-height: 1 !important;
				letter-spacing: .025em;
				text-align: center;
				text-transform: lowercase;
				transition:
					color 260ms ease,
					background 260ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart:not(.variations_form) > .single_add_to_cart_button,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-add-to-cart > .single_add_to_cart_button {
				grid-column: 2 !important;
				grid-row: 1 !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .single_add_to_cart_button:hover,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .button:hover {
				background: #111 !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .single_add_to_cart_button.disabled,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .single_add_to_cart_button:disabled {
				cursor: not-allowed;
				opacity: .42;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations {
				display: block;
				width: 100%;
				margin: 0 0 15px;
				border: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations tbody,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations tr,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations th,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations td {
				display: block;
				width: 100%;
				padding: 0;
				border: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations tr + tr {
				margin-top: 12px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations label {
				display: block;
				margin-bottom: 6px;
				color: var(--igsp-muted);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				letter-spacing: .03em;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations select {
				appearance: none;
				width: 100%;
				height: 48px;
				margin: 0;
				padding: 0 34px 0 12px;
				border: 1px solid var(--igsp-line);
				border-radius: 0;
				background:
					linear-gradient(45deg, transparent 50%, currentColor 50%)
					calc(100% - 16px) 50% / 4px 4px no-repeat,
					linear-gradient(135deg, currentColor 50%, transparent 50%)
					calc(100% - 12px) 50% / 4px 4px no-repeat,
					var(--igsp-paper);
				color: var(--igsp-ink);
				box-shadow: none;
				font-size: var(--ioulia-small);
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .variations select.igsp__native-select {
				position: absolute !important;
				width: 1px !important;
				height: 1px !important;
				margin: -1px !important;
				padding: 0 !important;
				overflow: hidden !important;
				clip: rect(0, 0, 0, 0) !important;
				white-space: nowrap !important;
				border: 0 !important;
				opacity: 0 !important;
				pointer-events: none !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-picker {
				position: relative;
				width: 100%;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-trigger {
				appearance: none;
				position: relative;
				display: flex;
				width: 100%;
				height: 48px;
				margin: 0;
				padding: 0 36px 0 12px;
				border: 1px solid var(--igsp-line);
				border-radius: 0;
				background: var(--igsp-paper);
				color: var(--igsp-ink);
				box-shadow: none;
				cursor: pointer;
				align-items: center;
				font-size: var(--ioulia-small);
				font-weight: 400;
				text-align: left;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-trigger::after {
				content: "";
				position: absolute;
				top: 50%;
				right: 14px;
				width: 6px;
				height: 6px;
				margin-top: -4px;
				border-right: 1px solid currentColor;
				border-bottom: 1px solid currentColor;
				transform: rotate(45deg);
				transition: transform 220ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-picker.is-open .igsp__variation-trigger::after {
				margin-top: 0;
				transform: rotate(225deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-menu {
				position: absolute;
				z-index: 30;
				top: calc(100% - 1px);
				right: 0;
				left: 0;
				display: grid;
				max-height: 210px;
				padding: 6px 0;
				overflow-y: auto;
				border: 1px solid var(--igsp-line);
				background: var(--igsp-paper);
				box-shadow: 0 14px 30px rgba(43, 43, 43, .07);
				opacity: 0;
				visibility: hidden;
				transform: translateY(-4px);
				transition:
					opacity 150ms ease,
					visibility 150ms ease,
					transform 220ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-picker.is-open .igsp__variation-menu {
				opacity: 1;
				visibility: visible;
				transform: translateY(0);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-option {
				appearance: none;
				position: relative;
				width: 100%;
				min-height: 39px;
				margin: 0;
				padding: 8px 30px 8px 12px;
				border: 0;
				border-radius: 0;
				background: transparent;
				color: var(--igsp-muted);
				box-shadow: none;
				cursor: pointer;
				font-size: var(--ioulia-small);
				font-weight: 400;
				line-height: 1.25;
				text-align: left;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-option:hover,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-option[aria-selected="true"] {
				color: var(--igsp-ink);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-option[aria-selected="true"]::after {
				content: "";
				position: absolute;
				top: 50%;
				right: 13px;
				width: 5px;
				height: 5px;
				border-radius: 50%;
				background: var(--igsp-accent);
				transform: translateY(-50%);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-option:disabled {
				cursor: not-allowed;
				opacity: .28;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-trigger:focus-visible,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__variation-option:focus-visible {
				outline: 1px solid var(--igsp-accent);
				outline-offset: 2px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .reset_variations {
				display: inline-block;
				margin-top: 6px;
				color: var(--igsp-muted);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				text-decoration: underline;
				text-transform: lowercase;
				text-underline-offset: 3px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation {
				margin-bottom: 12px;
				font-size: var(--ioulia-small);
				line-height: 1.45;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-price {
				color: var(--igsp-ink);
				font-size: var(--ioulia-small);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .stock {
				margin: 0 0 8px;
				color: var(--igsp-muted);
				font-size: var(--ioulia-micro);
				text-transform: lowercase;
			}

			/**
			 * Official WooCommerce Stripe / WooPayments express checkout
			 * containers. Stripe still renders and secures the actual wallet UI.
			 */
			#<?php echo esc_attr( $instance_id ); ?> #wc-stripe-express-checkout-element,
			#<?php echo esc_attr( $instance_id ); ?> #wc-stripe-payment-request-wrapper,
			#<?php echo esc_attr( $instance_id ); ?> .wc-stripe-product-checkout-container,
			#<?php echo esc_attr( $instance_id ); ?> .wcpay-express-checkout-wrapper {
				width: 100%;
				margin-top: 12px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .wc-stripe-express-checkout-button-separator,
			#<?php echo esc_attr( $instance_id ); ?> #wc-stripe-payment-request-button-separator {
				display: flex;
				margin: 15px 0 10px;
				color: var(--igsp-muted);
				align-items: center;
				gap: 12px;
				font-size: var(--ioulia-micro);
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .wc-stripe-express-checkout-button-separator::before,
			#<?php echo esc_attr( $instance_id ); ?> .wc-stripe-express-checkout-button-separator::after,
			#<?php echo esc_attr( $instance_id ); ?> #wc-stripe-payment-request-button-separator::before,
			#<?php echo esc_attr( $instance_id ); ?> #wc-stripe-payment-request-button-separator::after {
				content: "";
				height: 1px;
				background: var(--igsp-line);
				flex: 1;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordions {
				margin-top: clamp(30px, 3vw, 44px);
				counter-reset: igsp-accordion;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion {
				margin: 2px 0;
				border: 0;
				counter-increment: igsp-accordion;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion[open] {
				margin: 2px 0;
				padding: 0;
				background: transparent;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion summary {
				display: grid;
				min-height: 48px;
				padding: 8px 0;
				color: var(--igsp-ink);
				cursor: pointer;
				align-items: center;
				grid-template-columns: 30px minmax(0, 1fr) 30px;
				gap: 10px;
				font-size: var(--ioulia-small);
				font-weight: 400;
				line-height: 1.35;
				list-style: none;
				text-transform: lowercase;
				transition: color 180ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion summary::-webkit-details-marker {
				display: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion summary::before {
				content: counter(igsp-accordion, decimal-leading-zero);
				color: var(--igsp-muted);
				font-size: var(--ioulia-micro);
				letter-spacing: .04em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion summary::after {
				content: "+";
				display: grid;
				width: 28px;
				height: 28px;
				border: 1px solid var(--igsp-line);
				border-radius: 50%;
				color: var(--igsp-muted);
				font-size: 16px;
				font-weight: 300;
				line-height: 1;
				place-items: center;
				transition: transform 260ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion summary:hover {
				color: var(--igsp-accent);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion[open] summary::after {
				transform: rotate(45deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion-content {
				max-width: 48ch;
				margin: 3px 0 18px 40px;
				padding: 0;
				color: var(--igsp-muted);
				font-size: var(--ioulia-small);
				font-weight: 400;
				line-height: 1.65;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion-content > *:first-child {
				margin-top: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion-content > *:last-child {
				margin-bottom: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__details-list {
				display: grid;
				margin: 0;
				grid-template-columns: auto minmax(0, 1fr);
				gap: 6px 16px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__details-list dt,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__details-list dd {
				margin: 0;
				font-weight: 400;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__details-list dt {
				color: var(--igsp-ink);
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox {
				position: fixed;
				z-index: 100500;
				inset: 0;
				display: grid;
				padding: clamp(18px, 3vw, 44px);
				background: rgba(255, 254, 247, .97);
				opacity: 0;
				visibility: hidden;
				place-items: center;
				transition: opacity 220ms ease, visibility 220ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox.is-open {
				opacity: 1;
				visibility: visible;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-image {
				display: block;
				width: 100%;
				height: 100%;
				max-width: 1500px;
				max-height: calc(100vh - 88px);
				object-fit: contain;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-close,
			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-arrow {
				appearance: none;
				position: absolute;
				z-index: 2;
				display: grid;
				margin: 0;
				padding: 0;
				border: 0;
				border-radius: 0;
				background: transparent;
				color: var(--igsp-ink);
				box-shadow: none;
				cursor: pointer;
				font-weight: 300;
				place-items: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-close {
				top: 18px;
				right: 22px;
				width: 34px;
				height: 34px;
				font-size: 27px;
				transform: rotate(45deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-arrow {
				top: 50%;
				width: 52px;
				height: 52px;
				font-size: 26px;
				transform: translateY(-50%);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-arrow--prev {
				left: 12px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-arrow--next {
				right: 12px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-count {
				position: absolute;
				right: 24px;
				bottom: 20px;
				color: var(--igsp-muted);
				font-size: var(--ioulia-micro);
				letter-spacing: .035em;
			}

			@media (max-width: 900px) {
				#<?php echo esc_attr( $instance_id ); ?> .igsp__layout {
					display: block;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__gallery {
					padding-top: var(--igsp-nav-bottom);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__media-list {
					display: flex;
					width: 100%;
					overflow-x: auto;
					overflow-y: hidden;
					scroll-behavior: smooth;
					scroll-snap-type: x mandatory;
					scrollbar-width: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__media-list::-webkit-scrollbar {
					display: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__media-item {
					width: 100%;
					height: auto;
					min-width: 100%;
					min-height: 0;
					aspect-ratio: 4 / 5;
					flex: 0 0 100%;
					scroll-snap-align: start;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__gallery-ui {
					display: flex;
					height: auto;
					padding: 12px var(--igsp-x) 0;
					opacity: 0;
					transform: translateY(8px);
					animation: igsp-element-in 680ms 420ms cubic-bezier(.16, 1, .3, 1) forwards;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__mobile-arrows {
					display: flex;
					align-items: center;
					gap: 14px;
					pointer-events: auto;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__arrow {
					appearance: none;
					position: relative;
					width: 36px;
					height: 24px;
					margin: 0;
					padding: 0;
					border: 0;
					background: transparent;
					color: var(--igsp-ink);
					box-shadow: none;
					cursor: pointer;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__arrow::before {
					content: "";
					position: absolute;
					top: 50%;
					right: 0;
					left: 0;
					height: 1px;
					background: currentColor;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__arrow::after {
					content: "";
					position: absolute;
					top: 50%;
					width: 7px;
					height: 7px;
					border-top: 1px solid currentColor;
					border-left: 1px solid currentColor;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__arrow--prev::after {
					left: 0;
					transform: translateY(-50%) rotate(-45deg);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__arrow--next::after {
					right: 0;
					transform: translateY(-50%) rotate(135deg);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel {
					position: static;
					height: auto;
					min-height: 0;
					padding:
						clamp(38px, 9vw, 64px)
						var(--igsp-x)
						clamp(82px, 16vw, 120px);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner {
					display: block;
					max-width: none;
					height: auto;
					opacity: 1;
					transform: none;
					animation: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__title {
					max-width: 16ch;
					font-size: clamp(28px, 8vw, 36px);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__intro {
					max-width: 58ch;
					font-size: 14px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart:not(.variations_form),
				#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-add-to-cart {
					grid-template-columns: 96px minmax(0, 1fr) !important;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__accordions {
					margin-top: 30px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion summary {
					min-height: 48px;
					font-size: var(--ioulia-small);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__accordion-content {
					max-width: 62ch;
					font-size: var(--ioulia-small);
				}
			}

			@media (max-width: 520px) {
				#<?php echo esc_attr( $instance_id ); ?> .igsp__media-item {
					aspect-ratio: 3 / 4;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__heading-row {
					gap: 12px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase form.cart:not(.variations_form),
				#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .woocommerce-variation-add-to-cart {
					grid-template-columns: 88px minmax(0, 1fr) !important;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__purchase .quantity {
					width: 88px !important;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox-arrow {
					top: auto;
					bottom: 12px;
					transform: none;
				}
			}

			@media (prefers-reduced-motion: reduce) {
				#<?php echo esc_attr( $instance_id ); ?> .igsp__image,
				#<?php echo esc_attr( $instance_id ); ?> .igsp__lightbox {
					transition: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner {
					opacity: 1;
					transform: none;
					animation: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igsp__product-panel-inner > *,
				#<?php echo esc_attr( $instance_id ); ?> .igsp__gallery-ui {
					opacity: 1;
					transform: none;
					animation: none;
				}
			}
		</style>

		<div class="igsp__layout">
			<div class="igsp__gallery">
				<ol class="igsp__media-list" data-gallery>
					<?php foreach ( $image_ids as $image_index => $image_id ) : ?>
						<?php
						$full_image_url = $image_id
							? wp_get_attachment_image_url( $image_id, 'full' )
							: wc_placeholder_img_src( 'woocommerce_single' );
						?>
						<li
							class="igsp__media-item"
							data-slide
							data-slide-index="<?php echo esc_attr( $image_index ); ?>"
						>
							<button
								class="igsp__media-button"
								type="button"
								data-lightbox-open
								data-image-index="<?php echo esc_attr( $image_index ); ?>"
								data-full-image="<?php echo esc_url( $full_image_url ); ?>"
								aria-label="<?php echo esc_attr( 'enlarge ' . $product_name . ' image ' . ( $image_index + 1 ) ); ?>"
							>
								<?php
								if ( $image_id ) {
									echo wp_get_attachment_image(
										$image_id,
										'woocommerce_single',
										false,
										array(
											'class'    => 'igsp__image',
											'loading'  => 0 === $image_index ? 'eager' : 'lazy',
											'decoding' => 'async',
											'alt'      => $product_name,
										)
									);
								} else {
									echo wc_placeholder_img(
										'woocommerce_single',
										array(
											'class' => 'igsp__image',
											'alt'   => $product_name,
										)
									);
								}
								?>
							</button>
						</li>
					<?php endforeach; ?>
				</ol>

				<div class="igsp__gallery-ui">
					<span class="igsp__count" aria-live="polite">
						<span data-current-slide>1</span>/<span><?php echo esc_html( count( $image_ids ) ); ?></span>
					</span>

					<?php if ( count( $image_ids ) > 1 ) : ?>
						<div class="igsp__mobile-arrows">
							<button
								class="igsp__arrow igsp__arrow--prev"
								type="button"
								data-gallery-prev
								aria-label="previous image"
							></button>
							<button
								class="igsp__arrow igsp__arrow--next"
								type="button"
								data-gallery-next
								aria-label="next image"
							></button>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<aside class="igsp__product-panel">
				<div class="igsp__product-panel-inner">
					<div class="igsp__heading-row">
						<h1 class="igsp__title"><?php echo esc_html( $product_name ); ?></h1>
						<p class="igsp__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
					</div>

					<div class="igsp__stock">
						<?php echo wp_kses_post( wc_get_stock_html( $product ) ); ?>
					</div>

					<?php if ( $short_description ) : ?>
						<div class="igsp__intro">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_short_description', $short_description ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $product->is_purchasable() || $product->is_type( 'external' ) ) : ?>
						<div class="igsp__purchase">
							<?php echo $add_to_cart_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>

					<div class="igsp__accordions">
						<?php if ( $full_description ) : ?>
							<details class="igsp__accordion">
								<summary>the object</summary>
								<div class="igsp__accordion-content">
									<?php echo wp_kses_post( apply_filters( 'the_content', $full_description ) ); ?>
								</div>
							</details>
						<?php endif; ?>

						<?php if ( $collection_description ) : ?>
							<details class="igsp__accordion">
								<summary><?php echo esc_html( $collection_title ); ?></summary>
								<div class="igsp__accordion-content">
									<?php echo wp_kses_post( $collection_description ); ?>
								</div>
							</details>
						<?php endif; ?>

						<?php if ( $materials_care ) : ?>
							<details class="igsp__accordion">
								<summary>materials &amp; care</summary>
								<div class="igsp__accordion-content">
									<?php echo wp_kses_post( wpautop( $materials_care ) ); ?>
								</div>
							</details>
						<?php endif; ?>

						<?php if ( ! empty( $detail_rows ) ) : ?>
							<details class="igsp__accordion">
								<summary>details</summary>
								<div class="igsp__accordion-content">
									<dl class="igsp__details-list">
										<?php foreach ( $detail_rows as $detail_label => $detail_value ) : ?>
											<dt><?php echo esc_html( $detail_label ); ?></dt>
											<dd><?php echo esc_html( $detail_value ); ?></dd>
										<?php endforeach; ?>
									</dl>
								</div>
							</details>
						<?php endif; ?>

						<?php if ( $shipping_returns ) : ?>
							<details class="igsp__accordion">
								<summary>shipping &amp; returns</summary>
								<div class="igsp__accordion-content">
									<?php echo wp_kses_post( wpautop( $shipping_returns ) ); ?>
								</div>
							</details>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>

		<div
			class="igsp__lightbox"
			data-lightbox
			aria-hidden="true"
			role="dialog"
			aria-modal="true"
			aria-label="<?php echo esc_attr( $product_name . ' image gallery' ); ?>"
		>
			<button
				class="igsp__lightbox-close"
				type="button"
				data-lightbox-close
				aria-label="close gallery"
			>+</button>
			<button
				class="igsp__lightbox-arrow igsp__lightbox-arrow--prev"
				type="button"
				data-lightbox-prev
				aria-label="previous image"
			>←</button>
			<img
				class="igsp__lightbox-image"
				data-lightbox-image
				src=""
				alt=""
			>
			<button
				class="igsp__lightbox-arrow igsp__lightbox-arrow--next"
				type="button"
				data-lightbox-next
				aria-label="next image"
			>→</button>
			<span class="igsp__lightbox-count" data-lightbox-count></span>
		</div>

		<script>
		(function () {
			"use strict";

			var root = document.getElementById(<?php echo wp_json_encode( $instance_id ); ?>);
			if (!root || root.dataset.ready === "true") return;
			root.dataset.ready = "true";

			var gallery = root.querySelector("[data-gallery]");
			var slides = Array.prototype.slice.call(root.querySelectorAll("[data-slide]"));
			var currentCounter = root.querySelector("[data-current-slide]");
			var galleryPrev = root.querySelector("[data-gallery-prev]");
			var galleryNext = root.querySelector("[data-gallery-next]");
			var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
			var currentSlide = 0;
			var scrollFrame = 0;

			function setCurrentSlide(index) {
				if (!slides.length) return;

				currentSlide = (index + slides.length) % slides.length;

				if (currentCounter) {
					currentCounter.textContent = String(currentSlide + 1);
				}
			}

			function goToSlide(index) {
				if (!slides.length || !gallery) return;
				setCurrentSlide(index);

				if (window.innerWidth <= 900) {
					gallery.scrollTo({
						left: currentSlide * gallery.clientWidth,
						behavior: reduceMotion ? "auto" : "smooth"
					});
				} else {
					slides[currentSlide].scrollIntoView({
						behavior: reduceMotion ? "auto" : "smooth",
						block: "start"
					});
				}
			}

			if (galleryPrev) {
				galleryPrev.addEventListener("click", function () {
					goToSlide(currentSlide - 1);
				});
			}

			if (galleryNext) {
				galleryNext.addEventListener("click", function () {
					goToSlide(currentSlide + 1);
				});
			}

			if (gallery) {
				gallery.addEventListener("scroll", function () {
					if (window.innerWidth > 900 || scrollFrame) return;

					scrollFrame = window.requestAnimationFrame(function () {
						scrollFrame = 0;
						var width = gallery.clientWidth || 1;
						setCurrentSlide(Math.round(gallery.scrollLeft / width));
					});
				}, { passive: true });
			}

			if ("IntersectionObserver" in window) {
				var revealObserver = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) return;
						entry.target.classList.add("is-visible");
					});
				}, {
					rootMargin: "0px 0px -5% 0px",
					threshold: 0.08
				});

				slides.forEach(function (slide) {
					revealObserver.observe(slide);
				});

				var desktopCounterObserver = new IntersectionObserver(function (entries) {
					if (window.innerWidth <= 900) return;

					var visibleEntries = entries
						.filter(function (entry) {
							return entry.isIntersecting;
						})
						.sort(function (a, b) {
							return b.intersectionRatio - a.intersectionRatio;
						});

					if (visibleEntries.length) {
						setCurrentSlide(
							Number(visibleEntries[0].target.dataset.slideIndex || 0)
						);
					}
				}, {
					threshold: [0.25, 0.5, 0.75]
				});

				slides.forEach(function (slide) {
					desktopCounterObserver.observe(slide);
				});
			} else {
				slides.forEach(function (slide) {
					slide.classList.add("is-visible");
				});
			}

			/**
			 * Quantity controls are added around WooCommerce's real input,
			 * keeping its stock limits and validation intact.
			 */
			Array.prototype.slice.call(
				root.querySelectorAll(".quantity")
			).forEach(function (quantity) {
				var input = quantity.querySelector(".qty");
				if (!input || quantity.querySelector("[data-qty-minus]")) return;

				var minus = document.createElement("button");
				var plus = document.createElement("button");

				minus.type = "button";
				minus.className = "igsp__qty-button";
				minus.dataset.qtyMinus = "";
				minus.setAttribute("aria-label", "decrease quantity");
				minus.textContent = "−";

				plus.type = "button";
				plus.className = "igsp__qty-button";
				plus.dataset.qtyPlus = "";
				plus.setAttribute("aria-label", "increase quantity");
				plus.textContent = "+";

				quantity.insertBefore(minus, input);
				quantity.appendChild(plus);

				function changeQuantity(direction) {
					var step = Number(input.getAttribute("step")) || 1;
					var min = Number(input.getAttribute("min"));
					var max = Number(input.getAttribute("max"));
					var value = Number(input.value) || 0;

					if (!Number.isFinite(min)) min = 0;
					if (!Number.isFinite(max) || max <= 0) max = Infinity;

					value = Math.min(max, Math.max(min, value + (direction * step)));
					input.value = String(value);
					input.dispatchEvent(new Event("change", { bubbles: true }));
				}

				minus.addEventListener("click", function () {
					changeQuantity(-1);
				});

				plus.addEventListener("click", function () {
					changeQuantity(1);
				});
			});

			/**
			 * Replace visible WooCommerce variation selects with custom menus.
			 * The real selects remain in the form and receive the changes, so
			 * WooCommerce still controls availability, price and variation IDs.
			 */
			var variationPickers = [];
			var variationSyncers = [];

			function closeVariationPickers(except) {
				variationPickers.forEach(function (picker) {
					if (picker === except) return;
					picker.classList.remove("is-open");
					var trigger = picker.querySelector(".igsp__variation-trigger");
					if (trigger) trigger.setAttribute("aria-expanded", "false");
				});
			}

			Array.prototype.slice.call(
				root.querySelectorAll(".variations select")
			).forEach(function (select, selectIndex) {
				if (select.dataset.igspReady === "true") return;
				select.dataset.igspReady = "true";
				select.classList.add("igsp__native-select");

				var picker = document.createElement("div");
				var trigger = document.createElement("button");
				var triggerLabel = document.createElement("span");
				var menu = document.createElement("div");
				var menuId =
					<?php echo wp_json_encode( $instance_id ); ?> +
					"-variation-" +
					String(selectIndex);

				picker.className = "igsp__variation-picker";
				trigger.className = "igsp__variation-trigger";
				trigger.type = "button";
				trigger.setAttribute("aria-expanded", "false");
				trigger.setAttribute("aria-controls", menuId);

				menu.className = "igsp__variation-menu";
				menu.id = menuId;
				menu.setAttribute("role", "listbox");

				trigger.appendChild(triggerLabel);
				picker.appendChild(trigger);
				picker.appendChild(menu);
				select.insertAdjacentElement("afterend", picker);
				variationPickers.push(picker);

				Array.prototype.slice.call(select.options).forEach(function (nativeOption) {
					var option = document.createElement("button");
					option.className = "igsp__variation-option";
					option.type = "button";
					option.dataset.value = nativeOption.value;
					option.setAttribute("role", "option");
					option.setAttribute("aria-selected", "false");
					option.textContent = nativeOption.textContent;
					menu.appendChild(option);

					option.addEventListener("click", function () {
						if (option.disabled) return;
						select.value = option.dataset.value;
						select.dispatchEvent(new Event("change", { bubbles: true }));
						closeVariationPickers();
					});
				});

				function syncPicker() {
					var nativeOptions = Array.prototype.slice.call(select.options);
					var customOptions = Array.prototype.slice.call(
						menu.querySelectorAll(".igsp__variation-option")
					);
					var selectedOption = nativeOptions[select.selectedIndex] || nativeOptions[0];

					triggerLabel.textContent = selectedOption
						? selectedOption.textContent
						: "choose an option";

					customOptions.forEach(function (option, optionIndex) {
						var nativeOption = nativeOptions[optionIndex];
						var selected = nativeOption && nativeOption.value === select.value;
						option.disabled = !nativeOption || nativeOption.disabled;
						option.setAttribute("aria-selected", selected ? "true" : "false");
					});
				}

				variationSyncers.push(syncPicker);

				trigger.addEventListener("click", function (event) {
					event.stopPropagation();
					var willOpen = !picker.classList.contains("is-open");
					closeVariationPickers(picker);
					picker.classList.toggle("is-open", willOpen);
					trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
				});

				select.addEventListener("change", function () {
					window.setTimeout(syncPicker, 0);
				});

				if ("MutationObserver" in window) {
					new MutationObserver(syncPicker).observe(select, {
						attributes: true,
						childList: true,
						subtree: true
					});
				}

				syncPicker();
			});

			Array.prototype.slice.call(
				root.querySelectorAll(".reset_variations")
			).forEach(function (resetLink) {
				resetLink.addEventListener("click", function () {
					window.setTimeout(function () {
						variationSyncers.forEach(function (syncPicker) {
							syncPicker();
						});
					}, 0);
				});
			});

			document.addEventListener("click", function (event) {
				if (!event.target.closest(".igsp__variation-picker")) {
					closeVariationPickers();
				}
			});

			document.addEventListener("keydown", function (event) {
				if (event.key === "Escape") closeVariationPickers();
			});

			/**
			 * Fullscreen image viewer.
			 */
			var lightbox = root.querySelector("[data-lightbox]");
			var lightboxImage = root.querySelector("[data-lightbox-image]");
			var lightboxCount = root.querySelector("[data-lightbox-count]");
			var openButtons = Array.prototype.slice.call(
				root.querySelectorAll("[data-lightbox-open]")
			);
			var closeButton = root.querySelector("[data-lightbox-close]");
			var lightboxPrev = root.querySelector("[data-lightbox-prev]");
			var lightboxNext = root.querySelector("[data-lightbox-next]");
			var lightboxIndex = 0;
			var previousBodyOverflow = "";

			function renderLightbox(index) {
				if (!openButtons.length || !lightboxImage) return;
				lightboxIndex = (index + openButtons.length) % openButtons.length;

				var sourceButton = openButtons[lightboxIndex];
				lightboxImage.src = sourceButton.dataset.fullImage || "";
				lightboxImage.alt =
					<?php echo wp_json_encode( $product_name ); ?> +
					" — " +
					String(lightboxIndex + 1);

				if (lightboxCount) {
					lightboxCount.textContent =
						String(lightboxIndex + 1) + "/" + String(openButtons.length);
				}
			}

			function openLightbox(index) {
				if (!lightbox) return;
				renderLightbox(index);
				previousBodyOverflow = document.body.style.overflow;
				document.body.style.overflow = "hidden";
				lightbox.classList.add("is-open");
				lightbox.setAttribute("aria-hidden", "false");
				if (closeButton) closeButton.focus();
			}

			function closeLightbox() {
				if (!lightbox) return;
				lightbox.classList.remove("is-open");
				lightbox.setAttribute("aria-hidden", "true");
				document.body.style.overflow = previousBodyOverflow;
			}

			openButtons.forEach(function (button) {
				button.addEventListener("click", function () {
					openLightbox(Number(button.dataset.imageIndex || 0));
				});
			});

			if (closeButton) {
				closeButton.addEventListener("click", closeLightbox);
			}

			if (lightboxPrev) {
				lightboxPrev.addEventListener("click", function () {
					renderLightbox(lightboxIndex - 1);
				});
			}

			if (lightboxNext) {
				lightboxNext.addEventListener("click", function () {
					renderLightbox(lightboxIndex + 1);
				});
			}

			if (lightbox) {
				lightbox.addEventListener("click", function (event) {
					if (event.target === lightbox) closeLightbox();
				});
			}

			document.addEventListener("keydown", function (event) {
				if (!lightbox || !lightbox.classList.contains("is-open")) return;

				if (event.key === "Escape") closeLightbox();
				if (event.key === "ArrowLeft") renderLightbox(lightboxIndex - 1);
				if (event.key === "ArrowRight") renderLightbox(lightboxIndex + 1);
			});

			/**
			 * Keep mobile gallery and the sticky information panel clear
			 * of the existing fixed navbar, including its scroll resize.
			 */
			var siteHeader = document.getElementById("ioulia-header");
			var navbarFrame = 0;

			function syncNavbarHeight() {
				navbarFrame = 0;

				if (!siteHeader) {
					root.style.setProperty("--igsp-nav-bottom", "24px");
					return;
				}

				var bottom = Math.max(
					0,
					Math.ceil(siteHeader.getBoundingClientRect().bottom)
				);

				root.style.setProperty("--igsp-nav-bottom", (bottom + 10) + "px");
			}

			function requestNavbarSync() {
				if (navbarFrame) return;
				navbarFrame = window.requestAnimationFrame(syncNavbarHeight);
			}

			window.addEventListener("resize", requestNavbarSync);
			window.addEventListener("scroll", requestNavbarSync, { passive: true });

			if (siteHeader && "ResizeObserver" in window) {
				new ResizeObserver(requestNavbarSync).observe(siteHeader);
			}

			syncNavbarHeight();
			setCurrentSlide(0);
		})();
		</script>
	</section>
	<?php

	return ob_get_clean();
}
