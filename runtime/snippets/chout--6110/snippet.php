<?php
/**
 * CHOUT — retired.
 *
 * This wrapped the checkout in an .ig-checkout-app shell with its own two-step
 * navigation. The wrapper never rendered: it hung on a the_content filter, and
 * the checkout page reaches the shortcode through a canvas instead, so
 * in_the_loop() was never true there.
 *
 * What did ship was 25KB of stylesheet on every checkout page, most of it
 * written for that missing wrapper and the rest landing on WooCommerce's
 * markup half-applied. That is what left the checkout with notices over the
 * header and the form in a cut-off white box.
 *
 * The checkout is dressed by the "Checkout" canvas now. WooCommerce keeps the
 * form, the validation, the order review and the gateways exactly as they
 * were; only the appearance is ours.
 *
 * Kept as an empty file rather than deleted: importing never removes a record
 * from WordPress, so the snippet has to be disabled here to be disabled there.
 */
