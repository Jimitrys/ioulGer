<?php
/**
 * Ioulia i18n translate — the runtime that swaps Greek source text for its
 * stored translation, and the store the visual editor writes into.
 *
 * How it works
 * ------------
 * Pages are authored in Greek. On a secondary-language request (/en/...) the
 * rendered HTML is passed through one pass that replaces each Greek text node
 * with its saved translation. Nothing is stored per page: the key of a string is
 * a hash of the Greek text itself, so the same wording translated once is
 * translated everywhere it appears.
 *
 * Why not DOMDocument
 * -------------------
 * The site is full of inline SVG and hand-written HTML5. Re-serialising it
 * through DOMDocument mangles self-closing tags, entities and namespaces. Instead
 * the HTML is split into tag tokens and text tokens; only text tokens are
 * touched and everything is concatenated back, so the output is byte-identical
 * apart from the words that actually changed.
 *
 * Cost
 * ----
 * This runs only when the request is not in the default language, so the Greek
 * site — the one nearly every visitor sees — is completely untouched. With an
 * empty store the pass returns immediately.
 *
 * Requires the "i18n core" snippet.
 */

if ( ! defined( 'IOULIA_I18N_OPTION' ) ) {
	define( 'IOULIA_I18N_OPTION', 'ioulia_i18n_translations' );
}

if ( ! defined( 'IOULIA_I18N_MAX_LENGTH' ) ) {
	define( 'IOULIA_I18N_MAX_LENGTH', 20000 );
}

/* -------------------------------------------------------------------------
 * The store
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_normalize_source' ) ) {
	/**
	 * Two pieces of text that differ only in surrounding or repeated whitespace
	 * are the same string as far as a translator is concerned.
	 */
	function ioulia_normalize_source( $text ) {
		$text = preg_replace( '/[[:space:]]+/u', ' ', (string) $text );

		return trim( (string) $text );
	}
}

if ( ! function_exists( 'ioulia_source_key' ) ) {
	function ioulia_source_key( $text ) {
		return md5( ioulia_normalize_source( $text ) );
	}
}

if ( ! function_exists( 'ioulia_translation_store' ) ) {
	/**
	 * Whole store: language code => source hash => entry.
	 * An entry is array( 'source' => Greek, 'text' => translation, 'updated' => gmt ).
	 */
	function ioulia_translation_store( $refresh = false ) {
		static $store = null;

		if ( null === $store || $refresh ) {
			$store = (array) get_option( IOULIA_I18N_OPTION, array() );
		}

		return $store;
	}
}

if ( ! function_exists( 'ioulia_translation_map' ) ) {
	/**
	 * Flat hash => translation map for one language, with empty entries dropped
	 * so an untranslated string falls through to the Greek original.
	 *
	 * Two layers, in this order:
	 *
	 *   1. the seed shipped in the repository ("i18n seed" snippet), which is the
	 *      bulk translation of the site and is version controlled;
	 *   2. anything saved from the visual editor, which wins, so a correction made
	 *      on the live site is never undone by a deploy.
	 *
	 * The editor's export turns layer 2 back into layer 1 when you want a fix to
	 * become permanent in Git.
	 */
	function ioulia_translation_map( $lang ) {
		static $maps = array();

		if ( isset( $maps[ $lang ] ) ) {
			return $maps[ $lang ];
		}

		$store = ioulia_translation_store();
		$map   = array();

		foreach ( (array) apply_filters( 'ioulia_i18n_seed', array(), $lang ) as $source => $translation ) {
			if ( '' !== trim( (string) $translation ) ) {
				$map[ md5( ioulia_normalize_source( $source ) ) ] = (string) $translation;
			}
		}

		if ( isset( $store[ $lang ] ) && is_array( $store[ $lang ] ) ) {
			foreach ( $store[ $lang ] as $hash => $entry ) {
				if ( is_array( $entry ) && isset( $entry['text'] ) && '' !== trim( (string) $entry['text'] ) ) {
					$map[ $hash ] = (string) $entry['text'];
				}
			}
		}

		$maps[ $lang ] = $map;

		return $map;
	}
}

if ( ! function_exists( 'ioulia_lookup_translation' ) ) {
	/**
	 * Current translation of one Greek string, or '' when it has none.
	 */
	function ioulia_lookup_translation( $lang, $source ) {
		$map  = ioulia_translation_map( $lang );
		$hash = md5( ioulia_normalize_source( $source ) );

		return isset( $map[ $hash ] ) ? $map[ $hash ] : '';
	}
}

if ( ! function_exists( 'ioulia_save_translation' ) ) {
	/**
	 * Store one translation. An empty translation removes the entry, which is how
	 * the editor's "reset" works.
	 */
	function ioulia_save_translation( $lang, $source, $translation ) {
		$languages = ioulia_languages();

		if ( ! isset( $languages[ $lang ] ) || IOULIA_LANG_DEFAULT === $lang ) {
			return new WP_Error( 'ioulia_bad_language', 'Unknown target language.' );
		}

		$source = ioulia_normalize_source( $source );

		if ( '' === $source || strlen( $source ) > IOULIA_I18N_MAX_LENGTH ) {
			return new WP_Error( 'ioulia_bad_source', 'The source string is empty or too long.' );
		}

		$translation = trim( (string) $translation );

		if ( strlen( $translation ) > IOULIA_I18N_MAX_LENGTH ) {
			return new WP_Error( 'ioulia_bad_translation', 'The translation is too long.' );
		}

		$store = ioulia_translation_store( true );
		$hash  = md5( $source );

		if ( ! isset( $store[ $lang ] ) || ! is_array( $store[ $lang ] ) ) {
			$store[ $lang ] = array();
		}

		if ( '' === $translation ) {
			unset( $store[ $lang ][ $hash ] );
		} else {
			$store[ $lang ][ $hash ] = array(
				'source'  => $source,
				'text'    => wp_kses_post( $translation ),
				'updated' => current_time( 'mysql', true ),
			);
		}

		update_option( IOULIA_I18N_OPTION, $store, false );
		ioulia_translation_store( true );

		return isset( $store[ $lang ][ $hash ] ) ? $store[ $lang ][ $hash ] : array(
			'source'  => $source,
			'text'    => '',
			'updated' => '',
		);
	}
}

/* -------------------------------------------------------------------------
 * HTML walking
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_html_skip_tags' ) ) {
	/**
	 * Elements whose text is code, not copy.
	 */
	function ioulia_html_skip_tags() {
		return array( 'script', 'style', 'svg', 'canvas', 'textarea', 'code', 'pre', 'noscript' );
	}
}

if ( ! function_exists( 'ioulia_walk_html' ) ) {
	/**
	 * Split HTML into tags, comments and text, hand every text run to $callback,
	 * and glue the result back together unchanged everywhere else.
	 */
	function ioulia_walk_html( $html, $callback ) {
		$parts = preg_split( '/(<!--.*?-->|<[^>]*>)/s', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( ! is_array( $parts ) ) {
			return $html;
		}

		$skip_tags = ioulia_html_skip_tags();
		$depth     = 0;
		$output    = '';

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			if ( '<' === $part[0] ) {
				if ( preg_match( '#^</[[:space:]]*([a-z0-9:_-]+)#i', $part, $matches ) ) {
					if ( in_array( strtolower( $matches[1] ), $skip_tags, true ) && $depth > 0 ) {
						$depth--;
					}
				} elseif ( preg_match( '#^<[[:space:]]*([a-z0-9:_-]+)#i', $part, $matches ) ) {
					if ( in_array( strtolower( $matches[1] ), $skip_tags, true ) && '/>' !== substr( rtrim( $part ), -2 ) ) {
						$depth++;
					}
				}

				$output .= $part;
				continue;
			}

			$output .= $depth > 0 ? $part : call_user_func( $callback, $part );
		}

		return $output;
	}
}

if ( ! function_exists( 'ioulia_translatable_attributes' ) ) {
	/**
	 * Attributes that hold copy a visitor can read. "value" is deliberately absent:
	 * translating a form value would change what gets submitted.
	 */
	function ioulia_translatable_attributes() {
		return array( 'aria-label', 'alt', 'placeholder', 'title', 'aria-placeholder' );
	}
}

/* -------------------------------------------------------------------------
 * The translation pass
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_translate_html' ) ) {
	function ioulia_translate_html( $html ) {
		$map = ioulia_translation_map( ioulia_lang() );

		if ( empty( $map ) ) {
			return $html;
		}

		$translate = static function ( $text ) use ( $map ) {
			if ( '' === trim( $text ) ) {
				return $text;
			}

			if ( ! preg_match( '/^([[:space:]]*)(.*?)([[:space:]]*)$/su', $text, $matches ) ) {
				return $text;
			}

			$hash = md5( ioulia_normalize_source( $matches[2] ) );

			return isset( $map[ $hash ] ) ? $matches[1] . $map[ $hash ] . $matches[3] : $text;
		};

		$attributes = implode( '|', array_map( 'preg_quote', ioulia_translatable_attributes() ) );

		$html = ioulia_walk_html(
			$html,
			$translate
		);

		return (string) preg_replace_callback(
			'/([[:space:]](?:' . $attributes . ')[[:space:]]*=[[:space:]]*")([^"]+)(")/i',
			static function ( $matches ) use ( $map ) {
				$hash = md5( ioulia_normalize_source( html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' ) ) );

				return isset( $map[ $hash ] )
					? $matches[1] . esc_attr( $map[ $hash ] ) . $matches[3]
					: $matches[0];
			},
			$html
		);
	}
}

if ( ! function_exists( 'ioulia_start_translation_buffer' ) ) {
	function ioulia_start_translation_buffer() {
		if ( ioulia_is_default_lang() || is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		ob_start( 'ioulia_translate_html' );
	}
	add_action( 'template_redirect', 'ioulia_start_translation_buffer', 0 );
}
