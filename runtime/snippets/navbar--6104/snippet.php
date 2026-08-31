<?php
/**
 * Ioulia navbar + WooCommerce mini cart.
 * Copy the complete contents into a PHP snippet and run it everywhere.
 */

if ( ! function_exists( 'ioulia_mini_cart_count_markup' ) ) {
    function ioulia_mini_cart_count_markup() {
        $count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

        return sprintf(
            '<span class="ioulia-cart-count%1$s" aria-hidden="true">%2$d</span>',
            0 === $count ? ' is-empty' : '',
            (int) $count
        );
    }
}

if ( ! function_exists( 'ioulia_mini_cart_shell' ) ) {
    function ioulia_mini_cart_shell() {
        ob_start();
        ?>
        <div class="ioulia-mini-cart-shell" aria-live="polite">
            <?php if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) : ?>
                <div class="ioulia-mini-cart-items">
                    <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

                        if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] < 1 ) {
                            continue;
                        }

                        $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                        $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                        $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
                        $quantity          = (int) $cart_item['quantity'];
                        $max_quantity      = $_product->get_max_purchase_quantity();
                        ?>
                        <article class="ioulia-mini-cart-item" data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>">
                            <div class="ioulia-mini-cart-thumb">
                                <?php if ( $product_permalink ) : ?>
                                    <a href="<?php echo esc_url( $product_permalink ); ?>" tabindex="-1"><?php echo wp_kses_post( $thumbnail ); ?></a>
                                <?php else : ?>
                                    <?php echo wp_kses_post( $thumbnail ); ?>
                                <?php endif; ?>
                            </div>

                            <div class="ioulia-mini-cart-product">
                                <div class="ioulia-mini-cart-product-top">
                                    <div>
                                        <?php if ( $product_permalink ) : ?>
                                            <a class="ioulia-mini-cart-name" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
                                        <?php else : ?>
                                            <span class="ioulia-mini-cart-name"><?php echo wp_kses_post( $product_name ); ?></span>
                                        <?php endif; ?>
                                        <div class="ioulia-mini-cart-meta"><?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?></div>
                                    </div>
                                    <button class="ioulia-mini-cart-remove" type="button" data-ioulia-remove aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ); ?>">
                                        <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M4 4 L12 12 M12 4 L4 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    </button>
                                </div>

                                <div class="ioulia-mini-cart-product-bottom">
                                    <div class="ioulia-mini-cart-quantity" aria-label="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
                                        <button type="button" data-ioulia-quantity="<?php echo esc_attr( max( 0, $quantity - 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Decrease quantity', 'woocommerce' ); ?>">−</button>
                                        <span><?php echo esc_html( $quantity ); ?></span>
                                        <button type="button" data-ioulia-quantity="<?php echo esc_attr( $quantity + 1 ); ?>"<?php echo ( $max_quantity > 0 && $quantity >= $max_quantity ) ? ' disabled' : ''; ?> aria-label="<?php esc_attr_e( 'Increase quantity', 'woocommerce' ); ?>">+</button>
                                    </div>
                                    <span class="ioulia-mini-cart-line-price"><?php echo wp_kses_post( WC()->cart->get_product_subtotal( $_product, $quantity ) ); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="ioulia-mini-cart-footer">
                    <div class="ioulia-mini-cart-subtotal">
                        <span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
                        <strong><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong>
                    </div>
                    <p class="ioulia-mini-cart-note">Τα μεταφορικά και οι φόροι υπολογίζονται στο ταμείο.</p>
                    <a class="ioulia-mini-cart-checkout" href="<?php echo esc_url( wc_get_cart_url() ); ?>">δες το καλάθι</a>
                </div>
            <?php else : ?>
                <div class="ioulia-mini-cart-empty">
                    <p>το καλάθι σου είναι άδειο.</p>
                    <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">δες το κατάστημα</a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'ioulia_mini_cart_fragments' ) ) {
    function ioulia_mini_cart_fragments( $fragments ) {
        $fragments['.ioulia-mini-cart-shell'] = ioulia_mini_cart_shell();
        $fragments['.ioulia-cart-count']      = ioulia_mini_cart_count_markup();
        return $fragments;
    }
    add_filter( 'woocommerce_add_to_cart_fragments', 'ioulia_mini_cart_fragments' );
}

if ( ! function_exists( 'ioulia_mini_cart_ajax' ) ) {
    function ioulia_mini_cart_ajax() {
        check_ajax_referer( 'ioulia_mini_cart', 'nonce' );

        if ( ! function_exists( 'WC' ) ) {
            wp_send_json_error( array( 'message' => 'WooCommerce is not available.' ), 400 );
        }

        if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
            wc_load_cart();
        }

        $cart_item_key = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( $_POST['cart_item_key'] ) ) : '';
        $operation     = isset( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : '';

        $cart_contents = WC()->cart->get_cart();

        if ( ! $cart_item_key || ! isset( $cart_contents[ $cart_item_key ] ) ) {
            wp_send_json_error( array( 'message' => 'This cart item could not be found.' ), 404 );
        }

        if ( 'remove' === $operation ) {
            WC()->cart->remove_cart_item( $cart_item_key );
        } elseif ( 'quantity' === $operation ) {
            $quantity = isset( $_POST['quantity'] ) ? max( 0, wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) ) : 1;
            WC()->cart->set_quantity( $cart_item_key, $quantity, true );
        } else {
            wp_send_json_error( array( 'message' => 'Unknown cart operation.' ), 400 );
        }

        WC()->cart->calculate_totals();

        wp_send_json_success(
            array(
                'html'  => ioulia_mini_cart_shell(),
                'count' => (int) WC()->cart->get_cart_contents_count(),
            )
        );
    }
    add_action( 'wp_ajax_ioulia_mini_cart', 'ioulia_mini_cart_ajax' );
    add_action( 'wp_ajax_nopriv_ioulia_mini_cart', 'ioulia_mini_cart_ajax' );
}

add_shortcode( 'ioulia_navbar', 'ioulia_custom_navbar_shortcode' );
function ioulia_custom_navbar_shortcode() {
    if ( function_exists( 'wp_enqueue_script' ) ) {
        wp_enqueue_script( 'wc-cart-fragments' );
    }

    ob_start();
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600&display=swap');

        :root {
            --ioulia-bg-dark: #7C3737;
            --ioulia-cream: #FFFEF7;
            --ioulia-peach: #FECAA7; 
            --ioulia-dark: #2b2b2b;
            --ioulia-font: 'Montserrat', sans-serif;
            --ioulia-page-x: clamp(28px, 3.05vw, 46px); /* Ευθυγράμμιση με το About Hero */
            --ioulia-snappy-ease: cubic-bezier(0.7, 0, 0.15, 1);
            --ioulia-smooth-ease: cubic-bezier(0.33, 1, 0.68, 1);
        }

        /* --- Header Container (Boxed 2200px & Matching Padding) --- */
        #ioulia-header {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: var(--ioulia-shell);
            padding: 4em var(--ioulia-page-x) 0 var(--ioulia-page-x);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 99999;
            transition: padding 0.6s var(--ioulia-snappy-ease);
            box-sizing: border-box;
            pointer-events: none;
        }

        #ioulia-header > * {
            pointer-events: auto;
        }

        #ioulia-header.scrolled {
            padding-top: 2em;
        }

        /* --- Icons --- */
        .ioulia-logo-circle {
            width: 99px;
            height: 99px;
            border: 2px solid var(--ioulia-dark);
            border-radius: 50%;
            background-color: transparent;
            transition: border-color 0.5s ease, transform 0.4s var(--ioulia-snappy-ease);
        }
        .ioulia-logo-circle:hover {
            transform: scale(1.08);
        }

        .ioulia-nav-right {
            display: flex;
            align-items: center;
            gap: 4em;
        }

        /* Language switcher. Inherits the header ink so the two header states
           below only have to change one colour. */
        .ioulia-lang-switcher {
            display: flex;
            align-items: center;
            gap: .5em;
            color: var(--ioulia-dark);
            font-size: var(--ioulia-small);
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
            transition: color 0.5s ease;
        }
        .ioulia-lang-switcher a {
            color: inherit;
            text-decoration: none;
            opacity: .45;
            transition: opacity .3s ease;
        }
        .ioulia-lang-switcher a:hover,
        .ioulia-lang-switcher a.is-current { opacity: 1; }
        .ioulia-lang-sep { opacity: .28; }

        .ioulia-cart-icon {
            position: relative;
            display: grid !important;
            width: 42px;
            height: 42px;
            padding: 0 !important;
            place-items: center;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--ioulia-dark) !important;
            cursor: pointer;
            appearance: none;
        }

        .ioulia-cart-icon:hover,
        .ioulia-cart-icon:focus,
        .ioulia-cart-icon:active {
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--ioulia-dark) !important;
            outline: none !important;
        }

        .ioulia-cart-icon:focus-visible {
            outline: 1px solid currentColor !important;
            outline-offset: 7px !important;
        }

        .ioulia-cart-icon svg {
            width: 38px;
            height: auto;
            fill: var(--ioulia-dark);
            transition: fill 0.5s ease, transform 0.4s var(--ioulia-snappy-ease);
            display: block;
        }
        .ioulia-cart-icon:hover svg {
            transform: scale(1.1);
        }

        .ioulia-cart-count {
            position: absolute;
            top: -5px;
            right: -7px;
            display: grid;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            place-items: center;
            border-radius: 999px;
            background: var(--ioulia-dark);
            color: var(--ioulia-cream);
            font-family: var(--ioulia-font);
            font-size: var(--ioulia-micro);
            font-weight: 400;
            line-height: 1;
            box-sizing: border-box;
            transition: background-color .35s ease, color .35s ease, transform .35s var(--ioulia-smooth-ease);
        }

        .ioulia-cart-count.is-empty {
            transform: scale(0);
        }

        /* --- WooCommerce mini cart --- */
        .ioulia-mini-cart-backdrop {
            position: fixed;
            inset: 0;
            z-index: 100000;
            background: rgba(24, 24, 22, .3);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            opacity: 0;
            visibility: hidden;
            transition: opacity .34s ease, visibility .34s ease;
        }

        .ioulia-mini-cart-backdrop.is-open {
            opacity: 1;
            visibility: visible;
        }

        .ioulia-mini-cart-panel {
            position: fixed;
            top: 12px;
            right: 12px;
            bottom: 12px;
            z-index: 100001;
            display: flex;
            width: min(480px, calc(100% - 24px));
            height: auto;
            padding: 0;
            flex-direction: column;
            overflow-x: hidden !important;
            overflow-y: hidden !important;
            overscroll-behavior-y: contain;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-y;
            background: var(--ioulia-cream);
            color: var(--ioulia-dark);
            border: 1px solid rgba(43, 43, 43, .1);
            border-radius: 24px;
            box-shadow: -20px 18px 80px rgba(20, 20, 18, .16);
            transform: translate3d(calc(100% + 24px), 0, 0);
            transition: none;
            box-sizing: border-box;
            contain: layout paint;
        }

        .ioulia-mini-cart-panel.is-ready {
            transition: transform .52s cubic-bezier(.16, 1, .3, 1);
        }

        .ioulia-mini-cart-panel.is-open {
            transform: translate3d(0, 0, 0);
        }

        .ioulia-mini-cart-panel.is-closing { pointer-events: none; }

        .ioulia-mini-cart-grab { display: none; }

        .ioulia-mini-cart-header {
            display: flex;
            padding: 22px 24px 18px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(43, 43, 43, .16);
            background: var(--ioulia-cream);
        }

        .ioulia-mini-cart-header,
        .ioulia-mini-cart-item,
        .ioulia-mini-cart-footer,
        .ioulia-mini-cart-empty {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity .32s ease, transform .48s var(--ioulia-smooth-ease);
        }

        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-header,
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-item,
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-footer,
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-empty {
            opacity: 1;
            transform: translateY(0);
        }

        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-header {
            transition-delay: .08s;
        }

        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-item:nth-child(1) { transition-delay: .12s; }
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-item:nth-child(2) { transition-delay: .16s; }
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-item:nth-child(3) { transition-delay: .20s; }
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-item:nth-child(4) { transition-delay: .24s; }
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-item:nth-child(n+5) { transition-delay: .27s; }
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-footer,
        .ioulia-mini-cart-panel.is-open .ioulia-mini-cart-empty {
            transition-delay: .18s;
        }

        .ioulia-mini-cart-title {
            margin: 0 !important;
            color: var(--ioulia-dark) !important;
            font-family: var(--ioulia-font) !important;
            font-size: clamp(34px, 3vw, 44px) !important;
            font-weight: 400 !important;
            line-height: 1 !important;
            letter-spacing: -.045em !important;
            text-transform: lowercase;
        }

        .ioulia-mini-cart-close,
        .ioulia-mini-cart-remove,
        .ioulia-mini-cart-quantity button {
            min-width: 0 !important;
            min-height: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            color: var(--ioulia-dark) !important;
            font-family: var(--ioulia-font) !important;
            font-weight: 400 !important;
            text-transform: lowercase !important;
            cursor: pointer;
            appearance: none;
        }

        .ioulia-mini-cart-close:hover,
        .ioulia-mini-cart-close:focus,
        .ioulia-mini-cart-close:active,
        .ioulia-mini-cart-remove:hover,
        .ioulia-mini-cart-remove:focus,
        .ioulia-mini-cart-remove:active,
        .ioulia-mini-cart-quantity button:hover,
        .ioulia-mini-cart-quantity button:focus,
        .ioulia-mini-cart-quantity button:active {
            border: 0 !important;
            background: transparent !important;
            color: var(--ioulia-dark) !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .ioulia-mini-cart-close:focus-visible,
        .ioulia-mini-cart-remove:focus-visible,
        .ioulia-mini-cart-quantity button:focus-visible,
        .ioulia-mini-cart-checkout:focus-visible,
        .ioulia-mini-cart-empty a:focus-visible {
            outline: 1px solid var(--ioulia-dark) !important;
            outline-offset: 4px !important;
        }

        .ioulia-mini-cart-close {
            display: grid !important;
            width: 44px !important;
            height: 44px !important;
            place-items: center;
            border: 1px solid rgba(43, 43, 43, .16) !important;
            border-radius: 999px !important;
            transition: border-color .22s ease, background-color .22s ease, transform .28s cubic-bezier(.16, 1, .3, 1) !important;
        }

        .ioulia-mini-cart-close:hover,
        .ioulia-mini-cart-close:focus-visible {
            border-color: var(--ioulia-dark) !important;
            background: rgba(43, 43, 43, .05) !important;
            transform: rotate(3deg) scale(1.04);
        }

        .ioulia-mini-cart-close svg { width: 15px; height: 15px; }

        .ioulia-mini-cart-shell {
            display: flex;
            min-height: 0;
            flex: 1;
            flex-direction: column;
            overflow: hidden !important;
        }

        .ioulia-mini-cart-items {
            min-height: 0;
            padding: 12px 16px 16px;
            flex: 1 1 0;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(43, 43, 43, .2) transparent;
        }

        .ioulia-mini-cart-items::-webkit-scrollbar { width: 5px; }
        .ioulia-mini-cart-items::-webkit-scrollbar-track { background: transparent; }
        .ioulia-mini-cart-items::-webkit-scrollbar-thumb { border-radius: 999px; background: rgba(43, 43, 43, .2); }

        .ioulia-mini-cart-item {
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 10px;
            padding: 12px;
            border: 1px solid rgba(43, 43, 43, .12);
            border-radius: 18px;
            background: rgba(255, 255, 255, .38);
        }

        .ioulia-mini-cart-item.is-updating {
            opacity: .42;
            pointer-events: none;
        }

        .ioulia-mini-cart-thumb {
            aspect-ratio: 4 / 5;
            overflow: hidden;
            border-radius: 13px;
            background: #f1efe8;
        }

        .ioulia-mini-cart-thumb a,
        .ioulia-mini-cart-thumb img {
            display: block;
            width: 100%;
            height: 100%;
        }

        .ioulia-mini-cart-thumb img {
            object-fit: cover;
        }

        .ioulia-mini-cart-product {
            display: flex;
            min-width: 0;
            flex-direction: column;
            justify-content: space-between;
        }

        .ioulia-mini-cart-product-top,
        .ioulia-mini-cart-product-bottom,
        .ioulia-mini-cart-subtotal {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .ioulia-mini-cart-name,
        .ioulia-mini-cart-line-price,
        .ioulia-mini-cart-subtotal,
        .ioulia-mini-cart-note,
        .ioulia-mini-cart-meta {
            font-family: var(--ioulia-font) !important;
            font-weight: 400 !important;
        }

        .ioulia-mini-cart-name {
            color: var(--ioulia-dark) !important;
            font-size: 14px !important;
            line-height: 1.35 !important;
            text-decoration: none !important;
        }

        .ioulia-mini-cart-name:hover {
            color: var(--ioulia-dark) !important;
            opacity: .58;
        }

        .ioulia-mini-cart-meta,
        .ioulia-mini-cart-meta p {
            margin: 5px 0 0 !important;
            color: rgba(43, 43, 43, .55) !important;
            font-size: 10px !important;
            line-height: 1.4 !important;
        }

        .ioulia-mini-cart-remove {
            display: grid !important;
            width: 32px !important;
            height: 32px !important;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid rgba(43, 43, 43, .12) !important;
            border-radius: 999px !important;
            color: rgba(43, 43, 43, .48) !important;
            transition: color .2s ease, border-color .2s ease, background-color .2s ease, transform .24s cubic-bezier(.16, 1, .3, 1) !important;
        }

        .ioulia-mini-cart-remove:hover {
            color: var(--ioulia-dark) !important;
            border-color: var(--ioulia-dark) !important;
            background: rgba(43, 43, 43, .05) !important;
            transform: scale(1.04);
        }

        .ioulia-mini-cart-remove svg { width: 13px; height: 13px; }

        .ioulia-mini-cart-product-bottom {
            align-items: center;
        }

        .ioulia-mini-cart-quantity {
            display: inline-grid;
            grid-template-columns: 30px 28px 30px;
            height: 34px;
            padding: 1px;
            align-items: center;
            border: 1px solid rgba(43, 43, 43, .16);
            border-radius: 999px;
            background: rgba(255, 255, 255, .5);
        }

        .ioulia-mini-cart-quantity button,
        .ioulia-mini-cart-quantity span {
            display: grid !important;
            height: 30px !important;
            place-items: center;
            color: var(--ioulia-dark) !important;
            font-family: var(--ioulia-font) !important;
            font-size: 11px !important;
            line-height: 1 !important;
        }

        .ioulia-mini-cart-quantity button:disabled {
            opacity: .25;
            cursor: default;
        }

        .ioulia-mini-cart-line-price {
            color: var(--ioulia-dark);
            font-size: var(--ioulia-small);
            line-height: 1.2;
            white-space: nowrap;
        }

        .ioulia-mini-cart-footer {
            margin-top: auto;
            padding: 18px 24px max(20px, env(safe-area-inset-bottom));
            flex: 0 0 auto;
            border-top: 1px solid rgba(43, 43, 43, .12);
            background: var(--ioulia-cream);
            box-shadow: 0 -14px 36px rgba(43, 43, 43, .055);
        }

        .ioulia-mini-cart-subtotal {
            align-items: baseline;
            color: var(--ioulia-dark);
            font-size: 14px;
        }

        .ioulia-mini-cart-subtotal strong {
            font-size: 18px;
            font-weight: 400;
        }

        .ioulia-mini-cart-note {
            margin: 6px 0 16px !important;
            color: rgba(43, 43, 43, .52) !important;
            font-size: 10px !important;
            line-height: 1.4 !important;
        }

        .ioulia-mini-cart-checkout,
        .ioulia-mini-cart-empty a {
            display: flex !important;
            width: 100%;
            min-height: 52px;
            padding: 14px 20px !important;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--ioulia-dark) !important;
            border-radius: 14px !important;
            background: var(--ioulia-dark) !important;
            box-shadow: none !important;
            color: var(--ioulia-cream) !important;
            font-family: var(--ioulia-font) !important;
            font-size: 12px !important;
            font-weight: 400 !important;
            line-height: 1 !important;
            text-align: center;
            text-decoration: none !important;
            text-transform: lowercase !important;
            box-sizing: border-box;
            transition: background-color .25s ease, color .25s ease, transform .25s cubic-bezier(.16, 1, .3, 1), box-shadow .25s ease;
        }

        .ioulia-mini-cart-checkout:hover,
        .ioulia-mini-cart-checkout:focus,
        .ioulia-mini-cart-checkout:active,
        .ioulia-mini-cart-empty a:hover,
        .ioulia-mini-cart-empty a:focus,
        .ioulia-mini-cart-empty a:active {
            border-color: var(--ioulia-dark) !important;
            background: var(--ioulia-dark) !important;
            color: var(--ioulia-cream) !important;
            box-shadow: 0 9px 22px rgba(43, 43, 43, .16) !important;
            transform: translateY(-2px);
        }

        .ioulia-mini-cart-empty {
            display: flex;
            padding: clamp(2rem, 8vh, 5rem) 24px 24px;
            flex: 1;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .ioulia-mini-cart-empty p {
            margin: 0 0 28px !important;
            color: var(--ioulia-dark) !important;
            font-family: var(--ioulia-font) !important;
            font-size: clamp(24px, 3vw, 36px) !important;
            font-weight: 400 !important;
            letter-spacing: -.035em;
            line-height: 1.1 !important;
        }

        .ioulia-mini-cart-empty a {
            width: min(100%, 260px);
            min-width: 0;
        }

        @media (min-width: 700px) {
            .ioulia-mini-cart-subtotal {
                font-size: 16px;
            }

            .ioulia-mini-cart-subtotal strong {
                font-size: 21px;
            }

            .ioulia-mini-cart-note {
                margin-top: 8px !important;
                font-size: 14px !important;
                line-height: 1.5 !important;
            }

            .ioulia-mini-cart-checkout {
                font-size: 14px !important;
            }
        }

        /* Hold the page still while an overlay is open.
           The root is the scroller, so this is where the lock belongs: an
           overflow on body alone leaves the page scrolling behind the menu. */
        html.ioulia-cart-root-locked {
            overflow: hidden !important;
        }

        /* --- Burger Menu --- */
        .ioulia-burger {
            width: 50px;
            height: 16px; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            cursor: pointer;
            padding: 0 !important;
            position: relative;
        }

        .ioulia-burger:hover, .ioulia-burger:focus, .ioulia-burger:active {
            background: transparent !important;
            background-color: transparent !important;
        }

        .ioulia-burger .line {
            width: 100%;
            height: 2px;
            background-color: var(--ioulia-dark);
            transition: transform 0.6s var(--ioulia-snappy-ease), background-color 0.5s ease;
            transform-origin: center;
        }

        /* --- STATE: MENU OPEN (Dark Overlay) --- */
        #ioulia-header.menu-open .ioulia-logo-circle {
            border-color: var(--ioulia-cream);
        }
        #ioulia-header.menu-open .ioulia-cart-icon svg {
            fill: var(--ioulia-cream);
        }
        #ioulia-header.menu-open .ioulia-cart-count {
            background: var(--ioulia-cream);
            color: var(--ioulia-bg-dark);
        }
        #ioulia-header.menu-open .ioulia-burger .line {
            background-color: var(--ioulia-cream);
        }

        /* X Animation */
        #ioulia-header.menu-open .ioulia-burger .line1 {
            transform: translateY(7px) rotate(45deg);
        }
        #ioulia-header.menu-open .ioulia-burger .line2 {
            transform: translateY(-7px) rotate(-45deg);
        }

        /* --- STATE: LIGHT MODE SECTIONS --- */
        #ioulia-header.ioulia-nav-light:not(.menu-open) .ioulia-logo-circle {
            border-color: var(--ioulia-cream);
        }
        #ioulia-header.ioulia-nav-light:not(.menu-open) .ioulia-cart-icon svg {
            fill: var(--ioulia-cream);
        }
        #ioulia-header.ioulia-nav-light:not(.menu-open) .ioulia-cart-count {
            background: var(--ioulia-cream);
            color: var(--ioulia-dark);
        }
        #ioulia-header.ioulia-nav-light:not(.menu-open) .ioulia-burger .line {
            background-color: var(--ioulia-cream);
        }
        #ioulia-header.menu-open .ioulia-lang-switcher,
        #ioulia-header.ioulia-nav-light:not(.menu-open) .ioulia-lang-switcher {
            color: var(--ioulia-cream);
        }

        /* --- Fullscreen/Overlay Canvas --- */
        #ioulia-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70vh;
            background-color: var(--ioulia-bg-dark);
            z-index: 99998;
            transform: translateY(-100%);
            transition: transform 0.85s var(--ioulia-snappy-ease);
            box-sizing: border-box;
            overflow: hidden;
            padding: 16vh var(--ioulia-page-x) 5vh var(--ioulia-page-x); 
        }

        #ioulia-menu-overlay.active {
            transform: translateY(0);
        }

        /* --- Canvas Layout --- */
        .ioulia-canvas-container {
            width: 100%;
            max-width: var(--ioulia-shell);
            margin: 0 auto;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .ioulia-canvas-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 5vh;
        }

        /* Δεξιά Στήλη */
        .ioulia-col-right .ioulia-menu-list {
            align-items: flex-end;
        }

        /* --- Parallax Entrance Content --- */
        .ioulia-menu-list {
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .ioulia-menu-item {
            margin: 0 0 1rem 0;
            padding: 0;
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.6s var(--ioulia-smooth-ease), transform 0.8s var(--ioulia-snappy-ease);
        }

        #ioulia-menu-overlay.active .ioulia-menu-item {
            opacity: 1;
            transform: translateY(0);
        }

        #ioulia-menu-overlay.active .delay-1 { transition-delay: 0.3s; }
        #ioulia-menu-overlay.active .delay-2 { transition-delay: 0.38s; }
        #ioulia-menu-overlay.active .delay-3 { transition-delay: 0.46s; }
        #ioulia-menu-overlay.active .delay-4 { transition-delay: 0.54s; }
        #ioulia-menu-overlay.active .delay-5 { transition-delay: 0.62s; }

        /* --- Τυπογραφία & Links --- */
        .ioulia-menu-item-link {
            font-family: var(--ioulia-font);
            font-weight: 400;
            font-size: clamp(2.8rem, 4.8vw, 5.2rem);
            color: var(--ioulia-cream);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .ioulia-menu-text {
            color: var(--ioulia-cream);
            transition: transform 0.6s var(--ioulia-snappy-ease), color 0.4s ease, font-weight 0.15s ease-out !important;
        }

        .ioulia-vessel-svg {
            width: 0;
            opacity: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            transition: width 0.5s var(--ioulia-snappy-ease), opacity 0.4s ease, transform 0.5s var(--ioulia-snappy-ease);
        }

        .ioulia-vessel-svg svg {
            height: 50px;
            width: auto;
        }

        .ioulia-col-left{
            padding-bottom:2em;
        }

        /* --- Αριστερή Στήλη: Hover --- */
        .ioulia-col-left .ioulia-vessel-svg {
            transform: translateX(-25px) scale(0.8);
        }
        
        .ioulia-col-left .ioulia-menu-item-link:hover .ioulia-menu-text {
            transform: translateX(20px);
            font-weight: 400;
            color: var(--ioulia-peach);
        }
        .ioulia-col-left .ioulia-menu-item-link:hover .ioulia-vessel-svg {
            width: 35px;
            opacity: 1;
            transform: translateX(0) scale(1);
        }

        /* --- Δεξιά Στήλη: Reverse Hover --- */
        .ioulia-col-right .ioulia-menu-item-link {
            flex-direction: row-reverse;
        }
        .ioulia-col-right .ioulia-vessel-svg {
            transform: translateX(25px) scale(0.8);
        }
        .ioulia-col-right .ioulia-menu-item-link:hover .ioulia-menu-text {
            transform: translateX(-20px);
            font-weight: 400;
            color: var(--ioulia-peach);
        }
        .ioulia-col-right .ioulia-menu-item-link:hover .ioulia-vessel-svg {
            width: 35px;
            opacity: 1;
            transform: translateX(0) scale(1);
        }

        /* --- Canvas Footer --- */
        .ioulia-canvas-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(254, 250, 228, 0.1);
            padding-top: 3.5vh;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s var(--ioulia-snappy-ease);
            transition-delay: 0.7s;
        }

        #ioulia-menu-overlay.active .ioulia-canvas-footer {
            opacity: 1;
            transform: translateY(0);
        }

        .ioulia-privacy-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .ioulia-privacy-links a {
            font-family: var(--ioulia-font);
            font-size: var(--ioulia-micro);
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(254, 250, 228, 0.45);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .ioulia-privacy-links a:hover {
            color: var(--ioulia-cream);
        }
        .ioulia-divider {
            color: rgba(254, 250, 228, 0.2);
            font-size: var(--ioulia-micro);
        }

        .ioulia-social-links {
            display: flex;
            gap: 2.5rem;
        }
        .ioulia-social-links a {
            color: rgba(254, 250, 228, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .ioulia-social-links a:hover {
            color: var(--ioulia-cream);
            transform: translateY(-3px);
        }
        .ioulia-social-links svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

        /* --- Mobile --- */
        @media (max-width: 991px) {
            #ioulia-header { padding-top: 2.5em; }

            /* svh, not vh: the panel then holds still while the browser chrome
               slides away, instead of growing mid-animation. The shorter curve
               lets the links arrive with the panel rather than a beat after it. */
            #ioulia-menu-overlay {
                height: 100svh;
                padding-top: var(--ioulia-header-h, 139px);
                padding-bottom: 4vh;
                transition: transform 0.62s cubic-bezier(0.22, 1, 0.36, 1);
            }

            /* The list sits in the middle of what is left below the header,
               with the footer still resting at the bottom. */
            .ioulia-canvas-main {
                flex: 1;
                flex-direction: column;
                justify-content: center;
                /* Without stretch the columns shrink to their text and the
                   right edge of the list stops short of the gutter. */
                align-items: stretch;
                gap: 0;
                margin-top: 0;
            }
            .ioulia-col-left{ padding-bottom: 0; }

            /* Both columns read as one right-aligned list on a phone. */
            .ioulia-menu-list,
            .ioulia-col-right .ioulia-menu-list { align-items: flex-end; }
            .ioulia-menu-item-link { flex-direction: row-reverse; font-size: 2.4rem; }
            .ioulia-vessel-svg,
            .ioulia-col-right .ioulia-vessel-svg { transform: translateX(25px) scale(0.8); }
            .ioulia-menu-item-link:hover .ioulia-menu-text,
            .ioulia-col-right .ioulia-menu-item-link:hover .ioulia-menu-text { transform: translateX(-20px); }

            /* The hover vessel is 50px tall even while it is hidden, which set
               the row height on its own; at 34px the rows follow the text. */
            .ioulia-vessel-svg svg { height: 34px; }

            /* Closing is the plain state, so the links leave quickly; opening
               gets the longer spring and a tighter stagger. */
            .ioulia-menu-item {
                margin-bottom: 0.5rem;
                transform: translateY(24px);
                transition: opacity 0.24s ease, transform 0.3s cubic-bezier(0.4, 0, 1, 1);
            }
            #ioulia-menu-overlay.active .ioulia-menu-item {
                transition: opacity 0.5s var(--ioulia-smooth-ease), transform 0.62s var(--ioulia-snappy-ease);
            }
            #ioulia-menu-overlay.active .delay-1 { transition-delay: 0.10s; }
            #ioulia-menu-overlay.active .delay-2 { transition-delay: 0.14s; }
            #ioulia-menu-overlay.active .delay-3 { transition-delay: 0.18s; }
            #ioulia-menu-overlay.active .delay-4 { transition-delay: 0.22s; }
            #ioulia-menu-overlay.active .delay-5 { transition-delay: 0.26s; }

            .ioulia-canvas-footer {
                flex-direction: column;
                align-items: flex-end;
                gap: 1.5rem;
                padding-top: 2.5vh;
                transition: opacity 0.35s ease, transform 0.4s var(--ioulia-snappy-ease);
                transition-delay: 0s;
            }
            #ioulia-menu-overlay.active .ioulia-canvas-footer { transition-delay: 0.3s; }
            .ioulia-nav-right { gap: 1.5em; }
            .ioulia-lang-switcher { font-size: var(--ioulia-small); gap: .35em; }
            .ioulia-mini-cart-panel {
                width: min(460px, calc(100% - 24px));
            }
            .ioulia-mini-cart-item {
                grid-template-columns: 92px minmax(0, 1fr);
                gap: 13px;
            }
        }

        @media (max-width: 699px) {
            html.ioulia-cart-root-locked,
            html.ioulia-cart-root-locked body {
                overflow: hidden !important;
                overscroll-behavior: none;
            }
            body.ioulia-cart-locked {
                position: fixed !important;
                right: 0;
                left: 0;
                width: 100%;
            }
            .ioulia-mini-cart-panel {
                top: auto;
                right: 0;
                bottom: 0;
                left: 0;
                width: 100%;
                height: min(92vh, 820px);
                height: min(92dvh, 820px);
                border-width: 1px 1px 0;
                border-radius: 24px 24px 0 0;
                box-shadow: 0 -22px 70px rgba(20, 20, 18, .18);
                transform: translate3d(0, 102%, 0);
            }
            .ioulia-mini-cart-panel.is-open { transform: translate3d(0, 0, 0); }
            .ioulia-mini-cart-grab {
                display: block;
                width: 40px;
                height: 4px;
                margin: 10px auto 0;
                flex: 0 0 auto;
                border-radius: 999px;
                background: rgba(43, 43, 43, .16);
            }
            .ioulia-mini-cart-grab,
            .ioulia-mini-cart-header { touch-action: none; }
            .ioulia-mini-cart-header { padding: 10px 20px 16px; }
            .ioulia-mini-cart-items {
                padding: 10px 12px 14px;
                overscroll-behavior-y: contain;
                -webkit-overflow-scrolling: touch;
                touch-action: pan-y;
            }
            .ioulia-mini-cart-footer { padding: 16px 20px max(16px, env(safe-area-inset-bottom)); }
            .ioulia-mini-cart-item { grid-template-columns: 84px minmax(0, 1fr); padding: 10px; }
            .ioulia-mini-cart-title { font-size: 32px !important; }
        }

        @media (prefers-reduced-motion: reduce) {
            .ioulia-mini-cart-panel,
            .ioulia-mini-cart-backdrop,
            .ioulia-mini-cart-header,
            .ioulia-mini-cart-item,
            .ioulia-mini-cart-footer,
            .ioulia-mini-cart-empty {
                transition: none !important;
            }
        }
    </style>

    <header id="ioulia-header">
        <div class="ioulia-nav-left">
            <a href="/" aria-label="Αρχική σελίδα"><div class="ioulia-logo-circle"></div></a>
        </div>
        <div class="ioulia-nav-right">
            <?php if ( function_exists( 'ioulia_language_switcher' ) ) { echo ioulia_language_switcher(); } ?>
            <button type="button" class="ioulia-cart-icon" data-ioulia-cart-open aria-label="Άνοιγμα καλαθιού" aria-controls="ioulia-mini-cart" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21.66 18.63">
                    <path d="M21.36,7.79h-5.75v-2.85c0-1.53-.74-2.96-1.95-3.9-1.81-1.42-4.4-1.38-6.18.09-1.15.94-1.84,2.34-1.84,3.84v2.82H.27C.06,7.79,0,7.96,0,8.13c.07,2.85,1.27,5.52,3.32,7.49,4.21,4.04,10.92,4.02,15.1-.09,1.36-1.33,2.35-2.99,2.85-4.82.23-.86.36-1.71.39-2.6,0-.18-.11-.32-.3-.32ZM14.58,7.79h-7.91v-2.87c0-1.12.53-2.15,1.35-2.88,1.25-1.11,3.05-1.31,4.5-.5,1.24.69,2.02,1.96,2.06,3.39v2.86Z"/>
                </svg>
                <?php echo ioulia_mini_cart_count_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </button>
            <button id="ioulia-burger" class="ioulia-burger" aria-label="Άνοιγμα μενού" aria-expanded="false" aria-controls="ioulia-menu-overlay">
                <span class="line line1"></span>
                <span class="line line2"></span>
            </button>
        </div>
    </header>

    <div class="ioulia-mini-cart-backdrop" data-ioulia-cart-close aria-hidden="true"></div>
    <aside id="ioulia-mini-cart" class="ioulia-mini-cart-panel" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="ioulia-mini-cart-title" data-lenis-prevent inert>
        <span class="ioulia-mini-cart-grab" aria-hidden="true"></span>
        <div class="ioulia-mini-cart-header">
            <h2 id="ioulia-mini-cart-title" class="ioulia-mini-cart-title">το καλάθι σου</h2>
            <button type="button" class="ioulia-mini-cart-close" data-ioulia-cart-close aria-label="Κλείσιμο καλαθιού">
                <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M3 3 L13 13 M13 3 L3 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </button>
        </div>
        <?php echo ioulia_mini_cart_shell(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </aside>

    <div id="ioulia-menu-overlay">
        <div class="ioulia-canvas-container">
            
            <div class="ioulia-canvas-main">
                
                <div class="ioulia-col-left">
                    <ul class="ioulia-menu-list">
                    <li class="ioulia-menu-item delay-1">
                        <a href="/" class="ioulia-menu-item-link">
                            <span class="ioulia-vessel-svg">
                                <svg viewBox="0 0 19 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.37028 30.6129C1.60799 29.5351 1.27362 28.4722 0.866278 27.34C-0.763025 22.651 -0.282733 17.4728 3.9372 14.5744C5.80042 13.2936 6.59413 11.2555 6.35836 9.10527L5.88422 4.98454C5.63589 2.77274 4.86148 0.51342 5.07054 0.160259C5.46348 -0.509295 15.4108 1.0872 18.6073 1.85739C16.907 4.8991 15.5164 7.76056 14.6165 10.945C14.1678 12.5274 14.5071 14.0309 15.4656 15.3338C16.2631 16.6161 17.3173 17.8114 17.7044 19.261C18.6394 22.8209 17.4873 27.3329 15.0042 30.0942L11.6574 33.8446L3.20768 32.7003C3.08213 31.9242 2.83821 31.2729 2.38789 30.6351L2.37028 30.6129Z" fill="#FECAA7"/>
                                </svg>
                            </span>
                            <span class="ioulia-menu-text">Αρχική</span>
                        </a>
                    </li>
                    <li class="ioulia-menu-item delay-2">
                        <a href="/shop" class="ioulia-menu-item-link">
                            <span class="ioulia-vessel-svg">
                                <svg viewBox="0 0 19 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.37028 30.6129C1.60799 29.5351 1.27362 28.4722 0.866278 27.34C-0.763025 22.651 -0.282733 17.4728 3.9372 14.5744C5.80042 13.2936 6.59413 11.2555 6.35836 9.10527L5.88422 4.98454C5.63589 2.77274 4.86148 0.51342 5.07054 0.160259C5.46348 -0.509295 15.4108 1.0872 18.6073 1.85739C16.907 4.8991 15.5164 7.76056 14.6165 10.945C14.1678 12.5274 14.5071 14.0309 15.4656 15.3338C16.2631 16.6161 17.3173 17.8114 17.7044 19.261C18.6394 22.8209 17.4873 27.3329 15.0042 30.0942L11.6574 33.8446L3.20768 32.7003C3.08213 31.9242 2.83821 31.2729 2.38789 30.6351L2.37028 30.6129Z" fill="#FECAA7"/>
                                </svg>
                            </span>
                            <span class="ioulia-menu-text">Κατάστημα</span>
                        </a>
                    </li>
                    <li class="ioulia-menu-item delay-3">
                        <a href="/about" class="ioulia-menu-item-link">
                            <span class="ioulia-vessel-svg">
                                <svg viewBox="0 0 19 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.37028 30.6129C1.60799 29.5351 1.27362 28.4722 0.866278 27.34C-0.763025 22.651 -0.282733 17.4728 3.9372 14.5744C5.80042 13.2936 6.59413 11.2555 6.35836 9.10527L5.88422 4.98454C5.63589 2.77274 4.86148 0.51342 5.07054 0.160259C5.46348 -0.509295 15.4108 1.0872 18.6073 1.85739C16.907 4.8991 15.5164 7.76056 14.6165 10.945C14.1678 12.5274 14.5071 14.0309 15.4656 15.3338C16.2631 16.6161 17.3173 17.8114 17.7044 19.261C18.6394 22.8209 17.4873 27.3329 15.0042 30.0942L11.6574 33.8446L3.20768 32.7003C3.08213 31.9242 2.83821 31.2729 2.38789 30.6351L2.37028 30.6129Z" fill="#FECAA7"/>
                                </svg>
                            </span>
                            <span class="ioulia-menu-text">Σχετικά</span>
                        </a>
                    </li>
                    </ul>
                </div>

                <div class="ioulia-col-right">
                    <ul class="ioulia-menu-list">
                    <li class="ioulia-menu-item delay-4">
                        <a href="/workshops" class="ioulia-menu-item-link">
                            <span class="ioulia-vessel-svg">
                                <svg viewBox="0 0 19 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.37028 30.6129C1.60799 29.5351 1.27362 28.4722 0.866278 27.34C-0.763025 22.651 -0.282733 17.4728 3.9372 14.5744C5.80042 13.2936 6.59413 11.2555 6.35836 9.10527L5.88422 4.98454C5.63589 2.77274 4.86148 0.51342 5.07054 0.160259C5.46348 -0.509295 15.4108 1.0872 18.6073 1.85739C16.907 4.8991 15.5164 7.76056 14.6165 10.945C14.1678 12.5274 14.5071 14.0309 15.4656 15.3338C16.2631 16.6161 17.3173 17.8114 17.7044 19.261C18.6394 22.8209 17.4873 27.3329 15.0042 30.0942L11.6574 33.8446L3.20768 32.7003C3.08213 31.9242 2.83821 31.2729 2.38789 30.6351L2.37028 30.6129Z" fill="#FECAA7"/>
                                </svg>
                            </span>
                            <span class="ioulia-menu-text">Εργαστήρια</span>
                        </a>
                    </li>
                    <li class="ioulia-menu-item delay-5">
                        <a href="/contact" class="ioulia-menu-item-link">
                            <span class="ioulia-vessel-svg">
                                <svg viewBox="0 0 19 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.37028 30.6129C1.60799 29.5351 1.27362 28.4722 0.866278 27.34C-0.763025 22.651 -0.282733 17.4728 3.9372 14.5744C5.80042 13.2936 6.59413 11.2555 6.35836 9.10527L5.88422 4.98454C5.63589 2.77274 4.86148 0.51342 5.07054 0.160259C5.46348 -0.509295 15.4108 1.0872 18.6073 1.85739C16.907 4.8991 15.5164 7.76056 14.6165 10.945C14.1678 12.5274 14.5071 14.0309 15.4656 15.3338C16.2631 16.6161 17.3173 17.8114 17.7044 19.261C18.6394 22.8209 17.4873 27.3329 15.0042 30.0942L11.6574 33.8446L3.20768 32.7003C3.08213 31.9242 2.83821 31.2729 2.38789 30.6351L2.37028 30.6129Z" fill="#FECAA7"/>
                                </svg>
                            </span>
                            <span class="ioulia-menu-text">Επικοινωνία</span>
                        </a>
                    </li>
                    </ul>
                </div>
            </div>

            <div class="ioulia-canvas-footer">
                <div class="ioulia-privacy-links">
                    <a href="/privacy-policy">Πολιτική Απορρήτου</a>
                    <span class="ioulia-divider">|</span>
                    <a href="/data-protection">Προστασία Δεδομένων</a>
                </div>
                <div class="ioulia-social-links">
                    <a href="https://instagram.com" target="_blank" aria-label="Instagram">
                        <svg width="24" height="24" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.55626 0.85318C6.03621 0.853973 6.27942 0.856515 6.48959 0.862771L6.57237 0.865475C6.66798 0.868874 6.76232 0.873139 6.87606 0.87847C7.32986 0.899439 7.63944 0.971227 7.91131 1.07678C8.1924 1.18517 8.42982 1.33159 8.66685 1.56864C8.90354 1.80569 9.04999 2.0438 9.15874 2.3242C9.26391 2.59573 9.33572 2.90563 9.35705 3.35946C9.36212 3.47319 9.36622 3.56752 9.36959 3.66316L9.37227 3.74593C9.3785 3.95607 9.38136 4.19932 9.38225 4.67929L9.38259 4.99727C9.38264 5.03612 9.38264 5.07621 9.38264 5.11757L9.38259 5.23788L9.38234 5.5559C9.38153 6.03585 9.37901 6.27911 9.37274 6.48923L9.37001 6.57201C9.36664 6.66767 9.36238 6.762 9.35705 6.8757C9.33607 7.32955 9.26391 7.63908 9.15874 7.91096C9.05033 8.19209 8.90354 8.4295 8.66685 8.66653C8.42982 8.90323 8.19133 9.04963 7.91131 9.15838C7.63944 9.26359 7.32986 9.33537 6.87606 9.35669C6.76232 9.36177 6.66798 9.3659 6.57237 9.36923L6.48959 9.37192C6.27942 9.37819 6.03621 9.381 5.55626 9.38194L5.23824 9.38228C5.19939 9.38228 5.1593 9.38228 5.11793 9.38228H4.99762L4.6796 9.38198C4.19965 9.38121 3.9564 9.37866 3.74626 9.37239L3.66349 9.3697C3.56786 9.36629 3.47352 9.36202 3.3598 9.35669C2.90596 9.33575 2.59677 9.26359 2.32454 9.15838C2.04378 9.05002 1.80602 8.90323 1.56898 8.66653C1.33193 8.4295 1.18586 8.19102 1.07711 7.91096C0.971563 7.63908 0.900129 7.32955 0.878805 6.8757C0.873739 6.762 0.869606 6.66767 0.866259 6.57201L0.863576 6.48923C0.857337 6.27911 0.854492 6.03585 0.853571 5.5559L0.853516 4.67929C0.854309 4.19932 0.856846 3.95607 0.863103 3.74593L0.865811 3.66316C0.86921 3.56752 0.873475 3.47319 0.878805 3.35946C0.899771 2.90527 0.971563 2.59608 1.07711 2.3242C1.18551 2.04345 1.33193 1.80569 1.56898 1.56864C1.80602 1.33159 2.04414 1.18553 2.32454 1.07678C2.59642 0.971227 2.90561 0.899793 3.3598 0.87847C3.47352 0.873407 3.56786 0.869275 3.66349 0.865927L3.74626 0.863245C3.9564 0.857001 4.19965 0.854157 4.6796 0.853235L5.55626 0.85318ZM5.11793 2.98523C3.93963 2.98523 2.98557 3.94033 2.98557 5.11757C2.98557 6.29587 3.94066 7.24993 5.11793 7.24993C6.29623 7.24993 7.25028 6.29485 7.25028 5.11757C7.25028 3.93929 6.29516 2.98523 5.11793 2.98523ZM5.11793 3.83818C5.82455 3.83818 6.39734 4.41078 6.39734 5.11757C6.39734 5.82419 5.82472 6.39699 5.11793 6.39699C4.41131 6.39699 3.83851 5.82441 3.83851 5.11757C3.83851 4.41096 4.4111 3.83818 5.11793 3.83818ZM7.3569 2.34553C7.06294 2.34553 6.82381 2.58431 6.82381 2.87825C6.82381 3.1722 7.06259 3.41135 7.3569 3.41135C7.65083 3.41135 7.88999 3.17257 7.88999 2.87825C7.88999 2.58431 7.65044 2.34516 7.3569 2.34553Z" fill="currentColor"/>
                        </svg>
                    </a>
                    <a href="https://facebook.com" target="_blank" aria-label="Facebook">
                        <svg width="24" height="24" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.11823 0.85318C2.76289 0.85318 0.853516 2.76255 0.853516 5.11789C0.853516 7.24653 2.41306 9.01084 4.45187 9.33078V6.35064H3.36902V5.11789H4.45187V4.17832C4.45187 3.10948 5.08855 2.51908 6.06269 2.51908C6.52929 2.51908 7.01735 2.60237 7.01735 2.60237V3.65189H6.47957C5.94981 3.65189 5.78459 3.98063 5.78459 4.31791V5.11789H6.96736L6.77831 6.35064H5.78459V9.33078C7.82338 9.01084 9.38294 7.24653 9.38294 5.11789C9.38294 2.76255 7.47354 0.85318 5.11823 0.85318Z" fill="currentColor"/>
                        </svg>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const header = document.getElementById("ioulia-header");
            const burger = document.getElementById("ioulia-burger");
            const overlay = document.getElementById("ioulia-menu-overlay");
            const body = document.body;
            const cartPanel = document.getElementById("ioulia-mini-cart");
            const cartOpen = document.querySelector("[data-ioulia-cart-open]");
            const cartBackdrop = document.querySelector(".ioulia-mini-cart-backdrop");
            const cartCloseButtons = document.querySelectorAll("[data-ioulia-cart-close]");
            const cartStoreApiUrl = <?php echo wp_json_encode( untrailingslashit( rest_url( 'wc/store/v1/cart' ) ) ); ?>;
            const cartFragmentsUrl = <?php echo wp_json_encode( add_query_arg( 'wc-ajax', 'get_refreshed_fragments', home_url( '/' ) ) ); ?>;
            let cartPreviousFocus = null;
            let cartCloseTimer = null;
            let lockedScrollY = 0;

            /* Enable transitions only after the hidden initial state has been painted. */
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => {
                    cartPanel.classList.add("is-ready");
                });
            });

            let lockedBodyLift = false;

            const syncBodyLock = () => {
                const shouldLock = overlay.classList.contains("active") || cartPanel.classList.contains("is-open") || cartPanel.classList.contains("is-closing");

                /* Below 700px the body is taken out of flow, so it has to be
                   pulled up by the scroll position to stay where it was. Above
                   that it is not, and setting the offset anyway shifted the whole
                   page upward: body carries a position from the theme, so a
                   negative top moves it, and with overflow-x clipped the content
                   that went above the viewport was simply gone. */
                const liftsBody = () => window.matchMedia("(max-width: 699px)").matches;

                if (shouldLock && !body.classList.contains("ioulia-cart-locked")) {
                    lockedScrollY = window.scrollY;
                    lockedBodyLift = liftsBody();

                    if (lockedBodyLift) {
                        body.style.top = `-${lockedScrollY}px`;
                    } else {
                        // Hiding the scrollbar would otherwise widen the page.
                        const bar = window.innerWidth - document.documentElement.clientWidth;
                        if (bar > 0) document.documentElement.style.paddingRight = `${bar}px`;
                    }

                    body.classList.add("ioulia-cart-locked");
                    document.documentElement.classList.add("ioulia-cart-root-locked");
                } else if (!shouldLock && body.classList.contains("ioulia-cart-locked")) {
                    body.classList.remove("ioulia-cart-locked");
                    document.documentElement.classList.remove("ioulia-cart-root-locked");
                    document.documentElement.style.paddingRight = "";
                    body.style.top = "";

                    if (lockedBodyLift) {
                        window.scrollTo(0, lockedScrollY);
                    }
                }
            };

            const closeMenu = () => {
                header.classList.remove("menu-open");
                overlay.classList.remove("active");
                burger.setAttribute("aria-expanded", "false");
                syncBodyLock();
            };

            const openMiniCart = () => {
                closeMenu();
                if (cartCloseTimer) {
                    window.clearTimeout(cartCloseTimer);
                    cartCloseTimer = null;
                }
                cartPreviousFocus = document.activeElement;
                cartPanel.style.transform = "";
                cartPanel.style.transition = "";
                cartBackdrop.style.opacity = "";
                cartBackdrop.style.transition = "";
                cartPanel.classList.remove("is-closing");
                cartPanel.classList.add("is-open");
                cartPanel.removeAttribute("inert");
                cartBackdrop.classList.add("is-open");
                cartPanel.setAttribute("aria-hidden", "false");
                cartBackdrop.setAttribute("aria-hidden", "false");
                cartOpen.setAttribute("aria-expanded", "true");
                syncBodyLock();

                window.setTimeout(() => {
                    const closeButton = cartPanel.querySelector(".ioulia-mini-cart-close");
                    if (closeButton) closeButton.focus({ preventScroll: true });
                }, 100);
            };

            const closeMiniCart = (restoreFocus = true) => {
                const wasOpen = cartPanel.classList.contains("is-open");

                if (cartCloseTimer) {
                    window.clearTimeout(cartCloseTimer);
                    cartCloseTimer = null;
                }

                cartPanel.classList.remove("is-open");
                cartPanel.classList.toggle("is-closing", wasOpen);
                cartBackdrop.classList.remove("is-open");
                cartBackdrop.setAttribute("aria-hidden", "true");
                cartOpen.setAttribute("aria-expanded", "false");
                syncBodyLock();

                const finishClose = () => {
                    cartPanel.style.transform = "";
                    cartPanel.style.transition = "";
                    cartBackdrop.style.opacity = "";
                    cartBackdrop.style.transition = "";
                    cartPanel.classList.remove("is-closing");
                    cartPanel.setAttribute("aria-hidden", "true");
                    cartPanel.setAttribute("inert", "");
                    syncBodyLock();
                    cartCloseTimer = null;

                    if (restoreFocus && cartPreviousFocus && typeof cartPreviousFocus.focus === "function") {
                        cartPreviousFocus.focus({ preventScroll: true });
                    }
                };

                if (!wasOpen || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                    finishClose();
                    return;
                }

                cartCloseTimer = window.setTimeout(finishClose, 540);
            };

            /* On phones the cart is a physical bottom sheet: the handle and
               header follow the finger, then dismiss or spring back. */
            (() => {
                let dragging = false;
                let startY = 0;
                let lastY = 0;
                let lastAt = 0;
                let offset = 0;
                let velocity = 0;

                const isBottomSheet = () => window.matchMedia("(max-width: 699px)").matches;

                const start = (event) => {
                    if (!isBottomSheet() || event.button || !cartPanel.classList.contains("is-open")) return;
                    if (!event.target.closest(".ioulia-mini-cart-grab, .ioulia-mini-cart-header") || event.target.closest("button, a")) return;

                    dragging = true;
                    startY = event.clientY;
                    lastY = event.clientY;
                    lastAt = event.timeStamp;
                    offset = 0;
                    velocity = 0;
                    cartPanel.style.transition = "none";
                    cartBackdrop.style.transition = "none";
                    if (cartPanel.setPointerCapture) cartPanel.setPointerCapture(event.pointerId);
                };

                const move = (event) => {
                    if (!dragging) return;
                    const elapsed = Math.max(1, event.timeStamp - lastAt);
                    velocity = (event.clientY - lastY) / elapsed;
                    lastY = event.clientY;
                    lastAt = event.timeStamp;
                    offset = Math.max(0, event.clientY - startY);
                    cartPanel.style.transform = `translate3d(0, ${offset}px, 0)`;
                    cartBackdrop.style.opacity = String(Math.max(0, 1 - (offset / cartPanel.offsetHeight) * 1.6));
                };

                const end = () => {
                    if (!dragging) return;
                    dragging = false;
                    const dismiss = offset > cartPanel.offsetHeight * .2 || (velocity > .65 && offset > 36);

                    cartPanel.style.transition = "transform .38s cubic-bezier(.16, 1, .3, 1)";
                    cartBackdrop.style.transition = "opacity .3s ease";

                    if (dismiss) {
                        cartPanel.style.transform = "translate3d(0, 102%, 0)";
                        cartBackdrop.style.opacity = "0";
                        closeMiniCart();
                        return;
                    }

                    cartPanel.style.transform = "";
                    cartBackdrop.style.opacity = "";
                    window.setTimeout(() => {
                        if (!cartPanel.classList.contains("is-closing")) {
                            cartPanel.style.transition = "";
                            cartBackdrop.style.transition = "";
                        }
                    }, 400);
                };

                cartPanel.addEventListener("pointerdown", start);
                cartPanel.addEventListener("pointermove", move);
                cartPanel.addEventListener("pointerup", end);
                cartPanel.addEventListener("pointercancel", end);
            })();

            const updateCartCount = (count) => {
                const badge = document.querySelector(".ioulia-cart-count");
                if (!badge) return;
                badge.textContent = String(count);
                badge.classList.toggle("is-empty", Number(count) === 0);
            };

            const replaceMiniCart = (html) => {
                const shell = cartPanel.querySelector(".ioulia-mini-cart-shell");
                if (shell && html) shell.outerHTML = html;
            };

            const refreshMiniCartFragments = async () => {
                /* cache: no-store only speaks to the browser. A page cache in front
                   of WordPress keys on the URL, so the fragments request needs a
                   URL nothing has seen before, or it is answered with the cart as
                   it stood the first time anyone asked. */
                const bustedUrl = cartFragmentsUrl
                    + (cartFragmentsUrl.indexOf("?") > -1 ? "&" : "?")
                    + "_igc=" + Date.now();

                const response = await fetch(bustedUrl, {
                    method: "POST",
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: { "X-Requested-With": "XMLHttpRequest", "Cache-Control": "no-cache" }
                });
                const result = await response.json();

                if (!response.ok || !result?.fragments) {
                    throw new Error("Cart fragments could not be refreshed.");
                }

                replaceMiniCart(result.fragments[".ioulia-mini-cart-shell"]);

                const countMarkup = result.fragments[".ioulia-cart-count"];
                const currentCount = document.querySelector(".ioulia-cart-count");
                if (currentCount && countMarkup) currentCount.outerHTML = countMarkup;

                if (window.jQuery) {
                    window.jQuery(document.body).trigger("wc_fragments_refreshed");
                }
            };

            /* The Store API answers every write with the cart in full, so the row
               can be brought in line from the reply rather than from a second
               request that a cache may or may not answer honestly. */
            const applyCartToMarkup = (cart, cartItemKey, operation) => {
                const row = cartPanel.querySelector('.ioulia-mini-cart-item[data-cart-key="' + cartItemKey + '"]');
                if (!row) return;

                if (operation === "remove") {
                    row.remove();

                    if (!cartPanel.querySelector(".ioulia-mini-cart-item")) {
                        const shell = cartPanel.querySelector(".ioulia-mini-cart-shell");
                        if (shell) shell.classList.add("is-emptying");
                    }

                    return;
                }

                const item = (cart.items || []).find(entry => entry.key === cartItemKey);
                if (!item) return;

                const readout = row.querySelector(".ioulia-mini-cart-quantity span");
                if (readout) readout.textContent = String(item.quantity);

                const most = item.quantity_limits && item.quantity_limits.maximum;
                const steppers = row.querySelectorAll("[data-ioulia-quantity]");

                if (steppers.length === 2) {
                    steppers[0].dataset.iouliaQuantity = String(Math.max(0, item.quantity - 1));
                    steppers[1].dataset.iouliaQuantity = String(item.quantity + 1);
                    steppers[1].disabled = !!most && item.quantity >= most;
                }
            };

            const changeCartItemViaStoreApi = async (cartItemKey, operation, quantity) => {
                const cartResponse = await fetch(cartStoreApiUrl, {
                    method: "GET",
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: { "Accept": "application/json" }
                });

                if (!cartResponse.ok) {
                    throw new Error("The WooCommerce cart session is unavailable.");
                }

                const cartToken = cartResponse.headers.get("Cart-Token");
                const storeNonce = cartResponse.headers.get("Nonce");
                const effectiveOperation = operation === "quantity" && quantity < 1 ? "remove" : operation;
                const endpoint = effectiveOperation === "remove" ? "remove-item" : "update-item";
                const headers = {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                };

                if (storeNonce) {
                    headers.Nonce = storeNonce;
                } else if (cartToken) {
                    headers["Cart-Token"] = cartToken;
                } else {
                    throw new Error("The WooCommerce cart session could not be verified.");
                }

                const payload = effectiveOperation === "remove"
                    ? { key: cartItemKey }
                    : { key: cartItemKey, quantity };
                const updateResponse = await fetch(`${cartStoreApiUrl}/${endpoint}`, {
                    method: "POST",
                    credentials: "same-origin",
                    cache: "no-store",
                    headers,
                    body: JSON.stringify(payload)
                });
                const result = await updateResponse.json();

                if (!updateResponse.ok) {
                    throw new Error(result?.message || "Cart update failed.");
                }

                updateCartCount(result.items_count || 0);
                applyCartToMarkup(result, cartItemKey, effectiveOperation);

                /* Fragments still run, for the prices and the subtotal, but the
                   quantity the visitor just changed is already correct. If this
                   request is stale or fails, the panel is not left lying. */
                try {
                    await refreshMiniCartFragments();
                } catch (error) {
                    console.error(error);
                }
            };

            const changeCartItem = async (item, operation, quantity = 0) => {
                if (!item || item.classList.contains("is-updating")) return;

                item.classList.add("is-updating");
                cartPanel.setAttribute("aria-busy", "true");

                try {
                    await changeCartItemViaStoreApi(item.dataset.cartKey || "", operation, quantity);
                } catch (error) {
                    item.classList.remove("is-updating");
                    console.error(error);
                } finally {
                    if (item.isConnected) item.classList.remove("is-updating");
                    cartPanel.removeAttribute("aria-busy");
                }
            };

            cartOpen.addEventListener("click", openMiniCart);
            cartCloseButtons.forEach(button => button.addEventListener("click", () => closeMiniCart()));

            cartPanel.addEventListener("click", (event) => {
                const removeButton = event.target.closest("[data-ioulia-remove]");
                const quantityButton = event.target.closest("[data-ioulia-quantity]");
                const item = event.target.closest(".ioulia-mini-cart-item");

                if (removeButton && item) {
                    event.preventDefault();
                    changeCartItem(item, "remove");
                } else if (quantityButton && item && !quantityButton.disabled) {
                    event.preventDefault();
                    changeCartItem(item, "quantity", Number(quantityButton.dataset.iouliaQuantity));
                }
            });

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && cartPanel.classList.contains("is-open")) {
                    closeMiniCart();
                }

                if (event.key === "Tab" && cartPanel.classList.contains("is-open")) {
                    const focusable = Array.from(cartPanel.querySelectorAll('a[href], button:not([disabled])'))
                        .filter(element => element.offsetParent !== null);

                    if (!focusable.length) return;
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });

            if (window.jQuery) {
                window.jQuery(document.body).on("added_to_cart", () => {
                    openMiniCart();
                });
            }

            /* --- 1. Basic Header Scroll State --- */
            window.addEventListener("scroll", () => {
                if (window.scrollY > 40) {
                    header.classList.add("scrolled");
                } else {
                    header.classList.remove("scrolled");
                }
            });

            /* --- 1b. Publish the header height for the rest of the site ---
               The header is fixed and shrinks once the page is scrolled, so a
               page that reserves room for it has to know its *unscrolled*
               height or the reserved band would move while reading. The class
               is lifted for the measurement and put straight back, before the
               browser paints, so nothing is visible and the transition is
               suppressed for that single frame. */
            const measureHeader = () => {
                const wasScrolled = header.classList.contains("scrolled");
                if (wasScrolled) {
                    header.style.transition = "none";
                    header.classList.remove("scrolled");
                }

                const full = Math.ceil(header.getBoundingClientRect().height);

                if (wasScrolled) {
                    header.classList.add("scrolled");
                    header.getBoundingClientRect();
                    header.style.transition = "";
                }

                document.documentElement.style.setProperty("--ioulia-header-h", full + "px");
            };

            let headerFrame = 0;
            const requestHeaderMeasure = () => {
                if (headerFrame) return;
                headerFrame = window.requestAnimationFrame(() => {
                    headerFrame = 0;
                    measureHeader();
                });
            };

            measureHeader();
            window.addEventListener("resize", requestHeaderMeasure, { passive: true });
            window.addEventListener("load", requestHeaderMeasure);
            if ("ResizeObserver" in window) {
                new ResizeObserver(requestHeaderMeasure).observe(header);
            }
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(requestHeaderMeasure);
            }

            /* --- 2. Menu Open / Close Logic --- */
            burger.addEventListener("click", () => {
                if (!overlay.classList.contains("active")) {
                    closeMiniCart(false);
                }
                header.classList.toggle("menu-open");
                overlay.classList.toggle("active");
                burger.setAttribute("aria-expanded", overlay.classList.contains("active") ? "true" : "false");
                syncBodyLock();
            });

            const menuLinks = document.querySelectorAll(".ioulia-menu-item-link");
            menuLinks.forEach(link => {
                link.addEventListener("click", () => {
                    closeMenu();
                });
            });

            /* --- 3. Intersection Observer for Light Mode Sections --- */
            if ('IntersectionObserver' in window) {
                const lightSections = document.querySelectorAll('.light-mode');

                const observerOptions = {
                    root: null,
                    rootMargin: '-50px 0px -90% 0px', 
                    threshold: 0
                };

                const headerObserver = new IntersectionObserver((entries) => {
                    let isOverLightSection = false;

                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            isOverLightSection = true;
                        }
                    });

                    if (isOverLightSection) {
                        header.classList.add('ioulia-nav-light');
                    } else {
                        header.classList.remove('ioulia-nav-light');
                    }
                }, observerOptions);

                lightSections.forEach(section => {
                    headerObserver.observe(section);
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
