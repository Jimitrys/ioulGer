<?php
/**
 * Ioulia i18n core — bilingual site, Greek primary.
 *
 * Greek (el) is the primary language and is served from the site root:  /shop/
 * English (en) is the secondary language and lives behind a prefix:     /en/shop/
 *
 * Both languages are served by the same WordPress objects. The "/en" prefix is
 * stripped just before WordPress parses the request and restored immediately
 * afterwards, so WordPress' own canonical redirect keeps agreeing with the URL
 * the visitor actually requested (this is what prevents redirect loops).
 *
 * Nothing here changes the Greek side of the site: with no prefix in the URL the
 * language is the default one and every filter below returns its input unchanged.
 *
 * Public API used by the other snippets and by canvas HTML:
 *
 *   ioulia_lang()                   'el' | 'en'
 *   ioulia_is_default_lang()        true on Greek
 *   ioulia_t( $key )                translated string for the current language
 *   ioulia_e( $key )                echo it, escaped for HTML
 *   ioulia_attr( $key )             echo it, escaped for an attribute
 *   ioulia_url( $path, $lang )      language-aware URL for a site path
 *   ioulia_alternate_url( $lang )   the current page in the given language
 *   ioulia_language_switcher()      markup for the EL / EN switcher
 *   [t k="nav.home"]                translated string inside canvas HTML
 *
 * Translations themselves live in the separate "i18n strings" snippet.
 */

if ( ! defined( 'IOULIA_LANG_DEFAULT' ) ) {
	define( 'IOULIA_LANG_DEFAULT', 'el' );
}

if ( ! function_exists( 'ioulia_languages' ) ) {
	/**
	 * The languages this site serves, in menu order.
	 *
	 * 'locale'   WordPress locale used for core/WooCommerce translations.
	 * 'hreflang' value written into <link rel="alternate">.
	 * 'label'    short label for the switcher.
	 */
	function ioulia_languages() {
		return array(
			'el' => array(
				'locale'   => 'el',
				'hreflang' => 'el',
				'label'    => 'ΕΛ',
				'name'     => 'Ελληνικά',
			),
			'en' => array(
				'locale'   => 'en_US',
				'hreflang' => 'en',
				'label'    => 'EN',
				'name'     => 'English',
			),
		);
	}
}

if ( ! function_exists( 'ioulia_home_path' ) ) {
	/**
	 * Path WordPress is installed under, always with a trailing slash ('/' at root).
	 * Reads the raw option so it is not affected by our own home_url filter.
	 */
	function ioulia_home_path() {
		static $path = null;

		if ( null === $path ) {
			$parts = wp_parse_url( (string) get_option( 'home' ) );
			$path  = isset( $parts['path'] ) && '' !== $parts['path'] ? trailingslashit( $parts['path'] ) : '/';
		}

		return $path;
	}
}

if ( ! function_exists( 'ioulia_original_request_uri' ) ) {
	/**
	 * REQUEST_URI exactly as it arrived, captured before anything rewrites it.
	 */
	function ioulia_original_request_uri() {
		static $uri = null;

		if ( null === $uri ) {
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		}

		return $uri;
	}
}

if ( ! function_exists( 'ioulia_relative_path' ) ) {
	/**
	 * Turn a URL or URI into a path relative to the WordPress home path,
	 * without leading slash and without query string. '' means the front page.
	 */
	function ioulia_relative_path( $uri ) {
		$parts = wp_parse_url( (string) $uri );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$home  = ioulia_home_path();

		if ( '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}

		return ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'ioulia_lang_from_path' ) ) {
	/**
	 * Read the language out of a home-relative path. Unprefixed paths are Greek.
	 */
	function ioulia_lang_from_path( $path ) {
		$path = ltrim( (string) $path, '/' );

		foreach ( ioulia_languages() as $code => $language ) {
			if ( IOULIA_LANG_DEFAULT === $code ) {
				continue;
			}

			if ( $code === $path || 0 === strpos( $path, $code . '/' ) || 0 === strpos( $path, $code . '?' ) ) {
				return $code;
			}
		}

		return IOULIA_LANG_DEFAULT;
	}
}

if ( ! function_exists( 'ioulia_lang' ) ) {
	/**
	 * Language of the current request. Resolved once, then cached.
	 *
	 * Admin screens always stay in the default language. AJAX requests inherit the
	 * language of the page that fired them, read from the referer, so the mini cart
	 * answers in the same language the visitor is browsing in.
	 */
	function ioulia_lang() {
		static $lang = null;

		if ( null !== $lang ) {
			return $lang;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			$lang = IOULIA_LANG_DEFAULT;

			return $lang;
		}

		if ( wp_doing_ajax() ) {
			$referer = wp_get_referer();
			$lang    = $referer ? ioulia_lang_from_path( ioulia_relative_path( $referer ) ) : IOULIA_LANG_DEFAULT;

			return $lang;
		}

		$lang = ioulia_lang_from_path( ioulia_relative_path( ioulia_original_request_uri() ) );

		return $lang;
	}
}

if ( ! function_exists( 'ioulia_is_default_lang' ) ) {
	function ioulia_is_default_lang() {
		return IOULIA_LANG_DEFAULT === ioulia_lang();
	}
}

/* -------------------------------------------------------------------------
 * Request routing: strip the language prefix around WP::parse_request()
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_strip_prefix_for_parsing' ) ) {
	function ioulia_strip_prefix_for_parsing( $do_parse ) {
		if ( ioulia_is_default_lang() || wp_doing_ajax() ) {
			return $do_parse;
		}

		$original = ioulia_original_request_uri();
		$prefix   = ioulia_home_path() . ioulia_lang();

		if ( 0 === strpos( $original, $prefix ) ) {
			$remainder = substr( $original, strlen( $prefix ) );

			if ( '' === $remainder || '/' === $remainder[0] || '?' === $remainder[0] ) {
				$_SERVER['REQUEST_URI'] = ioulia_home_path() . ltrim( $remainder, '/' );
			}
		}

		if ( isset( $_SERVER['PATH_INFO'] ) ) {
			$path_info = wp_unslash( $_SERVER['PATH_INFO'] );

			if ( 0 === strpos( $path_info, '/' . ioulia_lang() ) ) {
				$_SERVER['PATH_INFO'] = '/' . ltrim( substr( $path_info, strlen( ioulia_lang() ) + 1 ), '/' );
			}
		}

		return $do_parse;
	}
	add_filter( 'do_parse_request', 'ioulia_strip_prefix_for_parsing', 0 );
}

if ( ! function_exists( 'ioulia_restore_request_uri' ) ) {
	/**
	 * Put the prefixed URI back once parsing is done, so redirect_canonical()
	 * compares the real request against the real (prefixed) permalink.
	 */
	function ioulia_restore_request_uri() {
		if ( ioulia_is_default_lang() || wp_doing_ajax() ) {
			return;
		}

		$_SERVER['REQUEST_URI'] = ioulia_original_request_uri();
	}
	add_action( 'parse_request', 'ioulia_restore_request_uri', 0 );
}

/* -------------------------------------------------------------------------
 * Locale: let WordPress and WooCommerce translate themselves
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_switch_locale' ) ) {
	/**
	 * The site locale is Greek, so WooCommerce ships Greek strings for free.
	 * On an English request we switch after every text domain has loaded, which
	 * makes WordPress reload them in en_US.
	 */
	function ioulia_switch_locale() {
		if ( ioulia_is_default_lang() || is_admin() ) {
			return;
		}

		$languages = ioulia_languages();
		$locale    = isset( $languages[ ioulia_lang() ]['locale'] ) ? $languages[ ioulia_lang() ]['locale'] : 'en_US';

		if ( function_exists( 'switch_to_locale' ) && get_locale() !== $locale ) {
			switch_to_locale( $locale );
		}
	}
	add_action( 'init', 'ioulia_switch_locale', 999 );
}

if ( ! function_exists( 'ioulia_language_attributes' ) ) {
	function ioulia_language_attributes( $output ) {
		if ( ioulia_is_default_lang() ) {
			return $output;
		}

		$languages = ioulia_languages();
		$hreflang  = isset( $languages[ ioulia_lang() ]['hreflang'] ) ? $languages[ ioulia_lang() ]['hreflang'] : 'en';

		return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $hreflang ) . '"', $output );
	}
	add_filter( 'language_attributes', 'ioulia_language_attributes' );
}

/* -------------------------------------------------------------------------
 * URLs
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_prefix_path' ) ) {
	/**
	 * Add (or keep) the language prefix on a home-relative path. Idempotent.
	 */
	function ioulia_prefix_path( $path, $lang ) {
		$path = ltrim( (string) $path, '/' );

		if ( IOULIA_LANG_DEFAULT === $lang ) {
			return $path;
		}

		if ( $lang === $path || 0 === strpos( $path, $lang . '/' ) || 0 === strpos( $path, $lang . '?' ) ) {
			return $path;
		}

		return $lang . '/' . $path;
	}
}

if ( ! function_exists( 'ioulia_unprefix_path' ) ) {
	function ioulia_unprefix_path( $path ) {
		$path = ltrim( (string) $path, '/' );
		$lang = ioulia_lang_from_path( $path );

		if ( IOULIA_LANG_DEFAULT === $lang ) {
			return $path;
		}

		return ltrim( substr( $path, strlen( $lang ) ), '/' );
	}
}

if ( ! function_exists( 'ioulia_path_is_translatable' ) ) {
	/**
	 * Never prefix WordPress' own plumbing or direct file URLs.
	 */
	function ioulia_path_is_translatable( $path ) {
		$path = ltrim( (string) $path, '/' );

		if ( '' === $path ) {
			return true;
		}

		foreach ( array( 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login.php', 'wp-cron.php', 'xmlrpc.php' ) as $reserved ) {
			if ( 0 === strpos( $path, $reserved ) ) {
				return false;
			}
		}

		$file = wp_parse_url( $path, PHP_URL_PATH );

		return ! ( is_string( $file ) && preg_match( '/[.][a-z0-9]{2,5}$/i', $file ) );
	}
}

if ( ! function_exists( 'ioulia_filter_home_url' ) ) {
	/**
	 * Every permalink on the site is built on top of home_url(), so prefixing it
	 * here is what makes menus, WooCommerce links, pagination and forms stay in
	 * the language the visitor is browsing.
	 */
	function ioulia_filter_home_url( $url, $path, $scheme ) {
		if ( ioulia_is_default_lang() || 'rest' === $scheme || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $url;
		}

		$relative = ioulia_relative_path( $url );

		if ( ! ioulia_path_is_translatable( $relative ) ) {
			return $url;
		}

		$parts    = wp_parse_url( $url );
		$query    = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';
		$fragment = isset( $parts['fragment'] ) && '' !== $parts['fragment'] ? '#' . $parts['fragment'] : '';
		$prefixed = ioulia_prefix_path( $relative, ioulia_lang() );
		$base     = isset( $parts['scheme'], $parts['host'] ) ? $parts['scheme'] . '://' . $parts['host'] : '';

		if ( isset( $parts['port'] ) ) {
			$base .= ':' . $parts['port'];
		}

		return $base . ioulia_home_path() . $prefixed . $query . $fragment;
	}
	add_filter( 'home_url', 'ioulia_filter_home_url', 10, 3 );
}

if ( ! function_exists( 'ioulia_url' ) ) {
	/**
	 * Language-aware URL for a site path. Use this instead of hardcoding "/shop".
	 */
	function ioulia_url( $path = '', $lang = null ) {
		$lang = $lang ? $lang : ioulia_lang();
		$path = ioulia_prefix_path( ioulia_unprefix_path( $path ), $lang );

		return home_url( '/' . ltrim( $path, '/' ) );
	}
}

if ( ! function_exists( 'ioulia_alternate_url' ) ) {
	/**
	 * The page currently being viewed, in another language. Query strings are
	 * dropped on purpose so hreflang points at the clean URL.
	 */
	function ioulia_alternate_url( $lang ) {
		$path = ioulia_unprefix_path( ioulia_relative_path( ioulia_original_request_uri() ) );
		$path = ioulia_prefix_path( $path, $lang );
		$url  = ioulia_home_path() . $path;

		return set_url_scheme( untrailingslashit( (string) get_option( 'home' ) ) . '/' . ltrim( $url, '/' ) );
	}
}

/* -------------------------------------------------------------------------
 * Strings
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_strings' ) ) {
	/**
	 * The dictionary, keyed by string key then language code. Provided by the
	 * "i18n strings" snippet through the ioulia_i18n_strings filter.
	 */
	function ioulia_strings() {
		static $strings = null;

		if ( null === $strings ) {
			$strings = (array) apply_filters( 'ioulia_i18n_strings', array() );
		}

		return $strings;
	}
}

if ( ! function_exists( 'ioulia_t' ) ) {
	/**
	 * Translated string for the current language.
	 *
	 * Falls back to the default language, then to English, then to the key itself
	 * so a missing translation is visible during authoring instead of blank.
	 */
	function ioulia_t( $key, $lang = null ) {
		$strings = ioulia_strings();
		$lang    = $lang ? $lang : ioulia_lang();

		if ( ! isset( $strings[ $key ] ) ) {
			return $key;
		}

		$entry = (array) $strings[ $key ];

		foreach ( array( $lang, IOULIA_LANG_DEFAULT, 'en' ) as $candidate ) {
			if ( isset( $entry[ $candidate ] ) && '' !== $entry[ $candidate ] ) {
				return $entry[ $candidate ];
			}
		}

		return $key;
	}
}

if ( ! function_exists( 'ioulia_e' ) ) {
	function ioulia_e( $key ) {
		echo esc_html( ioulia_t( $key ) );
	}
}

if ( ! function_exists( 'ioulia_attr' ) ) {
	function ioulia_attr( $key ) {
		echo esc_attr( ioulia_t( $key ) );
	}
}

if ( ! function_exists( 'ioulia_kses' ) ) {
	/**
	 * For strings that intentionally contain inline markup (<br>, <em>, links).
	 */
	function ioulia_kses( $key ) {
		echo wp_kses_post( ioulia_t( $key ) );
	}
}

if ( ! function_exists( 'ioulia_translate_shortcode' ) ) {
	/**
	 * [t k="about.title"] — the way canvas HTML pulls a translated string.
	 * Canvas HTML is passed through do_shortcode() by the Site Studio renderer.
	 */
	function ioulia_translate_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'k' => '', 'raw' => '' ), $atts, 't' );

		if ( '' === $atts['k'] ) {
			return '';
		}

		$value = ioulia_t( $atts['k'] );

		return 'yes' === $atts['raw'] ? $value : wp_kses_post( $value );
	}

	if ( ! shortcode_exists( 't' ) ) {
		add_shortcode( 't', 'ioulia_translate_shortcode' );
	}
	add_shortcode( 'ioulia_t', 'ioulia_translate_shortcode' );
}

/* -------------------------------------------------------------------------
 * Language switcher
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_language_switcher' ) ) {
	function ioulia_language_switcher() {
		$current = ioulia_lang();
		$links   = array();

		foreach ( ioulia_languages() as $code => $language ) {
			$links[] = sprintf(
				'<a class="ioulia-lang-link%1$s" href="%2$s" hreflang="%3$s" lang="%3$s"%4$s>%5$s</a>',
				$code === $current ? ' is-current' : '',
				esc_url( ioulia_alternate_url( $code ) ),
				esc_attr( $language['hreflang'] ),
				$code === $current ? ' aria-current="true"' : '',
				esc_html( $language['label'] )
			);
		}

		return '<div class="ioulia-lang-switcher">' . implode( '<span class="ioulia-lang-sep" aria-hidden="true">/</span>', $links ) . '</div>';
	}
	add_shortcode( 'ioulia_language_switcher', 'ioulia_language_switcher' );
}

/* -------------------------------------------------------------------------
 * SEO: hreflang, canonical, Open Graph locale
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_head_alternates' ) ) {
	/**
	 * Both language versions are indexable and point at each other. Greek is
	 * x-default because it is the primary language of the site.
	 */
	function ioulia_head_alternates() {
		if ( is_admin() || is_404() || is_search() ) {
			return;
		}

		$output = '';

		foreach ( ioulia_languages() as $code => $language ) {
			$output .= sprintf(
				'<link rel="alternate" hreflang="%1$s" href="%2$s" />' . chr( 10 ),
				esc_attr( $language['hreflang'] ),
				esc_url( ioulia_alternate_url( $code ) )
			);
		}

		$output .= sprintf(
			'<link rel="alternate" hreflang="x-default" href="%s" />' . chr( 10 ),
			esc_url( ioulia_alternate_url( IOULIA_LANG_DEFAULT ) )
		);

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_url/esc_attr above.
	}
	add_action( 'wp_head', 'ioulia_head_alternates', 1 );
}

if ( ! function_exists( 'ioulia_filter_canonical' ) ) {
	/**
	 * SEOPress builds its canonical from the permalink, which already carries the
	 * prefix through the home_url filter. This is a belt-and-braces pass that also
	 * covers canonicals stored by hand. ioulia_prefix_path() is idempotent.
	 */
	function ioulia_filter_canonical( $canonical ) {
		if ( ioulia_is_default_lang() || ! is_string( $canonical ) || '' === $canonical ) {
			return $canonical;
		}

		$parts = wp_parse_url( $canonical );

		if ( ! isset( $parts['host'] ) ) {
			return $canonical;
		}

		$relative = ioulia_relative_path( $canonical );

		if ( ! ioulia_path_is_translatable( $relative ) ) {
			return $canonical;
		}

		$query = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';

		return $parts['scheme'] . '://' . $parts['host'] . ioulia_home_path() . ioulia_prefix_path( $relative, ioulia_lang() ) . $query;
	}
	add_filter( 'seopress_titles_canonical', 'ioulia_filter_canonical' );
	add_filter( 'get_canonical_url', 'ioulia_filter_canonical' );
}

if ( ! function_exists( 'ioulia_filter_og_locale' ) ) {
	function ioulia_filter_og_locale( $locale ) {
		$languages = ioulia_languages();
		$current   = ioulia_lang();

		return isset( $languages[ $current ]['locale'] ) ? $languages[ $current ]['locale'] : $locale;
	}
	add_filter( 'seopress_social_og_locale', 'ioulia_filter_og_locale' );
}

if ( ! function_exists( 'ioulia_body_language_class' ) ) {
	function ioulia_body_language_class( $classes ) {
		$classes[] = 'ioulia-lang-' . sanitize_html_class( ioulia_lang() );

		return $classes;
	}
	add_filter( 'body_class', 'ioulia_body_language_class' );
}
