<?php
defined( 'ABSPATH' ) || exit;

/**
 * The pass that turns a rendered page into another language.
 *
 * It runs only on a secondary-language request, so the site's own language —
 * what nearly every visitor sees — never enters it at all.
 *
 * The page is split into tag tokens and text tokens and glued back together,
 * rather than parsed into a document and re-serialised. Re-serialising
 * hand-written HTML5 and inline SVG through DOMDocument mangles self-closing
 * tags, entities and namespaces; splitting returns the page byte for byte apart
 * from the words that actually changed.
 *
 * Raw-text elements are matched whole rather than counted open and closed. Their
 * content is not markup: a comparison like "i < n" inside a script reads as the
 * start of a tag, and a generic tag pattern then runs past the real closing tag
 * looking for its angle bracket. That swallows the close, leaves the counter
 * stuck open, and silently drops every remaining text run on the page.
 */
final class IGC_I18N_Translator {

	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'start' ), 0 );
	}

	/**
	 * Elements whose text is code rather than copy, and which parse normally.
	 */
	private static function skip_tags(): array {
		return array( 'svg', 'canvas', 'code', 'pre' );
	}

	/**
	 * Attributes holding copy a visitor can read. "value" is deliberately absent:
	 * translating a form value would change what gets submitted.
	 */
	private static function attributes(): array {
		return array( 'aria-label', 'alt', 'placeholder', 'title', 'aria-placeholder' );
	}

	public static function start(): void {
		if ( ! IGC_I18N::is_enabled() || IGC_I18N::is_default() || is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		ob_start( array( self::class, 'translate' ) );
	}

	public static function translate( $html ): string {
		$html     = (string) $html;
		$language = IGC_I18N::language();
		$map      = IGC_I18N_Store::map( $language );

		if ( ! empty( $map ) ) {
			$html = self::walk(
				$html,
				static function ( string $text ) use ( $map ): string {
					if ( '' === trim( $text ) ) {
						return $text;
					}

					if ( ! preg_match( '/^(\s*)(.*?)(\s*)$/su', $text, $matches ) ) {
						return $text;
					}

					$hash = IGC_I18N_Store::key( $matches[2] );

					return isset( $map[ $hash ] ) ? $matches[1] . $map[ $hash ] . $matches[3] : $text;
				}
			);

			$attributes = implode( '|', array_map( 'preg_quote', self::attributes() ) );

			$html = (string) preg_replace_callback(
				'/(\s(?:' . $attributes . ')\s*=\s*")([^"]+)(")/i',
				static function ( array $matches ) use ( $map ): string {
					$hash = IGC_I18N_Store::key( html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' ) );

					return isset( $map[ $hash ] )
						? $matches[1] . esc_attr( $map[ $hash ] ) . $matches[3]
						: $matches[0];
				},
				$html
			);
		}

		// Links need the prefix whether or not any wording has been translated.
		return self::localise_links( $html, $language );
	}

	/**
	 * Split HTML into tags, comments and text; hand every text run to $callback;
	 * glue the result back together unchanged everywhere else.
	 */
	public static function walk( string $html, callable $callback ): string {
		$raw = '<script[^>]*>.*?</script\s*>'
			. '|<style[^>]*>.*?</style\s*>'
			. '|<textarea[^>]*>.*?</textarea\s*>'
			. '|<noscript[^>]*>.*?</noscript\s*>';

		$parts = preg_split( '#((?:' . $raw . ')|<!--.*?-->|<[^>]*>)#si', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( ! is_array( $parts ) ) {
			return $html;
		}

		$skip   = self::skip_tags();
		$depth  = 0;
		$output = '';

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			if ( '<' === $part[0] ) {
				if ( preg_match( '#^</\s*([a-z0-9:_-]+)#i', $part, $matches ) ) {
					if ( in_array( strtolower( $matches[1] ), $skip, true ) && $depth > 0 ) {
						$depth--;
					}
				} elseif ( preg_match( '#^<\s*([a-z0-9:_-]+)#i', $part, $matches ) ) {
					if ( in_array( strtolower( $matches[1] ), $skip, true ) && ! str_ends_with( rtrim( $part ), '/>' ) ) {
						$depth++;
					}
				}

				$output .= $part;
				continue;
			}

			$output .= $depth > 0 ? $part : (string) $callback( $part );
		}

		return $output;
	}

	/**
	 * Carry the language across root-relative links.
	 *
	 * Anything built through home_url() already keeps the prefix, but a link
	 * typed straight into a template as href="/shop" never goes near it, and a
	 * visitor reading the secondary language is dropped back into the default one
	 * the moment they use the menu. Rewriting the rendered markup covers every
	 * such link at once, including any added later.
	 *
	 * Only root-relative paths are touched, and prefixing is idempotent.
	 * Protocol-relative, absolute and anchor links are left alone, as is anything
	 * the path check calls untranslatable.
	 */
	public static function localise_links( string $html, string $language ): string {
		return (string) preg_replace_callback(
			'#(\s(?:href|action)=")(/[^"/][^"]*|/)(")#i',
			static function ( array $matches ) use ( $language ): string {
				$path = ltrim( $matches[2], '/' );

				if ( ! IGC_I18N::is_translatable_path( $path ) ) {
					return $matches[0];
				}

				return $matches[1] . '/' . IGC_I18N::prefix_path( $path, $language ) . $matches[3];
			},
			$html
		);
	}
}
