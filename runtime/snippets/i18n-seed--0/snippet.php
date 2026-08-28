<?php
/**
 * Ioulia i18n seed — the bulk translation of the site, kept in Git.
 *
 * The site is authored in Greek. This file maps each Greek string to its English
 * translation, so /en/ is complete the moment it is deployed and the visual
 * editor is only ever needed for corrections.
 *
 *   'Ελληνικό κείμενο' => 'English text',
 *
 * Matching rules, worth knowing before adding a line by hand:
 *
 *   - The key must be the Greek text exactly as it reads on the page, with the
 *     surrounding whitespace ignored. Runs of spaces and newlines inside it are
 *     collapsed to one space before matching, so indentation in the HTML is
 *     irrelevant.
 *   - Matching is per text run, not per element. A sentence split by an inline
 *     <em> or <br> is two strings, not one.
 *   - The same Greek string anywhere on the site gets the same translation.
 *   - Anything saved in the visual editor overrides the line here, so a fix made
 *     on the live site survives a deploy. Use the editor's "Εξαγωγή για Git"
 *     button to fold those corrections back into this file.
 *
 * Requires the "i18n translate" snippet.
 */

if ( ! function_exists( 'ioulia_i18n_seed_en' ) ) {
	function ioulia_i18n_seed_en( $seed, $lang ) {
		if ( 'en' !== $lang ) {
			return $seed;
		}

		return array_merge(
			(array) $seed,
			array(
				/* Filled in as each template and canvas is converted to Greek. */
			)
		);
	}
	add_filter( 'ioulia_i18n_seed', 'ioulia_i18n_seed_en', 10, 2 );
}
