<?php
defined( 'ABSPATH' ) || exit;

/**
 * Multilingual routing, link persistence and search-engine signals.
 *
 * One language is the site's own and lives at the root. Every other language
 * lives behind a prefix and is served by the same WordPress objects: there is no
 * duplicate page, no duplicate product, and nothing to keep in step. What
 * differs between the two is the words, and those come from IGC_I18N_Store by
 * way of IGC_I18N_Translator.
 *
 * The prefix is stripped just before WordPress parses the request and put back
 * immediately afterwards, so WordPress' own canonical redirect keeps agreeing
 * with the URL the visitor asked for. Restoring it is what stops the redirect
 * loop that this approach is otherwise prone to.
 */
final class IGC_I18N {

	private const OPTION = 'igc_i18n_settings';

	private static ?string $language = null;
	private static ?string $request = null;

	public static function init(): void {
		add_filter( 'do_parse_request', array( self::class, 'strip_prefix' ), 0 );
		add_action( 'parse_request', array( self::class, 'restore_request' ), 0 );
		add_action( 'init', array( self::class, 'switch_locale' ), 999 );

		add_filter( 'home_url', array( self::class, 'filter_home_url' ), 10, 3 );
		add_filter( 'language_attributes', array( self::class, 'language_attributes' ) );
		add_filter( 'body_class', array( self::class, 'body_class' ) );

		add_action( 'wp_head', array( self::class, 'head_alternates' ), 1 );
		add_filter( 'seopress_titles_canonical', array( self::class, 'canonical' ) );
		add_filter( 'get_canonical_url', array( self::class, 'canonical' ) );
		add_filter( 'seopress_social_og_locale', array( self::class, 'og_locale' ) );
		add_action( 'init', array( self::class, 'register_sitemaps' ), 20 );
		add_action( 'template_redirect', array( self::class, 'redirect_legacy_sitemap' ), 0 );
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	public static function settings(): array {
		$stored = get_option( self::OPTION, array() );

		return wp_parse_args(
			is_array( $stored ) ? $stored : array(),
			array(
				'enabled'   => false,
				'default'   => 'el',
				'languages' => array(
					array( 'code' => 'el', 'label' => 'ΕΛ', 'name' => 'Ελληνικά', 'locale' => 'el' ),
					array( 'code' => 'en', 'label' => 'EN', 'name' => 'English', 'locale' => 'en_US' ),
				),
			)
		);
	}

	public static function save_settings( array $settings ): void {
		update_option( self::OPTION, $settings, false );
	}

	public static function is_enabled(): bool {
		return (bool) self::settings()['enabled'];
	}

	/**
	 * Languages keyed by code, the default one first.
	 */
	public static function languages(): array {
		$settings = self::settings();
		$out      = array();

		foreach ( (array) $settings['languages'] as $language ) {
			if ( empty( $language['code'] ) ) {
				continue;
			}

			$code         = sanitize_key( $language['code'] );
			$out[ $code ] = array(
				'code'   => $code,
				'label'  => (string) ( $language['label'] ?? strtoupper( $code ) ),
				'name'   => (string) ( $language['name'] ?? $code ),
				'locale' => (string) ( $language['locale'] ?? $code ),
			);
		}

		return $out;
	}

	public static function default_language(): string {
		$settings  = self::settings();
		$languages = self::languages();
		$default   = sanitize_key( (string) $settings['default'] );

		return isset( $languages[ $default ] ) ? $default : (string) array_key_first( $languages );
	}

	public static function secondary_languages(): array {
		$languages = self::languages();
		unset( $languages[ self::default_language() ] );

		return $languages;
	}

	/* ---------------------------------------------------------------------
	 * The current language
	 * ------------------------------------------------------------------ */

	/**
	 * Path WordPress is installed under, with a trailing slash. Read from the raw
	 * option so it is not affected by our own home_url filter.
	 */
	public static function home_path(): string {
		static $path = null;

		if ( null === $path ) {
			$parts = wp_parse_url( (string) get_option( 'home' ) );
			$path  = isset( $parts['path'] ) && '' !== $parts['path'] ? trailingslashit( $parts['path'] ) : '/';
		}

		return $path;
	}

	/**
	 * REQUEST_URI as it arrived, captured before anything rewrites it.
	 */
	public static function request_uri(): string {
		if ( null === self::$request ) {
			self::$request = isset( $_SERVER['REQUEST_URI'] )
				? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
				: '/';
		}

		return self::$request;
	}

	/**
	 * A URL or URI as a path relative to the home path, without leading slash or
	 * query string. An empty string is the front page.
	 */
	public static function relative_path( string $uri ): string {
		$parts = wp_parse_url( $uri );
		$path  = $parts['path'] ?? '/';
		$home  = self::home_path();

		if ( '/' !== $home && str_starts_with( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}

		return ltrim( $path, '/' );
	}

	public static function language_from_path( string $path ): string {
		$path = ltrim( $path, '/' );

		foreach ( array_keys( self::secondary_languages() ) as $code ) {
			if ( $code === $path || str_starts_with( $path, $code . '/' ) || str_starts_with( $path, $code . '?' ) ) {
				return $code;
			}
		}

		return self::default_language();
	}

	/**
	 * Language of this request, resolved once.
	 *
	 * Admin screens stay in the default language. An AJAX call inherits the
	 * language of the page that fired it, read from the referer, so a mini cart
	 * answers in the language the visitor is reading.
	 */
	public static function language(): string {
		if ( null !== self::$language ) {
			return self::$language;
		}

		if ( ! self::is_enabled() ) {
			self::$language = self::default_language();

			return self::$language;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			self::$language = self::default_language();

			return self::$language;
		}

		if ( wp_doing_ajax() ) {
			// wp_get_referer() validates against home_url(), whose filter calls
			// language() again before the language has been resolved.
			$referer        = wp_get_raw_referer();
			self::$language = $referer
				? self::language_from_path( self::relative_path( $referer ) )
				: self::default_language();

			return self::$language;
		}

		self::$language = self::language_from_path( self::relative_path( self::request_uri() ) );

		return self::$language;
	}

	public static function is_default(): bool {
		return self::language() === self::default_language();
	}

	/* ---------------------------------------------------------------------
	 * Routing
	 * ------------------------------------------------------------------ */

	public static function strip_prefix( $do_parse ) {
		if ( ! self::is_enabled() || self::is_default() || wp_doing_ajax() ) {
			return $do_parse;
		}

		$original = self::request_uri();
		$prefix   = self::home_path() . self::language();

		if ( str_starts_with( $original, $prefix ) ) {
			$remainder = substr( $original, strlen( $prefix ) );

			if ( '' === $remainder || '/' === $remainder[0] || '?' === $remainder[0] ) {
				$_SERVER['REQUEST_URI'] = self::home_path() . ltrim( $remainder, '/' );
			}
		}

		if ( isset( $_SERVER['PATH_INFO'] ) ) {
			$info = wp_unslash( $_SERVER['PATH_INFO'] );

			if ( str_starts_with( $info, '/' . self::language() ) ) {
				$_SERVER['PATH_INFO'] = '/' . ltrim( substr( $info, strlen( self::language() ) + 1 ), '/' );
			}
		}

		return $do_parse;
	}

	/**
	 * Put the prefixed URI back once parsing is done, so redirect_canonical()
	 * compares the real request against the real permalink.
	 */
	public static function restore_request(): void {
		if ( ! self::is_enabled() || self::is_default() || wp_doing_ajax() ) {
			return;
		}

		$_SERVER['REQUEST_URI'] = self::request_uri();
	}

	/**
	 * The site locale belongs to the default language, so WordPress and
	 * WooCommerce translate themselves from their own language packs. On a
	 * secondary-language request we switch after every text domain has loaded,
	 * which makes WordPress reload them.
	 */
	public static function switch_locale(): void {
		if ( ! self::is_enabled() || self::is_default() || is_admin() ) {
			return;
		}

		$languages = self::languages();
		$locale    = $languages[ self::language() ]['locale'] ?? 'en_US';

		if ( function_exists( 'switch_to_locale' ) && get_locale() !== $locale ) {
			switch_to_locale( $locale );
		}
	}

	/* ---------------------------------------------------------------------
	 * URLs
	 * ------------------------------------------------------------------ */

	public static function prefix_path( string $path, string $language ): string {
		$path = ltrim( $path, '/' );

		if ( $language === self::default_language() ) {
			return $path;
		}

		if ( $language === $path || str_starts_with( $path, $language . '/' ) || str_starts_with( $path, $language . '?' ) ) {
			return $path;
		}

		return $language . '/' . $path;
	}

	public static function unprefix_path( string $path ): string {
		$path     = ltrim( $path, '/' );
		$language = self::language_from_path( $path );

		if ( $language === self::default_language() ) {
			return $path;
		}

		return ltrim( substr( $path, strlen( $language ) ), '/' );
	}

	/**
	 * Whether a path has a translated counterpart at all. WordPress' own
	 * plumbing and anything that looks like a file never do.
	 */
	public static function is_translatable_path( string $path ): bool {
		$path = ltrim( $path, '/' );

		if ( '' === $path ) {
			return true;
		}

		foreach ( array( 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login.php', 'wp-cron.php', 'xmlrpc.php', 'feed' ) as $reserved ) {
			if ( str_starts_with( $path, $reserved ) ) {
				return false;
			}
		}

		$file = wp_parse_url( $path, PHP_URL_PATH );
		$plain = ! ( is_string( $file ) && preg_match( '/\.[a-z0-9]{2,5}$/i', $file ) );

		/**
		 * Lets a feature exclude its own paths — an internal tool that should have
		 * no counterpart in another language, for instance.
		 */
		return (bool) apply_filters( 'igc_i18n_translatable_path', $plain, $path );
	}

	/**
	 * Every permalink on the site is built on home_url(), so prefixing it here is
	 * what keeps menus, WooCommerce links, pagination and form actions in the
	 * language the visitor is reading.
	 */
	public static function filter_home_url( $url, $path, $scheme ) {
		if ( ! self::is_enabled() || self::is_default() || 'rest' === $scheme || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $url;
		}

		$relative = self::relative_path( (string) $url );

		if ( ! self::is_translatable_path( $relative ) ) {
			return $url;
		}

		$parts    = wp_parse_url( (string) $url );
		$query    = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';
		$fragment = isset( $parts['fragment'] ) && '' !== $parts['fragment'] ? '#' . $parts['fragment'] : '';
		$base     = isset( $parts['scheme'], $parts['host'] ) ? $parts['scheme'] . '://' . $parts['host'] : '';

		if ( isset( $parts['port'] ) ) {
			$base .= ':' . $parts['port'];
		}

		return $base . self::home_path() . self::prefix_path( $relative, self::language() ) . $query . $fragment;
	}

	/**
	 * The page being viewed, in another language. Query strings are dropped so
	 * hreflang points at the clean URL.
	 */
	public static function alternate_url( string $language ): string {
		$path = self::prefix_path( self::unprefix_path( self::relative_path( self::request_uri() ) ), $language );

		return set_url_scheme( untrailingslashit( (string) get_option( 'home' ) ) . '/' . ltrim( self::home_path() . $path, '/' ) );
	}

	/* ---------------------------------------------------------------------
	 * Search engines
	 * ------------------------------------------------------------------ */

	public static function language_attributes( $output ) {
		if ( ! self::is_enabled() || self::is_default() ) {
			return $output;
		}

		return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( self::language() ) . '"', (string) $output );
	}

	public static function body_class( $classes ) {
		$classes[] = 'igc-lang-' . sanitize_html_class( self::language() );

		return $classes;
	}

	/**
	 * Both versions are indexable and point at each other. The default language
	 * is x-default because it is the site's own.
	 */
	public static function head_alternates(): void {
		if ( ! self::is_enabled() || is_admin() || is_404() || is_search() ) {
			return;
		}

		$output = '';

		foreach ( array_keys( self::languages() ) as $code ) {
			$output .= sprintf(
				'<link rel="alternate" hreflang="%1$s" href="%2$s" />' . "\n",
				esc_attr( $code ),
				esc_url( self::alternate_url( $code ) )
			);
		}

		$output .= sprintf(
			'<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
			esc_url( self::alternate_url( self::default_language() ) )
		);

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_url/esc_attr above.
	}

	/**
	 * SEOPress builds its canonical from the permalink, which already carries the
	 * prefix through the home_url filter. This also covers canonicals stored by
	 * hand. prefix_path() is idempotent, so running twice is harmless.
	 */
	public static function canonical( $canonical ) {
		if ( ! self::is_enabled() || self::is_default() || ! is_string( $canonical ) || '' === $canonical ) {
			return $canonical;
		}

		$parts = wp_parse_url( $canonical );

		if ( ! isset( $parts['host'], $parts['scheme'] ) ) {
			return $canonical;
		}

		$relative = self::relative_path( $canonical );

		if ( ! self::is_translatable_path( $relative ) ) {
			return $canonical;
		}

		$query = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';

		return $parts['scheme'] . '://' . $parts['host'] . self::home_path() . self::prefix_path( $relative, self::language() ) . $query;
	}

	public static function og_locale( $locale ) {
		$languages = self::languages();

		return $languages[ self::language() ]['locale'] ?? $locale;
	}

	/**
	 * WordPress' own sitemap lists the default language only, because every URL it
	 * knows about is a default-language permalink. This adds a provider so the
	 * prefixed URLs are submitted too, one section per secondary language.
	 */
	public static function register_sitemaps(): void {
		if ( ! self::is_enabled() || ! function_exists( 'wp_register_sitemap_provider' ) ) {
			return;
		}

		wp_register_sitemap_provider( 'ioulia', new IGC_I18N_Sitemap() );
	}

	/**
	 * The first provider key contained a dash, which core sitemap rewrites cannot
	 * parse. Redirect cached and bookmarked versions to the valid provider URL.
	 */
	public static function redirect_legacy_sitemap(): void {
		$path = self::relative_path( self::request_uri() );

		foreach ( array_keys( self::secondary_languages() ) as $code ) {
			if ( in_array( $path, array( 'wp-sitemap-igc-i18n-' . $code . '-1.xml', 'wp-sitemap-igci18n-' . $code . '-1.xml' ), true ) ) {
				wp_safe_redirect( home_url( '/wp-sitemap-ioulia-' . $code . '-1.xml' ), 301 );
				exit;
			}
		}
	}
}
