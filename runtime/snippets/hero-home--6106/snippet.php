<?php
/**
 * IOULIA — Pinned Cursor Product Hero
 *
 * Paste into Code Snippets or the child theme's functions.php.
 *
 * Shortcode:
 * [ioulia_cursor_products_hero]
 *
 * Example:
 * [ioulia_cursor_products_hero
 *   title_line_1="Αντικείμενα που πλάθονται αργά."
 *   title_line_2="Φτιαγμένα για να τα κρατάς."
 *   second_line_1="Μια πρακτική κεραμικής"
 *   second_line_2="φτιαγμένη στο χέρι."
 *   pieces_url="/store/"
 *   workshops_url="/workshops/"
 * ]
 */

if (!function_exists('ioulia_cursor_products_hero_shortcode')) {

    function ioulia_cursor_products_hero_shortcode($atts = array()) {
        if (!function_exists('wc_get_products')) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'limit'          => 8,
                'category'       => '',
                'title_line_1'   => 'Αντικείμενα που πλάθονται αργά.',
                'title_line_2'   => 'Φτιαγμένα για να τα κρατάς.',
                'second_line_1'  => 'Μια πρακτική κεραμικής',
                'second_line_2'  => 'φτιαγμένη στο χέρι.',
                'pieces_text'    => 'Δες τα κομμάτια',
                'pieces_url'     => '/store/',
                'workshops_text' => 'Εργαστήρια Κεραμικής',
                'workshops_url'  => '/workshops/',
                'show_price'     => 'no',
                'cursor_word'    => 'Δες',
                'second_svg'     => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/07/Group-13.svg',
            ),
            $atts,
            'ioulia_cursor_products_hero'
        );

        $args = array(
            'status'     => 'publish',
            'visibility' => 'visible',
            'limit'      => max(3, min(12, absint($atts['limit']))),
            'orderby'    => 'date',
            'order'      => 'DESC',
            'return'     => 'objects',
        );

        if (!empty($atts['category'])) {
            $args['category'] = array_map(
                'sanitize_title',
                array_filter(array_map('trim', explode(',', (string) $atts['category'])))
            );
        }

        $products = array();

        foreach (wc_get_products($args) as $product) {
            if (!$product instanceof WC_Product) {
                continue;
            }

            $image_id = $product->get_image_id();
            $image = $image_id ? wp_get_attachment_image_src($image_id, 'large') : false;

            if (!$image) {
                continue;
            }

            $products[] = array(
                'title'  => wp_strip_all_tags($product->get_name()),
                'url'    => $product->get_permalink(),
                'image'  => $image[0],
                'width'  => (int) $image[1],
                'height' => (int) $image[2],
                'price'  => strtolower((string) $atts['show_price']) === 'yes'
                    ? wp_strip_all_tags($product->get_price_html())
                    : '',
            );
        }

        if (!$products) {
            return '';
        }

        $id = 'icph-' . wp_generate_uuid4();
        static $css_printed = false;

        ob_start();

        if (!$css_printed) :
            $css_printed = true;
            ?>
<style id="ioulia-pinned-cursor-hero-css">
.icph-wrap,
.icph-wrap * {
    box-sizing: border-box;
}

.icph-wrap {
    --icph-grey: #454440;
    --icph-dark: #383735;
    --icph-paper: var(--ioulia-cream, #fefae4);
    --icph-x: clamp(16px, 2.2vw, 36px);

    position: relative;
    width: 100%;
    height: 185svh;
    background: transparent !important;
}

.icph {
    position: sticky;
    top: 0;
    isolation: isolate;
    width: 100%;
    height: 100svh;
    min-height: 620px;
    overflow: hidden;
    display: grid;
    place-items: center;
    padding: clamp(82px, 10svh, 126px) var(--icph-x) clamp(34px, 5svh, 62px);
    color: var(--icph-grey);
    background: transparent !important;
    /* Keep the native system cursor visible; the Explore circle is only a follower. */
    cursor: auto !important;
    user-select: none;
    -webkit-user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.icph a {
    cursor: pointer !important;
}

/* --- Entrance Animations --- */
@keyframes icph-entrance {
    0% {
        opacity: 0;
        transform: translateY(35px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.icph__marks {
    position: relative;
    width: clamp(100px, 9vw, 168px);
    height: clamp(38px, 3.5vw, 66px);
    margin-bottom: clamp(22px, 3vw, 42px);
    opacity: 0;
    animation: icph-entrance 1.2s cubic-bezier(.16, 1, .3, 1) forwards;
}

.icph__title--first .icph__line {
    opacity: 0;
    animation: icph-entrance 1.2s cubic-bezier(.16, 1, .3, 1) forwards;
}

.icph__title--first .icph__line:nth-child(1) {
    animation-delay: 0.15s;
}

.icph__title--first .icph__line:nth-child(2) {
    animation-delay: 0.25s;
}

.icph__meta span {
    opacity: 0;
    animation: icph-entrance 1.2s cubic-bezier(.16, 1, .3, 1) forwards;
}

.icph__meta span:nth-child(1) { animation-delay: 0.35s; }
.icph__meta span:nth-child(2) { animation-delay: 0.45s; }
.icph__meta span:nth-child(3) { animation-delay: 0.55s; }
/* --------------------------- */

.icph__stage {
    position: relative;
    z-index: 4;
    width: min(100%, 1540px);
    display: grid;
    place-items: center;
    text-align: center;
    pointer-events: none;
}

.icph__mark {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    will-change: opacity, transform, filter;
}

.icph__mark--old {
    color: var(--icph-grey);
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
}

.icph__mark--new {
    opacity: 0;
    transform: translate3d(0, 16px, 0) scale(.92);
    filter: blur(5px);
}

.icph__mark svg,
.icph__mark img {
    display: block;
    width: 100%;
    height: auto;
    max-width: none !important;
}

.icph__mark--old path {
    fill: currentColor;
}

.icph__mark--new img {
    object-fit: contain;
}

.icph__copy {
    position: relative;
    width: 100%;
    min-height: clamp(84px, 8vw, 138px);
}

.icph__title {
    position: absolute;
    inset: 0;
    display: grid;
    align-content: center;
    margin: 0;
    color: inherit;
    font-family: inherit;
    font-size: clamp(32px, 2.45vw, 46px);
    font-weight: 400;
    line-height: 1.02;
    letter-spacing: -.045em;
    text-wrap: balance;
}

.icph__title--second {
    color: var(--icph-dark);
    opacity: 0;
    pointer-events: none;
}

.icph__line {
    display: block;
    overflow: visible;
}

.icph__line-text {
    display: block;
    will-change: transform, opacity, filter;
}

.icph__title--first .icph__line-text {
    transform: translate3d(0, 0, 0);
}

.icph__title--second .icph__line-text {
    transform: translate3d(0, 14px, 0);
    filter: blur(2px);
}

.icph__actions {
    position: absolute;
    z-index: 8;
    left: 50%;
    bottom: clamp(46px, 6.5svh, 82px);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    opacity: 0;
    visibility: hidden;
    transform: translate3d(-50%, 18px, 0);
    pointer-events: none;
    will-change: transform, opacity;
}

.icph__product {
    position: absolute !important;
    z-index: 7;
    top: 0;
    left: 0;
    display: block !important;
    width: clamp(165px, 16vw, 280px);
    height: auto !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    outline: 0 !important;
    background: transparent !important;
    background-color: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
    color: var(--icph-grey) !important;
    text-decoration: none !important;
    opacity: 0;
    visibility: hidden;
    transform: translate3d(-9999px, -9999px, 0);
    will-change: transform, opacity;
    pointer-events: none;
}

.icph__product.is-visible {
    visibility: visible;
    pointer-events: auto;
}

.elementor .icph__product,
.elementor .icph__product:hover,
.elementor .icph__product:focus,
.elementor .icph__product:active,
.icph__product:hover,
.icph__product:focus,
.icph__product:active {
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    background-color: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
    color: var(--icph-grey) !important;
    text-decoration: none !important;
}

.icph__product-image {
    display: block !important;
    width: 100% !important;
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    max-width: none !important;
    aspect-ratio: auto !important;
    object-fit: contain !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    pointer-events: none;
    user-select: none;
    -webkit-user-drag: none;
}

.icph__product-caption {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    margin-top: 7px;
    color: inherit;
    font-family: inherit;
    font-size: var(--ioulia-micro);
    font-weight: 400;
    line-height: 1.15;
    opacity: 0;
    transform: translateY(4px);
    transition:
        opacity 220ms ease,
        transform 320ms cubic-bezier(.16, 1, .3, 1);
}

.icph__product:hover .icph__product-caption,
.icph__product:focus-visible .icph__product-caption {
    opacity: .82;
    transform: translateY(0);
}

.icph__product:focus-visible {
    outline: 1px solid currentColor !important;
    outline-offset: 7px !important;
}

.icph__cursor {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 999999;
    display: grid;
    place-items: center;
    width: 62px;
    height: 62px;
    border: 1px solid currentColor;
    border-radius: 50%;
    color: var(--icph-grey);
    background: transparent;
    pointer-events: none;
    opacity: 0;
    transform: translate(-50%, -50%) scale(.76);
    transition:
        opacity 180ms ease,
        transform 340ms cubic-bezier(.16, 1, .3, 1);
    font-family: inherit;
    font-size: var(--ioulia-micro);
    font-weight: 500;
    line-height: 1;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.icph__cursor.is-visible {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.icph__cursor.is-open {
    transform: translate(-50%, -50%) scale(.88);
}

.icph__meta {
    position: absolute;
    z-index: 6;
    left: var(--icph-x);
    right: var(--icph-x);
    bottom: clamp(18px, 2.6svh, 30px);
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 18px;
    color: var(--icph-grey);
    font-family: inherit;
    font-size: var(--ioulia-micro);
    font-weight: 400;
    line-height: 1.1;
    letter-spacing: .045em;
    text-transform: uppercase;
    opacity: .58;
    pointer-events: none;
    will-change: opacity;
}

.icph__meta span:nth-child(2) {
    justify-self: center;
}

.icph__meta span:last-child {
    justify-self: end;
}

.icph__mobile-product {
    display: none;
}

@media (max-width: 900px), (hover: none), (pointer: coarse) {
    .icph-wrap {
        height: 175svh;
    }

    .icph {
        cursor: auto;
        min-height: 560px;
        padding: clamp(102px, 13svh, 132px) 16px max(26px, env(safe-area-inset-bottom));
    }

    .icph__stage {
        transform: translateY(clamp(-96px, -9svh, -64px));
    }

    .icph__marks {
        width: clamp(106px, 29vw, 136px);
        height: clamp(40px, 11vw, 54px);
        margin-bottom: clamp(12px, 2svh, 18px);
    }

    .icph__mark--new img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
    }

    .icph__title {
        font-size: clamp(27px, 7.2vw, 34px);
        line-height: 1.04;
        letter-spacing: -.05em;
    }

    .icph__copy {
        min-height: clamp(124px, 34vw, 164px);
    }

    .icph__product,
    .icph__cursor {
        display: none !important;
    }

    .icph__mobile-product {
        position: absolute;
        z-index: 6;
        left: 50%;
        bottom: max(78px, calc(7svh + env(safe-area-inset-bottom)));
        display: block;
        width: min(46vw, 184px);
        color: var(--icph-grey);
        text-decoration: none;
        opacity: 1;
        transform: translateX(-50%);
        transition: opacity 200ms ease, transform 320ms cubic-bezier(.16, 1, .3, 1);
        will-change: opacity, transform;
    }

    .icph__mobile-product.is-changing {
        opacity: 0 !important;
        transform: translateX(-50%) translateY(8px) scale(.97) !important;
    }

    .icph__mobile-product img {
        display: block;
        width: 100% !important;
        height: auto !important;
        max-width: none !important;
        aspect-ratio: auto !important;
        object-fit: contain !important;
    }

    .icph__mobile-product span {
        display: none;
    }

    .icph__actions {
        width: calc(100% - 32px);
        top: calc(50% + 82px);
        bottom: auto;
        flex-wrap: wrap;
    }

    .icph__button {
        min-height: 40px;
    }

    .icph__meta {
        display: none;
    }
}

@media (max-width: 420px) {
    .icph__title {
        font-size: clamp(27px, 7.1vw, 30px);
    }

    .icph__copy {
        min-height: clamp(132px, 40vw, 168px);
    }

    .icph__actions {
        gap: 8px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .icph-wrap {
        height: auto;
    }

    .icph {
        position: relative;
    }

    .icph__product,
    .icph__cursor {
        display: none !important;
    }

    .icph__mobile-product {
        display: block;
    }
}
</style>
            <?php
        endif;
        ?>

<div class="icph-wrap" id="<?php echo esc_attr($id); ?>-wrap">
    <section class="icph" id="<?php echo esc_attr($id); ?>" aria-label="<?php echo esc_attr($atts['title_line_1'] . ' ' . $atts['title_line_2']); ?>">

        <div class="icph__stage">
            <div class="icph__marks" aria-hidden="true">
                <div class="icph__mark icph__mark--old">
                    <svg viewBox="0 0 194.5 72.69" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.54,7.47c2.61-.61,5.07-.28,7.76-.51,9.39-.76,18.62-.97,28.03-.08v1.96c.03,1.02.28,1.91.23,2.98l7.81.2c.98.03,1.94.41,2.17,1.38.57,3.92.51,7.59.56,12.08.13,2.45.44,4.59.37,6.98l.32,7.18c.36,2.45.78,5.96-.08,7.85-.23,1.02-.7,2.65-1.91,3.01-3.21.94-6.49.94-9.83,1.22l-.65,10.52c-.13,2.42-.31,4.69-.31,7.11.03,1.02,0,2.06-.41,3.03-3.03.64-6.7.1-10.03-.05l-5.74-.23c-1.68-.08-3.13.1-4.78.05l-5.3-.23c-.78-.05-2.04.97-2.79.18-.44-.43-.36-1.71-.36-2.47l.08-24.43c0-3.75.36-7.18.26-10.9-.08-3.9.16-7.62-.52-11.49-.05-2.14-.03-4.28.03-6.39,0-.51.03-1.02.05-1.5.02-.23-.13-.57-.38-.58-.14-.02-.16-.07-.27-.13-.12-.06-.19-.09-.24-.17l-4.28-5.27c-.34-.38-.21-1.2.23-1.3h-.02ZM45.02,47.29c2.28-.43,1.89-3.26,1.86-6.32-.03-1.78-.1-3.46-.18-5.22l-.31-7.87-.14-3.84c-.05-3.13.54-5.65-.58-6.91-1.24-1.32-3.69-1-6.13-.94v19.11c0,4.18-.05,8.15-.41,12.38,2.04.18,3.96,0,5.9-.38h0Z"/>
                        <path d="M39.28,43.59c-.36.36-.83.56-1.42.82-.41-.51-.21-1.25-.54-1.76-.34-.51-1.71-.48-2.17-.03-.47.18-.36-.43-.47-.61-.13-.28.41-1.27.96-1.89l.23-1.45c.18-.18.52,0,.59.08.23.31-.47.51-.41.87,0,.1.31.05.44.05.08,1.35,1.37,1.38,2.25,2.57-.28.31-.34.69-.28.84.05.15.26.38.83.51h-.01Z"/>
                        <path d="M194.29,38.13c-.62-2.88-1.58-5.63-2.92-8.25l-1.09-2.14c-1.47-2.06-3.83-3.06-6.08-4.13-1.81-.87-2.72-.98-4.69-1.26l-4.8-.68c-1.19-.18-1.81-1.71-1.76-2.8.08-1.53-.21-2.8-.18-4.25V5.65c.03-1.89.39-3.64-.23-5.5l-15.62-.15-.57,6.78-.41,9.32c-.15,3.52.39,5.83-3.72,7.74-1.71.79-3.31,1.73-5.28,2.17-5.72,1.27-3.96,1.64-7.56,6.82-.67.94-1,2.71-1.18,3.83-.96,5.6-.9,11.06-.75,16.76.1,4.61.03,9.1,1.27,13.55.7,2.52,3.39,4.03,5.92,4.03l11.07.08,7.76-.08,8.22.08,11.22.13c2.22,0,4.03-.36,5.69-1.76,4.55-3.95,5.07-6.9,5.38-13.12l.52-10.73c-.47-2.52-.65-4.92-.21-7.46h0ZM181.36,58.91c-2.61,1.12-5.25,1.48-8.07,1.76-6.1.64-14.2,1.43-19.63-.33-1.71-.56-2.4-2.27-2.59-3.87l-.13-5.03c.36-1.48-.29-3.41-.16-4.91l.7-6.74c.13-1.3,0-3.13,1.06-4,2.17-1.73,5.04-1.5,7.63-1.81l7.11-.79c4.5-.51,9.16-.87,13.11.82,1.84.76,1.89,7.06,1.99,10.27l.15,4.2.08,7.52c0,1.04-.88,1.99-1.27,2.93l.02-.02Z"/>
                        <path d="M128.19,19.37c-1.84-2.11-4.53-2.96-7.45-2.75-.49.03-2.02.15-2.02-.46l-.18-5.27c0-.43-.18-.89-.67-1.07-.88-.15-1.66-.1-2.35.18l-.28,2.27c-.57-.15-.8-.56-.75-1.17-.08-.48-.13-.97-.13-1.48l-13.89-.89c-2.2-.13-5.15-.33-5.64.43-.1.2-.13.82.1.99,1.19.79,1.73,1.86,2.48,3.01,1.89,2.83,3.08,5.89,3.1,9.35l.08,4.84c.03,1.58-.67,3.01-1.76,4.2-1.71,1.91-3.28,3.77-4.84,5.86-5.74,7.67-3.23,17.45,1.76,25.04l4.14,6.32c.57.87,1.86.99,2.9,1.15,5.3.74,10.65.66,15.85-.54.65-.15.78-1.07,1.03-1.5,2.27-3.92,4.55-7.59,6.41-11.72,1.45-3.24,2.27-6.62,1.84-10.11-.34-2.5.23-5.94,1.03-8.53.93-3.06,1.6-6.11,1.89-9.32.31-3.26-.6-6.27-2.66-8.81v-.02ZM124.96,38.32l-4.5-5.89c-2.56-3.31-2.33-7.06-1.89-11.11.23-2.17,4.84-1.53,6.52.13,1.47,1.45,2.3,3.36,2.2,5.5-.16,3.9-1.03,7.62-2.33,11.36h0Z"/>
                        <path d="M62.35,68.94c-1.14-1.25-1.73-2.55-2.43-3.92-2.82-5.71-3.05-12.38,1.81-16.76,2.15-1.94,2.82-4.66,2.17-7.36l-1.27-5.17c-.67-2.78-2.02-5.53-1.81-6.01.39-.92,13.21-.51,17.38-.05-1.66,4.15-2.95,8.03-3.57,12.23-.31,2.09.36,3.95,1.78,5.45,1.22,1.5,2.74,2.85,3.47,4.64,1.76,4.38,1.03,10.32-1.66,14.24l-3.62,5.32-10.86-.08c-.28-.97-.7-1.76-1.37-2.5l-.03-.03h.01Z"/>
                    </svg>
                </div>

                <div class="icph__mark icph__mark--new">
                    <img src="<?php echo esc_url($atts['second_svg']); ?>" alt="">
                </div>
            </div>

            <div class="icph__copy">
                <h1 class="icph__title icph__title--first">
                    <span class="icph__line"><span class="icph__line-text"><?php echo esc_html($atts['title_line_1']); ?></span></span>
                    <span class="icph__line"><span class="icph__line-text"><?php echo esc_html($atts['title_line_2']); ?></span></span>
                </h1>

                <h2 class="icph__title icph__title--second">
                    <span class="icph__line"><span class="icph__line-text"><?php echo esc_html($atts['second_line_1']); ?></span></span>
                    <span class="icph__line"><span class="icph__line-text"><?php echo esc_html($atts['second_line_2']); ?></span></span>
                </h2>
            </div>
        </div>

        <a class="icph__product" href="<?php echo esc_url($products[0]['url']); ?>">
            <img
                class="icph__product-image"
                src="<?php echo esc_url($products[0]['image']); ?>"
                width="<?php echo esc_attr($products[0]['width']); ?>"
                height="<?php echo esc_attr($products[0]['height']); ?>"
                alt="<?php echo esc_attr($products[0]['title']); ?>"
                draggable="false"
            >
            <span class="icph__product-caption">
                <span class="icph__product-name"><?php echo esc_html($products[0]['title']); ?></span>
                <span class="icph__product-price"><?php echo esc_html($products[0]['price']); ?></span>
            </span>
        </a>

        <a class="icph__mobile-product" href="<?php echo esc_url($products[0]['url']); ?>">
            <img
                src="<?php echo esc_url($products[0]['image']); ?>"
                width="<?php echo esc_attr($products[0]['width']); ?>"
                height="<?php echo esc_attr($products[0]['height']); ?>"
                alt="<?php echo esc_attr($products[0]['title']); ?>"
            >
            <span><?php echo esc_html($products[0]['title']); ?></span>
        </a>

        <div class="icph__actions">
            <a class="icph__button icph__button--filled" href="<?php echo esc_url($atts['pieces_url']); ?>">
                <?php echo esc_html($atts['pieces_text']); ?>
            </a>
            <a class="icph__button icph__button--outline" href="<?php echo esc_url($atts['workshops_url']); ?>">
                <?php echo esc_html($atts['workshops_text']); ?>
            </a>
        </div>

        <div class="icph__meta" aria-hidden="true">
            <span>Ioulia Geraskli / Ceramic Lab</span>
            <span class="icph__meta-product"><?php echo esc_html($products[0]['title']); ?></span>
            <span>Αθήνα, Ελλάδα</span>
        </div>

        <div class="icph__cursor" aria-hidden="true"><?php echo esc_html($atts['cursor_word']); ?></div>
    </section>
</div>

<script>
(function () {
    "use strict";

    var root = document.getElementById(<?php echo wp_json_encode($id); ?>);
    var wrap = document.getElementById(<?php echo wp_json_encode($id . '-wrap'); ?>);
    var products = <?php echo wp_json_encode($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    if (!root || !wrap || !products.length || root.dataset.icphReady === "true") {
        return;
    }

    root.dataset.icphReady = "true";

    var product = root.querySelector(".icph__product");
    var productImage = root.querySelector(".icph__product-image");
    var productName = root.querySelector(".icph__product-name");
    var productPrice = root.querySelector(".icph__product-price");
    var mobileProduct = root.querySelector(".icph__mobile-product");
    var mobileImage = mobileProduct.querySelector("img");
    var mobileName = mobileProduct.querySelector("span");

    var oldMark = root.querySelector(".icph__mark--old");
    var newMark = root.querySelector(".icph__mark--new");
    var firstTitle = root.querySelector(".icph__title--first");
    var secondTitle = root.querySelector(".icph__title--second");
    var firstLines = Array.prototype.slice.call(firstTitle.querySelectorAll(".icph__line-text"));
    var secondLines = Array.prototype.slice.call(secondTitle.querySelectorAll(".icph__line-text"));
    var actions = root.querySelector(".icph__actions");
    var meta = root.querySelector(".icph__meta");
    var metaProduct = root.querySelector(".icph__meta-product");
    var cursor = root.querySelector(".icph__cursor");

    var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
    var mobileLayout = window.matchMedia("(max-width: 900px), (hover: none), (pointer: coarse)");
    var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    var productIndex = 0;
    var lastTime = performance.now();
    var raf = 0;
    var mouse = {
        x: window.innerWidth / 2,
        y: window.innerHeight / 2,
        lastX: window.innerWidth / 2,
        lastY: window.innerHeight / 2,
        distance: 0,
        inside: false,
        moved: false
    };

    var card = {
        x: window.innerWidth / 2,
        y: window.innerHeight / 2,
        vx: 0,
        vy: 0
    };

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function smoothstep(edge0, edge1, value) {
        var x = clamp((value - edge0) / Math.max(edge1 - edge0, 0.0001), 0, 1);
        return x * x * (3 - 2 * x);
    }

    function setProduct(index) {
        var data = products[index % products.length];
        productIndex = index % products.length;

        product.href = data.url;
        productImage.src = data.image;
        productImage.alt = data.title;
        productImage.width = data.width || 900;
        productImage.height = data.height || 1200;
        productName.textContent = data.title;
        productPrice.textContent = data.price || "";

        mobileProduct.href = data.url;
        mobileImage.src = data.image;
        mobileImage.alt = data.title;
        mobileImage.width = data.width || 900;
        mobileImage.height = data.height || 1200;
        mobileName.textContent = data.title;

        if (metaProduct) {
            metaProduct.textContent = data.title;
        }
    }

    function nextProduct() {
        setProduct((productIndex + 1) % products.length);
    }

    function progress() {
        var rect = wrap.getBoundingClientRect();
        var distance = Math.max(wrap.offsetHeight - window.innerHeight, 1);
        return clamp(-rect.top / distance, 0, 1);
    }

    function renderScrollState() {
        var p = progress();

        var titleOut = smoothstep(0.22, 0.48, p);
        var titleIn = smoothstep(0.36, 0.60, p);
        var markSwap = smoothstep(0.28, 0.58, p);
        var productOut = smoothstep(0.48, 0.58, p);
        var buttonsIn = smoothstep(0.62, 0.76, p);

        oldMark.style.opacity = String(1 - markSwap);
        oldMark.style.transform =
            "translate3d(0," + (-10 * markSwap).toFixed(2) + "px,0) scale(" +
            (1 - 0.08 * markSwap).toFixed(4) + ")";
        oldMark.style.filter = "blur(" + (4 * markSwap).toFixed(2) + "px)";

        newMark.style.opacity = String(markSwap);
        newMark.style.transform =
            "translate3d(0," + (16 * (1 - markSwap)).toFixed(2) + "px,0) scale(" +
            (0.92 + 0.08 * markSwap).toFixed(4) + ")";
        newMark.style.filter = "blur(" + (5 * (1 - markSwap)).toFixed(2) + "px)";

        firstTitle.style.opacity = String(1 - titleOut);
        firstLines.forEach(function (line) {
            line.style.transform =
                "translate3d(0," + (-12 * titleOut).toFixed(2) + "px,0) scale(" +
                (1 - 0.012 * titleOut).toFixed(4) + ")";
            line.style.filter = "blur(" + (1.5 * titleOut).toFixed(2) + "px)";
        });

        secondTitle.style.opacity = String(titleIn);
        secondLines.forEach(function (line, index) {
            var delay = index * 0.035;
            var local = smoothstep(0.36 + delay, 0.60 + delay, p);
            line.style.transform =
                "translate3d(0," + (14 * (1 - local)).toFixed(2) + "px,0) scale(" +
                (0.988 + 0.012 * local).toFixed(4) + ")";
            line.style.filter = "blur(" + (2 * (1 - local)).toFixed(2) + "px)";
        });

        var productOpacity = 1 - productOut;
        product.style.opacity = mouse.inside && mouse.moved ? String(productOpacity) : "0";
        product.style.pointerEvents = productOpacity > 0.18 ? "auto" : "none";
        cursor.style.opacity = mouse.inside && productOpacity > 0.18 ? "1" : "0";
        mobileProduct.style.opacity = String(productOpacity);
        mobileProduct.style.transform =
            "translateX(-50%) translateY(" + (-16 * productOut).toFixed(2) + "px) scale(" +
            (1 - 0.06 * productOut).toFixed(4) + ")";
        mobileProduct.style.pointerEvents = productOpacity > 0.18 ? "auto" : "none";

        actions.style.opacity = String(buttonsIn);
        actions.style.visibility = buttonsIn > 0.01 ? "visible" : "hidden";
        actions.style.pointerEvents = buttonsIn > 0.72 ? "auto" : "none";

        if (mobileLayout.matches) {
            var rootRect = root.getBoundingClientRect();
            var secondTitleBottom = secondLines.reduce(function (bottom, line) {
                return Math.max(bottom, line.getBoundingClientRect().bottom);
            }, rootRect.top);
            var actionsTop = secondTitleBottom - rootRect.top + 28;
            var actionsMax = root.clientHeight - actions.offsetHeight - 24;

            actions.style.top = clamp(actionsTop, 0, actionsMax).toFixed(2) + "px";
        } else {
            actions.style.top = "";
        }

        actions.style.transform =
            "translate3d(-50%," + (12 * (1 - buttonsIn)).toFixed(2) + "px,0) scale(" +
            (0.985 + 0.015 * buttonsIn).toFixed(4) + ")";

        meta.style.opacity = String(0.58 * (1 - smoothstep(0.56, 0.78, p)));
    }

    function syncPointerPresence() {
        if (!finePointer || reducedMotion || !mouse.moved) {
            return;
        }

        var rect = root.getBoundingClientRect();
        var withinViewport =
            rect.bottom > 0 &&
            rect.top < window.innerHeight &&
            mouse.x >= rect.left &&
            mouse.x <= rect.right &&
            mouse.y >= Math.max(rect.top, 0) &&
            mouse.y <= Math.min(rect.bottom, window.innerHeight);

        mouse.inside = withinViewport;

        if (withinViewport && progress() < 0.96) {
            product.classList.add("is-visible");
            cursor.classList.add("is-visible");
        } else {
            product.classList.remove("is-visible");
            cursor.classList.remove("is-visible");
        }
    }

    function requestRender() {
        if (!raf) {
            raf = requestAnimationFrame(render);
        }
    }

    function render(now) {
        raf = 0;

        var dt = clamp((now - lastTime) / 1000, 0.001, 0.034);
        lastTime = now;

        renderScrollState();

        if (finePointer && !reducedMotion) {
            var rect = root.getBoundingClientRect();
            var targetX = clamp(mouse.x - rect.left, 30, rect.width - 30);
            var targetY = clamp(mouse.y - rect.top, 30, rect.height - 30);

            var forceX = (targetX - card.x) * 29;
            var forceY = (targetY - card.y) * 29;

            card.vx = (card.vx + forceX * dt) * Math.pow(0.80, dt * 60);
            card.vy = (card.vy + forceY * dt) * Math.pow(0.80, dt * 60);
            card.x += card.vx * dt;
            card.y += card.vy * dt;

            var rotation = clamp(card.vx * 0.012, -5, 5);

            product.style.transform =
                "translate3d(" + card.x.toFixed(2) + "px," + card.y.toFixed(2) + "px,0) " +
                "translate(-50%,-50%) rotate(" + rotation.toFixed(2) + "deg)";

            cursor.style.left = mouse.x + "px";
            cursor.style.top = mouse.y + "px";
        }

        if (
            mouse.inside ||
            Math.abs(card.vx) + Math.abs(card.vy) > 0.06
        ) {
            requestRender();
        }
    }

    if (finePointer && !reducedMotion) {
        root.addEventListener("pointerenter", function (event) {
            if (event.pointerType && event.pointerType !== "mouse") {
                return;
            }

            mouse.inside = true;
            mouse.x = mouse.lastX = event.clientX;
            mouse.y = mouse.lastY = event.clientY;

            if (mouse.moved && progress() < 0.96) {
                product.classList.add("is-visible");
                cursor.classList.add("is-visible");
            }

            requestRender();
        });

        root.addEventListener("pointermove", function (event) {
            if (event.pointerType && event.pointerType !== "mouse") {
                return;
            }

            var dx = event.clientX - mouse.lastX;
            var dy = event.clientY - mouse.lastY;

            mouse.distance += Math.sqrt(dx * dx + dy * dy);
            mouse.x = event.clientX;
            mouse.y = event.clientY;
            mouse.lastX = event.clientX;
            mouse.lastY = event.clientY;
            mouse.inside = true;

            if (!mouse.moved) {
                var rect = root.getBoundingClientRect();
                mouse.moved = true;
                card.x = event.clientX - rect.left;
                card.y = event.clientY - rect.top;
                product.classList.add("is-visible");
                cursor.classList.add("is-visible");
            }

            if (mouse.distance >= 190 && progress() < 0.96) {
                mouse.distance = 0;
                nextProduct();
            }

            requestRender();
        });

        root.addEventListener("pointerleave", function () {
            mouse.inside = false;
            product.classList.remove("is-visible");
            cursor.classList.remove("is-visible");
            requestRender();
        });

        product.addEventListener("mouseenter", function () {
            cursor.classList.add("is-open");
        });

        product.addEventListener("mouseleave", function () {
            cursor.classList.remove("is-open");
        });
    }

    if (!reducedMotion && products.length > 1) {
        var scheduleMobileRotation = function () {
            window.setTimeout(function () {
                var rect = root.getBoundingClientRect();
                var heroIsVisible = rect.bottom > 0 && rect.top < window.innerHeight;

                if (
                    !mobileLayout.matches ||
                    document.hidden ||
                    !heroIsVisible ||
                    progress() >= 0.48 ||
                    mobileProduct.classList.contains("is-changing")
                ) {
                    scheduleMobileRotation();
                    return;
                }

                var nextIndex = (productIndex + 1) % products.length;
                var nextImage = new Image();

                nextImage.onload = function () {
                    mobileProduct.classList.add("is-changing");

                    window.setTimeout(function () {
                        setProduct(nextIndex);
                        mobileProduct.classList.remove("is-changing");
                        requestRender();
                        scheduleMobileRotation();
                    }, 220);
                };

                nextImage.onerror = scheduleMobileRotation;
                nextImage.src = products[nextIndex].image;
            }, 4200);
        };

        scheduleMobileRotation();
    }

    window.addEventListener("scroll", function () {
        syncPointerPresence();
        requestRender();
    }, { passive: true });

    window.addEventListener("resize", function () {
        syncPointerPresence();
        requestRender();
    }, { passive: true });

    setProduct(0);
    requestRender();
})();
</script>
        <?php

        return ob_get_clean();
    }

    add_shortcode('ioulia_cursor_products_hero', 'ioulia_cursor_products_hero_shortcode');
}
