<?php
/**
 * Customer email language.
 *
 * WooCommerce normally sends customer emails in the store language. This site
 * serves Greek and English from the same WordPress objects, so the order keeps
 * the language in which checkout was completed and every later transactional
 * email follows it, including asynchronous status updates.
 *
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_order_language' ) ) {
	function ioulia_order_language( $order ) {
		if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return 'el';
		}

		return 'en' === $order->get_meta( '_ioulia_language', true ) ? 'en' : 'el';
	}
}

if ( ! function_exists( 'ioulia_store_order_language' ) ) {
	function ioulia_store_order_language( $order ) {
		if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) || $order->get_meta( '_ioulia_language', true ) ) {
			return;
		}

		$lang = function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang() ? 'en' : 'el';
		$order->update_meta_data( '_ioulia_language', $lang );
	}

	add_action( 'woocommerce_checkout_create_order', 'ioulia_store_order_language', 20 );
	add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'ioulia_store_order_language', 20 );
}

if ( ! function_exists( 'ioulia_customer_email_order' ) ) {
	function ioulia_customer_email_order( $email ) {
		if ( ! is_object( $email ) || ! method_exists( $email, 'is_customer_email' ) || ! $email->is_customer_email() ) {
			return null;
		}

		return isset( $email->object ) && is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	}
}

if ( ! function_exists( 'ioulia_customer_email_locale_setup' ) ) {
	function ioulia_customer_email_locale_setup( $allowed, $email ) {
		$order = ioulia_customer_email_order( $email );

		if ( ! $order || 'en' !== ioulia_order_language( $order ) ) {
			return $allowed;
		}

		$key = spl_object_id( $email );
		$GLOBALS['ioulia_email_locale_switched'][ $key ] = function_exists( 'switch_to_locale' ) && switch_to_locale( 'en_US' );

		/* We already selected the order language; do not let WooCommerce replace
		   it with the Greek store locale. */
		return false;
	}

	add_filter( 'woocommerce_allow_switching_email_locale', 'ioulia_customer_email_locale_setup', 20, 2 );
}

if ( ! function_exists( 'ioulia_customer_email_locale_restore' ) ) {
	function ioulia_customer_email_locale_restore( $allowed, $email ) {
		$key = is_object( $email ) ? spl_object_id( $email ) : 0;

		if ( ! isset( $GLOBALS['ioulia_email_locale_switched'][ $key ] ) ) {
			return $allowed;
		}

		if ( $GLOBALS['ioulia_email_locale_switched'][ $key ] ) {
			restore_previous_locale();
		}
		unset( $GLOBALS['ioulia_email_locale_switched'][ $key ] );

		return false;
	}

	add_filter( 'woocommerce_allow_restoring_email_locale', 'ioulia_customer_email_locale_restore', 20, 2 );
}
