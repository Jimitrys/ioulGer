<?php
/**
 * Ioulia i18n strings — the whole dictionary of the site, in one place.
 *
 * This is the file to edit when a wording changes. Nothing else needs touching:
 * templates and canvases only ever reference a key.
 *
 *   in a PHP snippet   <?php ioulia_e( 'nav.shop' ); ?>
 *   in canvas HTML     [t k="nav.shop"]
 *
 * Rules of the dictionary:
 *
 *   - 'el' is the primary language and what visitors get at the site root.
 *   - 'en' is the secondary language, served under /en/.
 *   - A missing 'en' entry falls back to the Greek one rather than breaking.
 *   - Keys are grouped by where the string appears: nav.*, cart.*, footer.*,
 *     home.*, about.*, workshops.*, contact.*, shop.*, product.*, checkout.*.
 *   - Basic inline markup (<br>, <em>, <strong>, links) is allowed in a value.
 *
 * Strings that belong to WordPress or WooCommerce itself are NOT listed here —
 * those come from the official language packs and follow the request locale.
 */

if ( ! function_exists( 'ioulia_i18n_dictionary' ) ) {
	function ioulia_i18n_dictionary( $strings ) {
		$dictionary = array(

			/* ---------------------------------------------------------------
			 * Header, menu overlay, language switcher
			 * ------------------------------------------------------------ */
			'nav.home'            => array(
				'el' => 'Αρχική',
				'en' => 'Home',
			),
			'nav.shop'            => array(
				'el' => 'Κατάστημα',
				'en' => 'Shop',
			),
			'nav.about'           => array(
				'el' => 'Σχετικά',
				'en' => 'About',
			),
			'nav.workshops'       => array(
				'el' => 'Εργαστήρια',
				'en' => 'Workshops',
			),
			'nav.contact'         => array(
				'el' => 'Επικοινωνία',
				'en' => 'Contact',
			),
			'nav.home_aria'       => array(
				'el' => 'Αρχική σελίδα',
				'en' => 'Home',
			),
			'nav.menu_toggle'     => array(
				'el' => 'Άνοιγμα μενού',
				'en' => 'Toggle menu',
			),
			'nav.cart_open'       => array(
				'el' => 'Άνοιγμα καλαθιού',
				'en' => 'Open cart',
			),
			'nav.privacy'         => array(
				'el' => 'Πολιτική Απορρήτου',
				'en' => 'Privacy Policy',
			),
			'nav.data_protection' => array(
				'el' => 'Προστασία Δεδομένων',
				'en' => 'Data Protection',
			),

			/* ---------------------------------------------------------------
			 * Mini cart
			 * ------------------------------------------------------------ */
			'cart.title'          => array(
				'el' => 'το καλάθι σου',
				'en' => 'your cart',
			),
			'cart.close'          => array(
				'el' => 'κλείσιμο',
				'en' => 'close',
			),
			'cart.close_aria'     => array(
				'el' => 'Κλείσιμο καλαθιού',
				'en' => 'Close cart',
			),
			'cart.remove'         => array(
				'el' => 'αφαίρεση',
				'en' => 'remove',
			),
			'cart.remove_aria'    => array(
				'el' => 'Αφαίρεση %s από το καλάθι',
				'en' => 'Remove %s from cart',
			),
			'cart.quantity'       => array(
				'el' => 'Ποσότητα',
				'en' => 'Quantity',
			),
			'cart.quantity_down'  => array(
				'el' => 'Μείωση ποσότητας',
				'en' => 'Decrease quantity',
			),
			'cart.quantity_up'    => array(
				'el' => 'Αύξηση ποσότητας',
				'en' => 'Increase quantity',
			),
			'cart.subtotal'       => array(
				'el' => 'Μερικό σύνολο',
				'en' => 'Subtotal',
			),
			'cart.shipping_note'  => array(
				'el' => 'Τα μεταφορικά και οι φόροι υπολογίζονται στο ταμείο.',
				'en' => 'Shipping and taxes are calculated at checkout.',
			),
			'cart.view'           => array(
				'el' => 'δες το καλάθι',
				'en' => 'view cart',
			),
			'cart.empty'          => array(
				'el' => 'το καλάθι σου είναι άδειο.',
				'en' => 'your cart is empty.',
			),
			'cart.explore_shop'   => array(
				'el' => 'δες το κατάστημα',
				'en' => 'explore the shop',
			),

			/* ---------------------------------------------------------------
			 * Footer
			 * ------------------------------------------------------------ */
			'footer.home_aria'    => array(
				'el' => 'Ioulia Geraskli — αρχική',
				'en' => 'Ioulia Geraskli home',
			),
			'footer.brand_line_1' => array(
				'el' => 'ioulia geraskli',
				'en' => 'ioulia geraskli',
			),
			'footer.brand_line_2' => array(
				'el' => 'εργαστήριο κεραμικής',
				'en' => 'ceramic lab',
			),
			'footer.legal_aria'   => array(
				'el' => 'Νομικές πληροφορίες',
				'en' => 'Legal',
			),
			'footer.copyright'    => array(
				'el' => 'Copyright © 2026 Ioulia Geraskli',
				'en' => 'Copyright © 2026 Ioulia Geraskli',
			),
		);

		return array_merge( (array) $strings, $dictionary );
	}
	add_filter( 'ioulia_i18n_strings', 'ioulia_i18n_dictionary' );
}
