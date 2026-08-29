<?php
defined( 'ABSPATH' ) || exit;

/**
 * The dictionary.
 *
 * A translation is keyed by a hash of the source text itself, not by where it
 * appears. Translate a phrase once and it is translated everywhere it occurs,
 * which is what makes a site of this size tractable: the header, the footer and
 * the buttons are the same handful of strings on every page.
 *
 * The consequence is worth stating plainly: a word that must read differently in
 * two places cannot be handled here. Those belong in the code, as two different
 * source strings.
 */
final class IGC_I18N_Store {

	private const OPTION = 'igc_i18n_strings';

	private static ?array $cache = null;

	/**
	 * Runs of whitespace are meaningless in HTML, so two strings that differ only
	 * in indentation are the same string to a translator.
	 */
	public static function normalize( string $text ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	public static function key( string $text ): string {
		return md5( self::normalize( $text ) );
	}

	public static function all( bool $refresh = false ): array {
		if ( null === self::$cache || $refresh ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = is_array( $stored ) ? $stored : array();
		}

		return self::$cache;
	}

	/**
	 * Flat hash to translation map for one language. Empty translations are
	 * dropped so an untranslated string falls through to its source rather than
	 * rendering blank.
	 */
	public static function map( string $language ): array {
		static $maps = array();

		if ( isset( $maps[ $language ] ) ) {
			return $maps[ $language ];
		}

		$map = array();

		foreach ( self::all()[ $language ] ?? array() as $hash => $entry ) {
			if ( is_array( $entry ) && '' !== trim( (string) ( $entry['text'] ?? '' ) ) ) {
				$map[ $hash ] = (string) $entry['text'];
			}
		}

		$maps[ $language ] = $map;

		return $map;
	}

	public static function lookup( string $language, string $source ): string {
		return self::map( $language )[ self::key( $source ) ] ?? '';
	}

	/**
	 * Store one translation. An empty translation removes the entry, which is how
	 * the editor's reset works.
	 */
	public static function save( string $language, string $source, string $translation ): bool|WP_Error {
		$languages = IGC_I18N::languages();

		if ( ! isset( $languages[ $language ] ) || $language === IGC_I18N::default_language() ) {
			return new WP_Error( 'igc_i18n_language', __( 'Unknown target language.', 'igc-builder' ) );
		}

		$source = self::normalize( $source );

		if ( '' === $source || strlen( $source ) > 20000 ) {
			return new WP_Error( 'igc_i18n_source', __( 'The source string is empty or too long.', 'igc-builder' ) );
		}

		$translation = trim( $translation );

		if ( strlen( $translation ) > 20000 ) {
			return new WP_Error( 'igc_i18n_translation', __( 'The translation is too long.', 'igc-builder' ) );
		}

		$strings = self::all( true );
		$hash    = md5( $source );

		if ( ! isset( $strings[ $language ] ) || ! is_array( $strings[ $language ] ) ) {
			$strings[ $language ] = array();
		}

		if ( '' === $translation ) {
			unset( $strings[ $language ][ $hash ] );
		} else {
			$strings[ $language ][ $hash ] = array(
				'source'  => $source,
				'text'    => wp_kses_post( $translation ),
				'updated' => current_time( 'mysql', true ),
			);
		}

		update_option( self::OPTION, $strings, false );
		self::$cache = $strings;

		return true;
	}

	/**
	 * Everything stored for one language, source text to translation, sorted so
	 * an export is stable enough to diff.
	 */
	public static function export( string $language ): array {
		$out = array();

		foreach ( self::all()[ $language ] ?? array() as $entry ) {
			if ( is_array( $entry ) && isset( $entry['source'], $entry['text'] ) ) {
				$out[ (string) $entry['source'] ] = (string) $entry['text'];
			}
		}

		ksort( $out );

		return $out;
	}

	/**
	 * Merge a source-to-translation map in. Existing entries are overwritten only
	 * when the import carries something for them, so a partial file cannot wipe
	 * work that is not in it.
	 */
	public static function import( string $language, array $pairs ): int {
		$strings = self::all( true );
		$count   = 0;

		if ( ! isset( $strings[ $language ] ) || ! is_array( $strings[ $language ] ) ) {
			$strings[ $language ] = array();
		}

		foreach ( $pairs as $source => $translation ) {
			$source      = self::normalize( (string) $source );
			$translation = trim( (string) $translation );

			if ( '' === $source || '' === $translation ) {
				continue;
			}

			$strings[ $language ][ md5( $source ) ] = array(
				'source'  => $source,
				'text'    => wp_kses_post( $translation ),
				'updated' => current_time( 'mysql', true ),
			);
			$count++;
		}

		update_option( self::OPTION, $strings, false );
		self::$cache = $strings;

		return $count;
	}

	public static function count( string $language ): int {
		return count( self::all()[ $language ] ?? array() );
	}
}
