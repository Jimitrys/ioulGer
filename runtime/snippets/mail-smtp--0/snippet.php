<?php
/**
 * Outgoing mail — SMTP.
 *
 * Everything this site sends goes through wp_mail(): the contact form, the
 * workshop bookings, and every WooCommerce order email. Left alone, wp_mail()
 * hands the message to the server's local PHP mail(), which sends it from the
 * hosting machine with no authentication behind it. Gmail, Outlook and iCloud
 * increasingly file that in spam or refuse it outright - so an order goes
 * through, the customer never sees the confirmation, and nobody finds out.
 *
 * This routes wp_mail() through a real authenticated mailbox instead.
 *
 * ---------------------------------------------------------------------------
 * WHAT TO DO
 *
 * The credentials are deliberately NOT in this repository. Add them to
 * wp-config.php, above the line that says "That's all, stop editing":
 *
 *     define( 'IOULIA_SMTP_HOST',   'smtp.example.com' );
 *     define( 'IOULIA_SMTP_PORT',   587 );
 *     define( 'IOULIA_SMTP_USER',   'info@iouliageraskliceramics.com' );
 *     define( 'IOULIA_SMTP_PASS',   'the mailbox password' );
 *     define( 'IOULIA_SMTP_SECURE', 'tls' );
 *
 * Until IOULIA_SMTP_HOST, _USER and _PASS all exist, this snippet does nothing
 * at all and mail keeps going out exactly as it does today. Nothing breaks
 * while the details are being found.
 *
 * Port 587 with 'tls' is the usual pair. A few providers want 465 with 'ssl'.
 * ---------------------------------------------------------------------------
 *
 * No backslashes anywhere in this file: Site Studio unslashes snippet code on
 * import. See CONVENTIONS.md.
 */

if ( ! function_exists( 'ioulia_smtp_configured' ) ) {
	function ioulia_smtp_configured() {
		return defined( 'IOULIA_SMTP_HOST' ) && IOULIA_SMTP_HOST
			&& defined( 'IOULIA_SMTP_USER' ) && IOULIA_SMTP_USER
			&& defined( 'IOULIA_SMTP_PASS' ) && IOULIA_SMTP_PASS;
	}
}

if ( ! function_exists( 'ioulia_smtp_from_address' ) ) {
	/**
	 * The address a message claims to come from has to belong to the domain that
	 * is actually sending it, or SPF and DMARC fail and the message is treated
	 * as forged. WordPress otherwise sends as wordpress@<host>, which is exactly
	 * the kind of address that gets rejected.
	 */
	function ioulia_smtp_from_address() {
		if ( defined( 'IOULIA_SMTP_FROM' ) && is_email( IOULIA_SMTP_FROM ) ) {
			return IOULIA_SMTP_FROM;
		}
		if ( defined( 'IOULIA_SMTP_USER' ) && is_email( IOULIA_SMTP_USER ) ) {
			return IOULIA_SMTP_USER;
		}

		return get_option( 'admin_email' );
	}
}

if ( ! function_exists( 'ioulia_smtp_from_name' ) ) {
	function ioulia_smtp_from_name() {
		if ( defined( 'IOULIA_SMTP_FROM_NAME' ) && IOULIA_SMTP_FROM_NAME ) {
			return IOULIA_SMTP_FROM_NAME;
		}

		return 'Ioulia Geraskli Ceramic Lab';
	}
}

if ( ! function_exists( 'ioulia_smtp_sender' ) ) {
	/**
	 * These two run whether or not SMTP is configured: a sensible From address
	 * helps on plain mail() as well, and it is the half of the problem that
	 * needs no credentials.
	 */
	function ioulia_smtp_sender( $address ) {
		$from = ioulia_smtp_from_address();

		return is_email( $from ) ? $from : $address;
	}

	function ioulia_smtp_sender_name( $name ) {
		return ioulia_smtp_from_name();
	}

	add_filter( 'wp_mail_from', 'ioulia_smtp_sender', 20 );
	add_filter( 'wp_mail_from_name', 'ioulia_smtp_sender_name', 20 );
}

if ( ! function_exists( 'ioulia_smtp_configure' ) ) {
	/**
	 * PHPMailer is handed to us already built, so this only switches the
	 * transport underneath it. Nothing about the message itself changes, which
	 * is why WooCommerce templates and the contact snippet need no edits.
	 */
	function ioulia_smtp_configure( $mailer ) {
		if ( ! ioulia_smtp_configured() ) {
			return;
		}

		$mailer->isSMTP();
		$mailer->Host       = IOULIA_SMTP_HOST;
		$mailer->SMTPAuth   = true;
		$mailer->Username   = IOULIA_SMTP_USER;
		$mailer->Password   = IOULIA_SMTP_PASS;
		$mailer->Port       = defined( 'IOULIA_SMTP_PORT' ) ? (int) IOULIA_SMTP_PORT : 587;
		$mailer->CharSet    = 'UTF-8';
		$mailer->Encoding   = 'base64';

		$secure = defined( 'IOULIA_SMTP_SECURE' ) ? strtolower( (string) IOULIA_SMTP_SECURE ) : 'tls';
		if ( in_array( $secure, array( 'tls', 'ssl' ), true ) ) {
			$mailer->SMTPSecure = $secure;
		} else {
			$mailer->SMTPAutoTLS = false;
			$mailer->SMTPSecure  = '';
		}

		/* The envelope sender, which is what SPF is actually checked against.
		   It is separate from the From header and is easy to leave behind. */
		$from = ioulia_smtp_from_address();
		if ( is_email( $from ) ) {
			$mailer->Sender = $from;
			$mailer->setFrom( $from, ioulia_smtp_from_name(), false );
		}
	}

	add_action( 'phpmailer_init', 'ioulia_smtp_configure' );
}

if ( ! function_exists( 'ioulia_smtp_log_failure' ) ) {
	/**
	 * A failed order email is silent otherwise: WooCommerce completes the order
	 * and nobody learns that the confirmation never left. This writes the reason
	 * to the PHP error log, which is where it can be found afterwards.
	 */
	function ioulia_smtp_log_failure( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return;
		}

		error_log( 'ioulia mail failed: ' . $error->get_error_message() );
	}

	add_action( 'wp_mail_failed', 'ioulia_smtp_log_failure' );
}
