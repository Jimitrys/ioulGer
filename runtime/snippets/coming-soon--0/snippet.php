<?php
/**
 * The curtain.
 *
 * Every front-end page answers with one screen instead of the site. Opening
 * /123 once lets that browser through for a month; everybody else keeps seeing
 * the curtain until it is taken down.
 *
 * ---------------------------------------------------------------------------
 * HOW TO GET IN
 *
 *     https://iouliageraskliceramics.com/123
 *
 * It sets a cookie and sends you to the homepage. From then on the site behaves
 * exactly as it did - shop, cart, bookings, everything - on that browser. Open
 * it again on a phone to let the phone through too.
 *
 * HOW TO TAKE IT DOWN
 *
 * One line in wp-config.php:
 *
 *     define( 'IOULIA_COMING_SOON', false );
 *
 * ---------------------------------------------------------------------------
 * WHY 503 AND NOT 200
 *
 * This site is already indexed and has had a lot of work put into how it reads
 * in search. A "coming soon" page answering 200 tells Google that thin page is
 * now what every URL contains, and it will replace the real ones with it. 503
 * with Retry-After says the opposite - temporarily away, come back - and
 * rankings are held rather than rewritten.
 *
 * That is right for a hold of days or a few weeks. If the site is going to sit
 * behind this for months, 200 is the honest answer instead, and it is one
 * filter:
 *
 *     add_filter( 'ioulia_coming_soon_status', function () { return 200; } );
 *
 * ---------------------------------------------------------------------------
 *
 * The admin is never shut out: wp-admin, wp-login and anyone signed in with
 * manage_options pass through without the key.
 *
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! defined( 'IOULIA_COMING_SOON_COOKIE' ) ) {
	define( 'IOULIA_COMING_SOON_COOKIE', 'ioulia_pass' );
}

if ( ! function_exists( 'ioulia_coming_soon_on' ) ) {
	function ioulia_coming_soon_on() {
		$on = defined( 'IOULIA_COMING_SOON' ) ? (bool) IOULIA_COMING_SOON : true;

		return (bool) apply_filters( 'ioulia_coming_soon', $on );
	}
}

if ( ! function_exists( 'ioulia_coming_soon_key' ) ) {
	function ioulia_coming_soon_key() {
		return (string) apply_filters( 'ioulia_coming_soon_key', '123' );
	}
}

if ( ! function_exists( 'ioulia_coming_soon_ticket' ) ) {
	/**
	 * What the cookie holds. Derived from the site's own salts, so it cannot be
	 * guessed from the key and changes if the salts are rotated.
	 */
	function ioulia_coming_soon_ticket() {
		return wp_hash( 'ioulia_coming_soon:' . ioulia_coming_soon_key() );
	}
}

if ( ! function_exists( 'ioulia_coming_soon_path' ) ) {
	/**
	 * The requested path, without the site's own subdirectory and without a
	 * language prefix, so /en/123 opens the door as readily as /123.
	 */
	function ioulia_coming_soon_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

		if ( '' !== $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}

		$path = trim( $path, '/' );

		if ( function_exists( 'ioulia_unprefix_path' ) ) {
			$path = trim( (string) ioulia_unprefix_path( $path ), '/' );
		}

		return $path;
	}
}

if ( ! function_exists( 'ioulia_coming_soon_allowed' ) ) {
	function ioulia_coming_soon_allowed() {
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( empty( $_COOKIE[ IOULIA_COMING_SOON_COOKIE ] ) ) {
			return false;
		}

		$held = sanitize_text_field( wp_unslash( $_COOKIE[ IOULIA_COMING_SOON_COOKIE ] ) );

		return hash_equals( ioulia_coming_soon_ticket(), $held );
	}
}

if ( ! function_exists( 'ioulia_coming_soon_unlock' ) ) {
	/**
	 * The key in the address bar. Sets the cookie and sends the browser to the
	 * homepage, so the key is not left sitting in history as a live URL.
	 */
	function ioulia_coming_soon_unlock() {
		if ( ! ioulia_coming_soon_on() || headers_sent() ) {
			return;
		}

		if ( ioulia_coming_soon_path() !== ioulia_coming_soon_key() ) {
			return;
		}

		setcookie(
			IOULIA_COMING_SOON_COOKIE,
			ioulia_coming_soon_ticket(),
			array(
				'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		wp_safe_redirect( home_url( '/' ), 302 );

		/* The validator blocks exit, so the body is silenced instead: returning
		   nothing from template_include means WordPress includes no template at
		   all, and the browser follows the header it already has. */
		add_filter( 'template_include', '__return_false', 99 );
	}

	add_action( 'template_redirect', 'ioulia_coming_soon_unlock', 0 );
}

if ( ! function_exists( 'ioulia_coming_soon_screen' ) ) {
	function ioulia_coming_soon_screen() {
		$english = function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang();
		$email   = function_exists( 'ioulia_studio_address' ) ? ioulia_studio_address() : 'info@iouliageraskliceramics.com';

		$title = $english ? 'Something new is coming.' : 'Κάτι καινούργιο ετοιμάζεται.';
		$copy  = $english
			? 'The studio is still here. We are putting the new site together and it will not be long.'
			: 'Το εργαστήριο είναι εδώ. Ετοιμάζουμε το νέο σάιτ και δεν θα αργήσουμε.';
		$aside = $english ? 'Write to us in the meantime' : 'Γράψε μας στο μεταξύ';

		ob_start();
		?>
<!doctype html>
<html lang="<?php echo esc_attr( $english ? 'en' : 'el' ); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $title ); ?> — Ioulia Geraskli Ceramic Lab</title>
	<style>
		:root { color-scheme: light; }
		* { box-sizing: border-box; }
		body {
			display: grid;
			min-height: 100svh;
			margin: 0;
			padding: clamp(2rem, 8vw, 5rem) clamp(1.25rem, 5vw, 3rem);
			background: #FFFEF7;
			color: #2B2B2B;
			font-family: "Montserrat z", -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
			place-items: center;
			text-align: center;
		}
		.cs { width: 100%; max-width: 30rem; }
		.cs__mark { display: block; width: clamp(96px, 22vw, 132px); height: auto; margin: 0 auto clamp(2rem, 6vw, 3rem); }
		.cs__title {
			margin: 0 0 1rem;
			font-size: clamp(1.9rem, 5.5vw, 2.9rem);
			font-weight: 400;
			line-height: 1.06;
			letter-spacing: -.04em;
			text-wrap: balance;
		}
		.cs__copy {
			margin: 0 0 clamp(2.25rem, 6vw, 3rem);
			color: rgba(43, 43, 43, .65);
			font-size: 1.0625rem;
			font-weight: 500;
			line-height: 1.6;
			text-wrap: pretty;
		}
		.cs__label {
			display: block;
			margin-bottom: .55rem;
			color: rgba(43, 43, 43, .45);
			font-size: .78rem;
			font-weight: 500;
			letter-spacing: .09em;
			text-transform: uppercase;
		}
		.cs__mail {
			color: #2B2B2B;
			font-size: 1.0625rem;
			font-weight: 500;
			text-decoration: none;
			text-underline-offset: 5px;
		}
		.cs__mail:hover { text-decoration: underline; }
		.cs__social { margin-top: clamp(2rem, 6vw, 2.75rem); }
		.cs__social a {
			color: rgba(43, 43, 43, .65);
			font-size: .9rem;
			font-weight: 500;
			letter-spacing: .04em;
			text-decoration: none;
		}
		.cs__social a:hover { color: #2B2B2B; text-decoration: underline; text-underline-offset: 4px; }
	</style>
</head>
<body>
	<main class="cs">
		<img class="cs__mark"
			src="https://iouliageraskliceramics.com/wp-content/uploads/2020/03/cropped-DarkIGLOGO-3.png"
			alt="Ioulia Geraskli Ceramic Lab"
			width="896" height="925">

		<h1 class="cs__title"><?php echo esc_html( $title ); ?></h1>
		<p class="cs__copy"><?php echo esc_html( $copy ); ?></p>

		<span class="cs__label"><?php echo esc_html( $aside ); ?></span>
		<a class="cs__mail" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>

		<p class="cs__social">
			<a href="https://www.instagram.com/iouliageraskli/" rel="noopener" target="_blank">Instagram</a>
		</p>
	</main>
</body>
</html>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'ioulia_coming_soon_gate' ) ) {
	function ioulia_coming_soon_gate() {
		if ( ! ioulia_coming_soon_on() || is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( ioulia_coming_soon_path() === ioulia_coming_soon_key() ) {
			return;
		}

		if ( ioulia_coming_soon_allowed() ) {
			return;
		}

		$status = (int) apply_filters( 'ioulia_coming_soon_status', 503 );

		nocache_headers();
		status_header( 503 === $status ? 503 : 200 );

		if ( 503 === $status && ! headers_sent() ) {
			header( 'Retry-After: 86400' );
		}

		echo ioulia_coming_soon_screen(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped above.

		/* Nothing else is printed: with no template to include, the request ends
		   with exactly what was echoed here. */
		add_filter( 'template_include', '__return_false', 99 );
	}

	add_action( 'template_redirect', 'ioulia_coming_soon_gate', 1 );
}

if ( ! function_exists( 'ioulia_coming_soon_quiet' ) ) {
	/**
	 * While the curtain is up there is nothing to crawl and nothing to announce.
	 * The sitemaps stop and IndexNow is held, so search engines are not sent to
	 * URLs that answer 503.
	 */
	function ioulia_coming_soon_quiet() {
		if ( ! ioulia_coming_soon_on() ) {
			return;
		}

		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		add_filter( 'ioulia_indexnow_enabled', '__return_false' );
	}

	add_action( 'init', 'ioulia_coming_soon_quiet', 5 );
}
