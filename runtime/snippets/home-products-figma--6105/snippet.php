<?php
/**
 * Plugin Name: Infinite Products Hero Shortcode
 * Description: Draggable infinite WooCommerce field with editorial heading, edge blending and clean prices.
 * Version: 1.6.1 Hotfix
 * Author: Custom
 * Requires Plugins: woocommerce
 */




/**
 * Format prices without a trailing .00 / ,00.
 * Non-integer prices keep the store's normal decimal precision.
 */
if ( ! function_exists( 'mxl_iph_wc_price' ) ) {
	function mxl_iph_wc_price( $price ) {
		$price    = (float) $price;
		$decimals = abs( $price - round( $price ) ) < 0.00001 ? 0 : wc_get_price_decimals();

		return wc_price(
			$price,
			array(
				'decimals' => $decimals,
			)
		);
	}
}

if ( ! function_exists( 'mxl_iph_product_price_html' ) ) {
	function mxl_iph_product_price_html( $product ) {
		if ( ! $product instanceof WC_Product || '' === $product->get_price() ) {
			return '';
		}

		$suffix = $product->get_price_suffix();

		if ( $product->is_type( 'variable' ) ) {
			$minimum = (float) $product->get_variation_price( 'min', true );
			$maximum = (float) $product->get_variation_price( 'max', true );

			if ( abs( $minimum - $maximum ) < 0.00001 ) {
				return mxl_iph_wc_price( $minimum ) . $suffix;
			}

			return mxl_iph_wc_price( $minimum ) . ' <span aria-hidden="true">–</span> ' . mxl_iph_wc_price( $maximum ) . $suffix;
		}

		$current = (float) wc_get_price_to_display( $product );

		if ( $product->is_on_sale() && '' !== $product->get_regular_price() && '' !== $product->get_sale_price() ) {
			$regular = (float) wc_get_price_to_display(
				$product,
				array(
					'price' => $product->get_regular_price(),
				)
			);
			$sale = (float) wc_get_price_to_display(
				$product,
				array(
					'price' => $product->get_sale_price(),
				)
			);

			return '<del>' . mxl_iph_wc_price( $regular ) . '</del> <ins>' . mxl_iph_wc_price( $sale ) . '</ins>' . $suffix;
		}

		return mxl_iph_wc_price( $current ) . $suffix;
	}
}

/**
 * Usage:
 * [infinite_products_hero]
 *
 * Example:
 * [infinite_products_hero limit="10" heading_line_1="Νέες" heading_line_2="δημιουργίες" show_price="yes"]
 */
function mxl_infinite_products_hero_v161_shortcode( $atts = array() ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return current_user_can( 'manage_options' )
			? '<p><strong>Infinite Products Hero:</strong> Το WooCommerce δεν είναι ενεργό.</p>'
			: '';
	}

	$atts = shortcode_atts(
		array(
			'limit'             => '10',
			'show_price'        => 'yes',
			'hide_out_of_stock' => 'no',
			'subtract_header'   => 'yes',
			'header_selector'   => '#masthead, .site-header, header.elementor-location-header',
			'height'            => '112',
			'min_height'        => '620',
			'text_color'        => '#2B2B2B',
			'aria_label'        => 'Πρόσφατα προϊόντα',
			'cursor_drag'       => 'ΣΥΡΕ',
			'cursor_link'       => 'ΔΕΣ',
			'show_heading'      => 'yes',
			'heading_line_1'    => 'Νέες',
			'heading_line_2'    => 'δημιουργίες',
			'store_text'        => 'Δες τα κομμάτια',
			'store_url'         => '',
			'accent_color'      => '#7C3737',
			'edge_color'        => '#FFFEF7',
		),
		$atts,
		'infinite_products_hero'
	);

	$limit      = max( 1, min( 12, absint( $atts['limit'] ) ) );
	$show_price = in_array( strtolower( (string) $atts['show_price'] ), array( '1', 'yes', 'true', 'on' ), true );
	$hide_oos     = in_array( strtolower( (string) $atts['hide_out_of_stock'] ), array( '1', 'yes', 'true', 'on' ), true );
	$subtract     = in_array( strtolower( (string) $atts['subtract_header'] ), array( '1', 'yes', 'true', 'on' ), true );
	$show_heading = in_array( strtolower( (string) $atts['show_heading'] ), array( '1', 'yes', 'true', 'on' ), true );

	$height     = max( 60, min( 140, absint( $atts['height'] ) ) );
	$min_height = max( 320, min( 1200, absint( $atts['min_height'] ) ) );

	$text_color = sanitize_hex_color( $atts['text_color'] );
	$text_color = $text_color ? $text_color : '#2B2B2B';

	$accent_color = sanitize_hex_color( $atts['accent_color'] );
	$accent_color = $accent_color ? $accent_color : '#7C3737';

	$edge_color = sanitize_hex_color( $atts['edge_color'] );
	$edge_color = $edge_color ? $edge_color : '#FFFEF7';

	$store_url = ! empty( $atts['store_url'] )
		? esc_url_raw( $atts['store_url'] )
		: home_url( '/store/' );

	/*
	 * Over-fetch slightly, then apply WooCommerce visibility rules.
	 * This avoids exposing catalog-hidden products while preserving newest-first order.
	 */
	$query_args = array(
		'status'  => 'publish',
		'limit'   => min( 36, $limit * 3 ),
		'orderby' => 'date',
		'order'   => 'DESC',
		'return'  => 'objects',
	);

	$queried_products = wc_get_products( $query_args );
	$products         = array();

	foreach ( $queried_products as $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			continue;
		}

		if ( $hide_oos && ! $product->is_in_stock() ) {
			continue;
		}

		$products[] = $product;

		if ( count( $products ) >= $limit ) {
			break;
		}
	}

	if ( empty( $products ) ) {
		return current_user_can( 'manage_options' )
			? '<p><strong>Infinite Products Hero:</strong> Δεν βρέθηκαν ορατά προϊόντα.</p>'
			: '';
	}

	$instance_id = wp_unique_id( 'mxl-iph-' );
	$canvas_id   = $instance_id . '-canvas';
	$viewport_id = $instance_id . '-viewport';
	$cursor_id   = $instance_id . '-cursor';

	static $styles_printed = false;

	ob_start();

	if ( ! $styles_printed ) :
		$styles_printed = true;
		?>
		<style id="mxl-infinite-products-hero-css">
			.mxl-iph,
			.mxl-iph * { box-sizing: border-box; }

			.mxl-iph {
				--mxl-iph-text: #2B2B2B;
				--mxl-iph-accent: #7C3737;
				--mxl-iph-edge: #FFFEF7;
				--mxl-iph-page-x: clamp(16px, 2.35vw, 38px);
				--mxl-iph-blend: clamp(88px, 14vh, 176px);
				--mxl-iph-demo-x: -180px;
				--mxl-iph-demo-y: 128px;
				position: relative;
				width: 100%;
				height: 112vh;
				height: 112dvh;
				min-height: 620px;
				overflow: hidden;
				background: transparent;
				color: var(--mxl-iph-text);
				/* Keep the native cursor visible; the round label is only a follower. */
				cursor: auto !important;
				user-select: none;
				-webkit-user-select: none;
				isolation: isolate;
			}

			.mxl-iph::before,
			.mxl-iph::after {
				content: "";
				position: absolute;
				z-index: 4;
				left: 0;
				right: 0;
				height: var(--mxl-iph-blend);
				pointer-events: none;
			}

			.mxl-iph::before {
				top: 0;
				background: linear-gradient(
					to bottom,
					var(--mxl-iph-edge) 0%,
					rgba(255, 254, 247, .84) 28%,
					rgba(255, 254, 247, 0) 100%
				);
			}

			.mxl-iph::after {
				bottom: 0;
				background: linear-gradient(
					to top,
					var(--mxl-iph-edge) 0%,
					rgba(255, 254, 247, .84) 28%,
					rgba(255, 254, 247, 0) 100%
				);
			}

			.mxl-iph__heading-wrap {
				position: absolute;
				z-index: 6;
				top: 50%;
				left: 50%;
				width: min(88vw, 760px);
				color: var(--mxl-iph-text);
				text-align: center;
				pointer-events: none;
				transform: translate3d(-50%, -50%, 0);
			}

			.mxl-iph__heading {
				margin: 0;
				will-change: transform;
				color: inherit;
				font-family: inherit;
				font-size: clamp(32px, 2.45vw, 46px);
				font-weight: 400;
				line-height: 1.02;
				letter-spacing: -.045em;
				text-transform: none;
			}

			.mxl-iph__heading-line {
				display: block;
				opacity: 0;
				transform: translate3d(0, 14px, 0) scale(.99);
				filter: blur(1.5px);
				transition:
					opacity .72s ease,
					transform 1s cubic-bezier(.16, 1, .3, 1),
					filter .72s ease;
			}

			.mxl-iph__heading-line:nth-child(2) {
				transition-delay: 70ms;
			}

			.mxl-iph.has-reveal .mxl-iph__heading-line {
				opacity: 1;
				transform: translate3d(0, 0, 0) scale(1);
				filter: blur(0);
			}

			.mxl-iph__store {
				position: absolute;
				z-index: 8;
				top: calc(50% + clamp(92px, 11vh, 128px));
				left: 50%;
				opacity: 0;
				visibility: hidden;
				transform: translate3d(-50%, 16px, 0) scale(.985);
				pointer-events: none;
				will-change: opacity, transform;
			}

			.mxl-iph__store-link,
			.elementor .mxl-iph__store-link {
				appearance: none !important;
				display: inline-flex !important;
				align-items: center;
				justify-content: center;
				min-height: 42px;
				padding: 11px 18px !important;
				border: 1px solid var(--mxl-iph-text) !important;
				border-radius: 5px !important;
				background: var(--mxl-iph-text) !important;
				background-color: var(--mxl-iph-text) !important;
				background-image: none !important;
				color: var(--mxl-iph-edge) !important;
				box-shadow: none !important;
				font: inherit;
				font-size: var(--ioulia-micro);
				font-weight: 500;
				line-height: 1;
				letter-spacing: .025em;
				text-decoration: none !important;
				white-space: nowrap;
				transition:
					color .22s ease,
					background-color .22s ease,
					border-color .22s ease,
					transform .36s cubic-bezier(.16, 1, .3, 1) !important;
			}

			.mxl-iph__store-link:hover,
			.mxl-iph__store-link:focus-visible,
			.elementor .mxl-iph__store-link:hover,
			.elementor .mxl-iph__store-link:focus-visible {
				border-color: var(--mxl-iph-text) !important;
				background: var(--mxl-iph-edge) !important;
				background-color: var(--mxl-iph-edge) !important;
				color: var(--mxl-iph-text) !important;
				box-shadow: none !important;
				transform: translateY(-3px);
				outline: none !important;
			}

			.mxl-iph__store-link:focus-visible {
				outline: 1px solid var(--mxl-iph-accent) !important;
				outline-offset: 4px !important;
			}

			.mxl-iph__scene {
				position: absolute;
				inset: 0;
				will-change: transform;
				transform: translate3d(0, 0, 0);
			}

			.mxl-iph__drag-cue {
				position: absolute;
				z-index: 7;
				left: 50%;
				bottom: clamp(74px, 10vh, 118px);
				display: grid;
				place-items: center;
				width: 78px;
				height: 78px;
				border: 1px solid currentColor;
				border-radius: 50%;
				color: var(--mxl-iph-text);
				background: rgba(255, 254, 247, .72);
				background: color-mix(in srgb, var(--mxl-iph-edge) 72%, transparent);
				backdrop-filter: blur(5px);
				font: inherit;
				font-size: var(--ioulia-micro);
				font-weight: 500;
				line-height: 1;
				letter-spacing: .075em;
				text-transform: uppercase;
				opacity: 0;
				pointer-events: none;
				transform: translate3d(-50%, 18px, 0) scale(.82);
				will-change: opacity, transform;
			}

			.mxl-iph.has-reveal.is-drag-demo .mxl-iph__drag-cue {
				animation: mxl-iph-cue 3.35s cubic-bezier(.45, 0, .15, 1) .16s both;
			}

			.mxl-iph.is-hovered .mxl-iph__drag-cue,
			.mxl-iph.is-dragging .mxl-iph__drag-cue {
				opacity: 0 !important;
			}

			/*
			 * Hover only hides the guided cursor while its one-time animation
			 * keeps running. Cancelling it on hover would restart the demo
			 * every time the pointer leaves the button/viewport.
			 */
			.mxl-iph.is-dragging .mxl-iph__drag-cue {
				animation: none !important;
			}

			@keyframes mxl-iph-cue {
				0% {
					opacity: 0;
					transform: translate3d(-50%, 0, 0) scale(.9);
				}
				12% {
					opacity: .88;
					transform: translate3d(-50%, 0, 0) scale(1);
				}
				72% {
					opacity: .88;
					transform: translate3d(calc(-50% + var(--mxl-iph-demo-x)), var(--mxl-iph-demo-y), 0) scale(1);
				}
				88% {
					opacity: .5;
					transform: translate3d(calc(-50% + var(--mxl-iph-demo-x)), var(--mxl-iph-demo-y), 0) scale(.98);
				}
				100% {
					opacity: 0;
					transform: translate3d(calc(-50% + var(--mxl-iph-demo-x)), var(--mxl-iph-demo-y), 0) scale(.95);
				}
			}

			.mxl-iph__viewport {
				position: absolute;
				z-index: 1;
				inset: 0;
				overflow: hidden;
				touch-action: none;
				-webkit-mask-image: linear-gradient(
					to bottom,
					transparent 0,
					#000 var(--mxl-iph-blend),
					#000 calc(100% - var(--mxl-iph-blend)),
					transparent 100%
				);
				mask-image: linear-gradient(
					to bottom,
					transparent 0,
					#000 var(--mxl-iph-blend),
					#000 calc(100% - var(--mxl-iph-blend)),
					transparent 100%
				);
			}

			.mxl-iph__canvas {
				position: absolute;
				top: 0;
				left: 0;
				will-change: transform;
				transform: translate3d(0, 0, 0);
			}

			.mxl-iph__item {
				position: absolute;
				margin: 0;
				padding: 0;
				transition: filter .4s ease, opacity .4s ease;
			}

			.mxl-iph.is-item-hover .mxl-iph__item {
				filter: blur(5px);
				opacity: .27;
			}

			.mxl-iph.is-item-hover .mxl-iph__item.is-focused {
				filter: none;
				opacity: 1;
			}

			.mxl-iph__media {
				display: block;
				width: 100%;
				overflow: hidden;
				text-decoration: none;
				color: inherit;
				outline: none;
				cursor: pointer !important;
			}

			.mxl-iph__media:focus-visible {
				outline: 1px solid currentColor;
				outline-offset: 5px;
			}

			.mxl-iph__img {
				display: block;
				width: 100%;
				height: auto;
				pointer-events: none;
				-webkit-user-drag: none;
			}

			.mxl-iph__caption {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				gap: 10px;
				margin-top: 6px;
				font: inherit;
				font-size: var(--ioulia-small);
				line-height: 1.2;
				color: inherit;
				pointer-events: none;
				opacity: 0;
				transform: translateY(4px);
				transition: opacity .25s ease, transform .25s ease;
			}

			.mxl-iph__item:hover .mxl-iph__caption,
			.mxl-iph__item.is-focused .mxl-iph__caption {
				opacity: 1;
				transform: translateY(0);
			}

			.mxl-iph__title {
				min-width: 0;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			.mxl-iph__price {
				flex: 0 0 auto;
				white-space: nowrap;
				color: var(--mxl-iph-accent);
				opacity: .88;
			}

			.mxl-iph__price del { opacity: .55; }
			.mxl-iph__price ins { text-decoration: none; }


			.mxl-iph__cursor {
				position: fixed;
				top: 0;
				left: 0;
				z-index: 99999;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 76px;
				height: 76px;
				border: 1px solid currentColor;
				border-radius: 50%;
				font: inherit;
				font-size: var(--ioulia-micro);
				font-weight: 500;
				letter-spacing: .065em;
				line-height: 1;
				text-transform: uppercase;
				color: inherit;
				pointer-events: none;
				opacity: 0;
				transform: translate(-50%, -50%) scale(0);
				transition: opacity .2s ease, transform .28s cubic-bezier(.34, 1.56, .64, 1);
			}

			.mxl-iph.is-hovered .mxl-iph__cursor {
				opacity: 1;
				transform: translate(-50%, -50%) scale(1);
			}

			.mxl-iph.is-dragging .mxl-iph__cursor {
				transform: translate(-50%, -50%) scale(.78);
			}

			@media (max-width: 989px), (pointer: coarse) {
				.mxl-iph {
					--mxl-iph-blend: clamp(70px, 11vh, 112px);
					--mxl-iph-demo-x: -112px;
					--mxl-iph-demo-y: 94px;
					cursor: grab;
				}

				.mxl-iph__heading-wrap {
					width: calc(100% - (2 * var(--mxl-iph-page-x)));
				}

				.mxl-iph__heading {
					font-size: clamp(30px, 8.8vw, 44px);
				}

				.mxl-iph__store {
					top: calc(50% + clamp(88px, 21vw, 116px));
				}

				.mxl-iph__store-link,
				.elementor .mxl-iph__store-link {
					min-height: 40px;
					padding: 10px 16px !important;
				}

				.mxl-iph__drag-cue {
					bottom: clamp(58px, 9vh, 92px);
					width: 72px;
					height: 72px;
					font-size: var(--ioulia-micro);
				}
				.mxl-iph.is-dragging { cursor: grabbing; }
				.mxl-iph__cursor { display: none !important; }
				.mxl-iph__caption { font-size: var(--ioulia-micro); }
			}


			@media (prefers-reduced-motion: reduce) {
				.mxl-iph__heading-line {
					opacity: 1 !important;
					transform: none !important;
					filter: none !important;
				}

				.mxl-iph__drag-cue { display: none !important; }

				.mxl-iph__store,
				.mxl-iph__store-link {
					transition-duration: .01ms !important;
				}

				.mxl-iph__item,
				.mxl-iph__caption,
				.mxl-iph__cursor { transition-duration: .01ms !important; }
			}
		</style>
		<?php
	endif;
	?>

	<section
		id="<?php echo esc_attr( $instance_id ); ?>"
		class="mxl-iph"
		aria-label="<?php echo esc_attr( $atts['aria_label'] ); ?>"
		data-subtract-header="<?php echo $subtract ? '1' : '0'; ?>"
		data-height-vh="<?php echo esc_attr( $height ); ?>"
		data-header-selector="<?php echo esc_attr( $atts['header_selector'] ); ?>"
		data-cursor-drag="<?php echo esc_attr( $atts['cursor_drag'] ); ?>"
		data-cursor-link="<?php echo esc_attr( $atts['cursor_link'] ); ?>"
		style="--mxl-iph-text: <?php echo esc_attr( $text_color ); ?>; --mxl-iph-accent: <?php echo esc_attr( $accent_color ); ?>; --mxl-iph-edge: <?php echo esc_attr( $edge_color ); ?>; height: <?php echo esc_attr( $height ); ?>vh; height: <?php echo esc_attr( $height ); ?>dvh; min-height: <?php echo esc_attr( $min_height ); ?>px;"
	>
		<?php if ( $show_heading ) : ?>
			<header class="mxl-iph__heading-wrap">
				<h2 class="mxl-iph__heading">
					<span class="mxl-iph__heading-line"><?php echo esc_html( $atts['heading_line_1'] ); ?></span>
					<span class="mxl-iph__heading-line"><?php echo esc_html( $atts['heading_line_2'] ); ?></span>
				</h2>
			</header>
		<?php endif; ?>

		<div class="mxl-iph__store">
			<a class="mxl-iph__store-link" href="<?php echo esc_url( $store_url ); ?>">
				<?php echo esc_html( $atts['store_text'] ); ?>
			</a>
		</div>

		<div class="mxl-iph__drag-cue" aria-hidden="true">
			<?php echo esc_html( $atts['cursor_drag'] ); ?>
		</div>

		<div id="<?php echo esc_attr( $cursor_id ); ?>" class="mxl-iph__cursor" aria-hidden="true">
			<?php echo esc_html( $atts['cursor_drag'] ); ?>
		</div>

		<div id="<?php echo esc_attr( $viewport_id ); ?>" class="mxl-iph__viewport">
			<div class="mxl-iph__scene">
				<div id="<?php echo esc_attr( $canvas_id ); ?>" class="mxl-iph__canvas">
				<?php foreach ( $products as $index => $product ) : ?>
					<?php
					$product_id   = $product->get_id();
					$product_name = $product->get_name();
					$product_url  = $product->get_permalink();
					$image_id     = $product->get_image_id();
					$price_html   = $show_price ? mxl_iph_product_price_html( $product ) : '';
					?>
					<figure
						class="mxl-iph__item"
						data-original="1"
						data-item-index="<?php echo esc_attr( $index ); ?>"
						data-product-id="<?php echo esc_attr( $product_id ); ?>"
					>
						<a
							class="mxl-iph__media"
							href="<?php echo esc_url( $product_url ); ?>"
							aria-label="<?php echo esc_attr( $product_name ); ?>"
							draggable="false"
						>
							<?php
							if ( $image_id ) {
								echo wp_get_attachment_image(
									$image_id,
									'large',
									false,
									array(
										'class'     => 'mxl-iph__img',
										'alt'       => $product_name,
										'loading'   => 0 === $index ? 'eager' : 'lazy',
										'decoding'  => 'async',
										'draggable' => 'false',
										'fetchpriority' => 0 === $index ? 'high' : 'auto',
									)
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								printf(
									'<img class="mxl-iph__img" src="%1$s" alt="%2$s" loading="lazy" decoding="async" draggable="false">',
									esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ),
									esc_attr( $product_name )
								);
							}
							?>
						</a>

						<figcaption class="mxl-iph__caption">
							<span class="mxl-iph__title"><?php echo esc_html( $product_name ); ?></span>
							<?php if ( $price_html ) : ?>
								<span class="mxl-iph__price"><?php echo wp_kses_post( $price_html ); ?></span>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
				</div>
			</div>
		</div>

	</section>

	<script>
	(function () {
		'use strict';

		var section = document.getElementById(<?php echo wp_json_encode( $instance_id ); ?>);
		if (!section || section.dataset.mxlIphReady === '1') return;
		section.dataset.mxlIphReady = '1';

		var viewport = document.getElementById(<?php echo wp_json_encode( $viewport_id ); ?>);
		var canvas   = document.getElementById(<?php echo wp_json_encode( $canvas_id ); ?>);
		var cursorEl = document.getElementById(<?php echo wp_json_encode( $cursor_id ); ?>);
		var headingEl = section.querySelector('.mxl-iph__heading');
		var storeButton = section.querySelector('.mxl-iph__store');
		if (!viewport || !canvas) return;

		var GRID = 3;
		var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var coarsePointer = window.matchMedia('(pointer: coarse)').matches;
		var dragThreshold = coarsePointer ? 12 : 8;

		var positionsDesktop = [
			{x:1060,y:70,w:260}, {x:160,y:180,w:220}, {x:1740,y:80,w:200},
			{x:60,y:720,w:240}, {x:860,y:650,w:220}, {x:1610,y:660,w:190},
			{x:450,y:1080,w:230}, {x:1300,y:1080,w:205}, {x:1940,y:950,w:180},
			{x:540,y:390,w:210}, {x:1450,y:300,w:235}, {x:1120,y:920,w:195}
		];

		var positionsMobile = [
			{x:470,y:50,w:170}, {x:60,y:110,w:145}, {x:760,y:40,w:130},
			{x:40,y:430,w:155}, {x:390,y:390,w:145}, {x:760,y:360,w:125},
			{x:190,y:650,w:150}, {x:570,y:620,w:135}, {x:850,y:690,w:110},
			{x:270,y:260,w:130}, {x:590,y:190,w:145}, {x:860,y:190,w:115}
		];

		var originals = Array.prototype.slice.call(canvas.querySelectorAll('.mxl-iph__item[data-original="1"]'));
		var allItems = [];
		var tileW = 2200;
		var tileH = 1400;
		var initPosX = 0;
		var initPosY = 0;
		var posX = 0;
		var posY = 0;
		var startX = 0;
		var startY = 0;
		var lastX = 0;
		var lastY = 0;
		var downX = 0;
		var downY = 0;
		var velX = 0;
		var velY = 0;
		var dragging = false;
		var hasDragged = false;
		var suppressClick = false;
		var pointerId = null;
		var captureActive = false;
		var momentumFrame = null;
		var parallaxX = 0;
		var parallaxY = 0;
		var targetPX = 0;
		var targetPY = 0;
		var parallaxStrength = reducedMotion ? 0 : 48;
		var lerp = 0.065;
		var resizeTimer = null;

		function getHeaderHeight() {
			if (section.dataset.subtractHeader !== '1') return 0;

			var selector = section.dataset.headerSelector || '';
			if (!selector) return 0;

			try {
				var header = document.querySelector(selector);
				return header ? header.getBoundingClientRect().height : 0;
			} catch (error) {
				return 0;
			}
		}

		function setSectionHeight() {
			var cssMinHeight = parseFloat(window.getComputedStyle(section).minHeight) || 320;
			var heightVh = parseFloat(section.dataset.heightVh || '100');
			var desired = window.innerHeight * (heightVh / 100) - getHeaderHeight();
			section.style.height = Math.max(cssMinHeight, desired) + 'px';
		}

		function getSectionHeight() {
			return section.getBoundingClientRect().height || window.innerHeight;
		}

		function applyTransform() {
			canvas.style.transform = 'translate3d(' +
				(posX + parallaxX).toFixed(2) + 'px,' +
				(posY + parallaxY).toFixed(2) + 'px,0)';
		}

		function snap() {
			while (posX - initPosX <= -tileW) { posX += tileW; startX -= tileW; }
			while (posX - initPosX >=  tileW) { posX -= tileW; startX += tileW; }
			while (posY - initPosY <= -tileH) { posY += tileH; startY -= tileH; }
			while (posY - initPosY >=  tileH) { posY -= tileH; startY += tileH; }
		}

		function resetPosition() {
			initPosX = window.innerWidth / 2 - (GRID / 2) * tileW;
			initPosY = getSectionHeight() / 2 - (GRID / 2) * tileH;
			posX = initPosX;
			posY = initPosY;
			startX = 0;
			startY = 0;
			parallaxX = 0;
			parallaxY = 0;
			targetPX = 0;
			targetPY = 0;
			applyTransform();
		}

		function buildCanvas() {
			var mobile = window.innerWidth < 990;
			var positions = mobile ? positionsMobile : positionsDesktop;
			tileW = mobile ? 1080 : 2200;
			tileH = mobile ? 900 : 1400;

			Array.prototype.slice.call(canvas.querySelectorAll('[data-clone="1"]')).forEach(function (clone) {
				clone.remove();
			});

			canvas.style.width = (GRID * tileW) + 'px';
			canvas.style.height = (GRID * tileH) + 'px';
			allItems = [];

			originals.forEach(function (item, index) {
				var point = positions[index % positions.length];

				for (var row = 0; row < GRID; row++) {
					for (var col = 0; col < GRID; col++) {
						var isCentre = row === 1 && col === 1;
						var node = isCentre ? item : item.cloneNode(true);

						if (!isCentre) {
							node.dataset.clone = '1';
							node.removeAttribute('data-original');
							canvas.appendChild(node);
						}

						node.style.left = (col * tileW + point.x) + 'px';
						node.style.top = (row * tileH + point.y) + 'px';
						node.style.width = point.w + 'px';
						node.dataset.itemIndex = String(index);
						allItems.push(node);
					}
				}
			});

			resetPosition();
		}

		function activateItem(index) {
			if (dragging) return;
			section.classList.add('is-item-hover');
			if (cursorEl) cursorEl.textContent = section.dataset.cursorLink || 'VIEW';

			allItems.forEach(function (item) {
				item.classList.toggle('is-focused', item.dataset.itemIndex === index);
			});
		}

		function resetItems() {
			section.classList.remove('is-item-hover');
			if (cursorEl) cursorEl.textContent = section.dataset.cursorDrag || 'ΣΥΡΕ';
			allItems.forEach(function (item) { item.classList.remove('is-focused'); });
		}

		function cancelMomentum() {
			if (momentumFrame) {
				window.cancelAnimationFrame(momentumFrame);
				momentumFrame = null;
			}
		}

		function runMomentum() {
			if (reducedMotion) return;

			cancelMomentum();
			momentumFrame = window.requestAnimationFrame(function momentumLoop() {
				if (Math.abs(velX) < 0.18 && Math.abs(velY) < 0.18) {
					velX = 0;
					velY = 0;
					momentumFrame = null;
					return;
				}

				velX *= 0.91;
				velY *= 0.91;
				posX += velX;
				posY += velY;
				snap();
				applyTransform();
				momentumFrame = window.requestAnimationFrame(momentumLoop);
			});
		}

		function startDrag(event) {
			if (event.pointerType === 'mouse' && event.button !== 0) return;

			if (guidedDragFrame) {
				window.cancelAnimationFrame(guidedDragFrame);
				guidedDragFrame = null;
				guidedDragActive = false;
			}

			cancelMomentum();
			dragging = true;
			hasDragged = false;
			suppressClick = false;
			pointerId = event.pointerId;
			startX = event.clientX - posX;
			startY = event.clientY - posY;
			lastX = event.clientX;
			lastY = event.clientY;
			downX = event.clientX;
			downY = event.clientY;
			velX = 0;
			velY = 0;
			targetPX = 0;
			targetPY = 0;
			section.classList.add('is-dragging');

		}

		function moveDrag(event) {
			if (cursorEl && event.pointerType !== 'touch') {
				cursorEl.style.left = event.clientX + 'px';
				cursorEl.style.top = event.clientY + 'px';
			}

			if (!dragging || event.pointerId !== pointerId) {
				if (!coarsePointer) {
					targetPX = (event.clientX / window.innerWidth - 0.5) * parallaxStrength;
					targetPY = (event.clientY / Math.max(1, getSectionHeight()) - 0.5) * parallaxStrength;
				}
				return;
			}

			var dx = event.clientX - lastX;
			var dy = event.clientY - lastY;
			var totalX = Math.abs(event.clientX - downX);
			var totalY = Math.abs(event.clientY - downY);

			if ((totalX > dragThreshold || totalY > dragThreshold) && !hasDragged) {
				hasDragged = true;

				/* Capture only after a genuine drag, so clicks remain product links. */
				if (viewport.setPointerCapture) {
					try {
						viewport.setPointerCapture(pointerId);
						captureActive = true;
					} catch (error) {}
				}

				resetItems();
			}

			velX = dx;
			velY = dy;
			lastX = event.clientX;
			lastY = event.clientY;
			posX = event.clientX - startX;
			posY = event.clientY - startY;
			snap();
			applyTransform();
		}

		function endDrag(event) {
			if (!dragging || (event && event.pointerId !== pointerId)) return;

			dragging = false;
			suppressClick = hasDragged;
			section.classList.remove('is-dragging');

			if (captureActive && viewport.releasePointerCapture && pointerId !== null) {
				try { viewport.releasePointerCapture(pointerId); } catch (error) {}
			}

			captureActive = false;
			pointerId = null;
			runMomentum();
		}

		viewport.addEventListener('pointerenter', function () {
			section.classList.add('is-hovered');
		});

		viewport.addEventListener('pointerleave', function () {
			section.classList.remove('is-hovered');
			resetItems();
			targetPX = 0;
			targetPY = 0;
			if (dragging) endDrag();
		});

		viewport.addEventListener('pointerover', function (event) {
			var item = event.target.closest('.mxl-iph__item');
			if (item && viewport.contains(item)) activateItem(item.dataset.itemIndex);
		});

		viewport.addEventListener('pointerout', function (event) {
			var fromItem = event.target.closest('.mxl-iph__item');
			var toItem = event.relatedTarget && event.relatedTarget.closest
				? event.relatedTarget.closest('.mxl-iph__item')
				: null;

			if (fromItem && (!toItem || fromItem.dataset.itemIndex !== toItem.dataset.itemIndex)) {
				resetItems();
			}
		});

		viewport.addEventListener('pointerdown', startDrag);
		viewport.addEventListener('pointermove', moveDrag);
		viewport.addEventListener('pointerup', endDrag);
		viewport.addEventListener('pointercancel', endDrag);

		viewport.addEventListener('click', function (event) {
			if (suppressClick) {
				event.preventDefault();
				event.stopPropagation();
				suppressClick = false;
			}
		}, true);

		viewport.addEventListener('dragstart', function (event) {
			event.preventDefault();
		});

		(function parallaxLoop() {
			parallaxX += (targetPX - parallaxX) * lerp;
			parallaxY += (targetPY - parallaxY) * lerp;
			if (!momentumFrame && !dragging) applyTransform();
			window.requestAnimationFrame(parallaxLoop);
		})();

		section.classList.add('has-reveal');

		var dragDemoPlayed = false;
		var dragDemoCheckFrame = null;
		var guidedDragFrame = null;
		var guidedDragActive = false;

		function smootherStep(value) {
			var t = Math.max(0, Math.min(1, value));
			return t * t * t * (t * (t * 6 - 15) + 10);
		}

		function updateHeadingParallax() {
			if (!headingEl || reducedMotion) return;

			var rect = section.getBoundingClientRect();
			var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
			var travel = Math.max(rect.height + viewportHeight, 1);
			var normalized = Math.max(0, Math.min(1, (viewportHeight - rect.top) / travel));

			/* Only 10px total travel: intentionally almost imperceptible. */
			var offsetY = (0.5 - normalized) * 10;
			headingEl.style.transform = 'translate3d(0,' + offsetY.toFixed(2) + 'px,0)';
		}

		function updateStoreButton() {
			if (!storeButton) return;

			var rect = section.getBoundingClientRect();
			var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
			var revealDistance = Math.max(viewportHeight * 0.18, 1);

			/*
			 * Fade in during the final part of the section entering the viewport,
			 * then fade out only as its lower edge leaves.
			 */
			var enterRaw = (revealDistance - rect.top) / revealDistance;
			var exitRaw = (rect.bottom - revealDistance) / revealDistance;
			var enter = smootherStep(Math.max(0, Math.min(1, enterRaw)));
			var exit = smootherStep(Math.max(0, Math.min(1, exitRaw)));
			var visibility = Math.min(enter, exit);

			storeButton.style.opacity = visibility.toFixed(3);
			storeButton.style.visibility = visibility > 0.01 ? 'visible' : 'hidden';
			storeButton.style.pointerEvents = visibility > 0.82 ? 'auto' : 'none';
			storeButton.style.transform =
				'translate3d(-50%,' + (16 * (1 - visibility)).toFixed(2) + 'px,0) scale(' +
				(0.985 + 0.015 * visibility).toFixed(4) + ')';
		}

		function playGuidedDrag() {
			if (guidedDragActive || dragDemoPlayed || reducedMotion) return;

			dragDemoPlayed = true;
			guidedDragActive = true;
			section.classList.add('is-drag-demo');

			var startTime = performance.now();
			var duration = 3200;
			var fromX = posX;
			var fromY = posY;
			var sectionStyles = window.getComputedStyle(section);
			var distanceX = parseFloat(sectionStyles.getPropertyValue('--mxl-iph-demo-x')) || -180;
			var distanceY = parseFloat(sectionStyles.getPropertyValue('--mxl-iph-demo-y')) || 128;

			cancelMomentum();
			velX = 0;
			velY = 0;
			targetPX = 0;
			targetPY = 0;

			function guidedDragTick(now) {
				if (!guidedDragActive) {
					guidedDragFrame = null;
					return;
				}

				var raw = Math.min(1, (now - startTime) / duration);
				var eased = smootherStep(raw);

				posX = fromX + distanceX * eased;
				posY = fromY + distanceY * eased;
				snap();
				applyTransform();

				if (raw < 1) {
					guidedDragFrame = window.requestAnimationFrame(guidedDragTick);
					return;
				}

				/*
				 * posX/posY now contain the final position.
				 * Nothing is removed or reset, therefore there is no jump-back.
				 */
				posX = fromX + distanceX;
				posY = fromY + distanceY;
				snap();
				applyTransform();

				guidedDragActive = false;
				guidedDragFrame = null;
			}

			guidedDragFrame = window.requestAnimationFrame(guidedDragTick);
		}

		function maybePlayDragDemo() {
			if (dragDemoPlayed || reducedMotion) return;

			var rect = section.getBoundingClientRect();
			var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
			var tolerance = 4;

			var viewportFullyCovered =
				rect.top <= tolerance &&
				rect.bottom >= viewportHeight - tolerance;

			if (viewportFullyCovered) {
				playGuidedDrag();
			}
		}

		function scheduleSectionEffects() {
			updateHeadingParallax();
			updateStoreButton();

			if (dragDemoPlayed || dragDemoCheckFrame) return;

			dragDemoCheckFrame = window.requestAnimationFrame(function () {
				dragDemoCheckFrame = null;
				maybePlayDragDemo();
			});
		}

		window.addEventListener('scroll', scheduleSectionEffects, { passive: true });
		window.addEventListener('resize', scheduleSectionEffects, { passive: true });
		scheduleSectionEffects();

		window.addEventListener('resize', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(function () {
				cancelMomentum();
				setSectionHeight();
				buildCanvas();
			}, 120);
		});

		window.requestAnimationFrame(function () {
			setSectionHeight();
			buildCanvas();
			section.classList.add('is-ready');
		});
	})();
	</script>
	<?php

	return ob_get_clean();
}
remove_shortcode( 'infinite_products_hero' );
add_shortcode( 'infinite_products_hero', 'mxl_infinite_products_hero_v161_shortcode' );

/* Diagnostic alias: useful if another old snippet re-registers the original shortcode later. */
add_shortcode( 'infinite_products_hero_v161', 'mxl_infinite_products_hero_v161_shortcode' );
