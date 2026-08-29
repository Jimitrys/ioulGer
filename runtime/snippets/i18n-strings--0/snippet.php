<?php
/**
 * Ioulia i18n strings — the keyed dictionary, for strings the automatic pass
 * cannot handle on its own.
 *
 * Most of the site does not need this file. Copy is written in Greek directly in
 * the templates and canvases, and the "i18n seed" snippet holds its English, so a
 * string is translated by its own wording. Use a key here only when that model
 * breaks down:
 *
 *   - the same Greek word must become different English words depending on where
 *     it sits (a headline split across lines whose word order differs between the
 *     two languages is the usual case);
 *   - the string is somewhere the text pass cannot reach: inside <script>, in a
 *     <title>, or built inside JavaScript;
 *   - the two languages need genuinely different content, not a translation.
 *
 * Usage:
 *
 *   in a PHP snippet   <?php ioulia_e( 'about.title_1' ); ?>
 *   in canvas HTML     [t k="about.title_1"]
 *
 * A missing 'en' entry falls back to the Greek one rather than rendering blank.
 *
 * Strings that belong to WordPress or WooCommerce itself are NOT listed here —
 * those come from the official language packs and follow the request locale.
 */

if ( ! function_exists( 'ioulia_i18n_dictionary' ) ) {
	function ioulia_i18n_dictionary( $strings ) {
		$dictionary = array(

			/* ---------------------------------------------------------------
			 * About — the stacked title. Greek and English put the words in a
			 * different order, so each line is keyed by its position instead of
			 * by its wording.
			 *
			 *   Το / Εργαστήριο / Κεραμικής   ->   The / Ceramic / Lab
			 * ------------------------------------------------------------ */
			'about.title_1' => array(
				'el' => 'Το',
				'en' => 'The',
			),
			'about.title_2' => array(
				'el' => 'Εργαστήριο',
				'en' => 'Ceramic',
			),
			'about.title_3' => array(
				'el' => 'Κεραμικής',
				'en' => 'Lab',
			),
		);

		return array_merge( (array) $strings, $dictionary );
	}
	add_filter( 'ioulia_i18n_strings', 'ioulia_i18n_dictionary' );
}
