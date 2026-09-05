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
 *     define( 'IOULIA_SMTP_HOST',   'mx152d.netcup.net' );
 *     define( 'IOULIA_SMTP_PORT',   465 );
 *     define( 'IOULIA_SMTP_USER',   'info@iouliageraskliceramics.com' );
 *     define( 'IOULIA_SMTP_PASS',   'the mailbox password' );
 *     define( 'IOULIA_SMTP_SECURE', 'ssl' );
 *
 * Those are the netcup values for this mailbox. 465 goes with 'ssl' - the
 * connection is encrypted from the first byte. 587 goes with 'tls', where it
 * starts in the clear and is upgraded; netcup only offers 465 here.
 *
 * Until IOULIA_SMTP_HOST, _USER and _PASS all exist, this snippet does nothing
 * at all and mail keeps going out exactly as it does today. Nothing breaks
 * while the password is being added.
 *
 * To check it afterwards, open /wp-admin/ with ?ioulia_mail_test=1 on the end
 * while logged in as an administrator. It sends one message to the site admin
 * address and says on screen whether the server accepted it.
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

if ( ! function_exists( 'ioulia_studio_recipients' ) ) {
	/**
	 * Who hears about it when someone writes, books a workshop, or orders.
	 *
	 * There used to be two answers to that question in two snippets - a filter
	 * defaulting to info@ for the contact form, and a settings key defaulting to
	 * the WordPress admin address for bookings - so adding a person meant
	 * remembering both. This is the one list, and it takes more than one address
	 * because Ioulia will want these as well as Dimitris.
	 *
	 * To add her, put her address in the array below. To change it without
	 * touching this file, filter it:
	 *
	 *     add_filter( 'ioulia_studio_recipients', function ( $to ) {
	 *         $to[] = 'ioulia@example.com';
	 *         return $to;
	 *     } );
	 *
	 * WooCommerce order emails are NOT covered here - it keeps its own recipient
	 * fields under WooCommerce > Settings > Emails, and it is the one place a
	 * change has to be made twice.
	 */
	function ioulia_studio_recipients() {
		$addresses = apply_filters(
			'ioulia_studio_recipients',
			array(
				'dimitrisantoniou2000@gmail.com',
			)
		);

		$addresses = array_values( array_unique( array_filter( (array) $addresses, 'is_email' ) ) );

		return $addresses ? $addresses : array( get_option( 'admin_email' ) );
	}
}

if ( ! function_exists( 'ioulia_studio_address' ) ) {
	/**
	 * The studio's own public address - what a visitor should reply to, which is
	 * not the same question as who gets notified internally.
	 */
	function ioulia_studio_address() {
		$address = apply_filters( 'ioulia_studio_address', 'info@iouliageraskliceramics.com' );

		return is_email( $address ) ? $address : get_option( 'admin_email' );
	}
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

if ( ! function_exists( 'ioulia_smtp_test' ) ) {
	/**
	 * A way to answer "did that work?" without placing a real order.
	 *
	 * Administrators only, on an explicit query argument, in the admin. It sends
	 * one message and reports what the mail server said back - which is the part
	 * that matters, because a wrong password or a blocked port fails here
	 * silently otherwise.
	 *
	 * The default recipient is the site admin address, which is not necessarily
	 * the studio mailbox - the first run of this went to an Outlook account while
	 * the netcup inbox was being watched for it. Pass an address to choose:
	 *
	 *     /wp-admin/?ioulia_mail_test=info@iouliageraskliceramics.com
	 *
	 * No nonce, deliberately: the URL is meant to be typed by hand, and the
	 * worst an administrator can be tricked into doing with it is sending
	 * themselves one email with no attacker-supplied content in it.
	 */
	function ioulia_smtp_test() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['ioulia_mail_test'] ) ) {
			return;
		}

		$reason = '';
		$catch  = function ( $error ) use ( &$reason ) {
			if ( is_wp_error( $error ) ) {
				$reason = $error->get_error_message();
			}
		};

		add_action( 'wp_mail_failed', $catch, 1 );

		$requested = sanitize_email( wp_unslash( (string) $_GET['ioulia_mail_test'] ) );
		$to        = is_email( $requested ) ? $requested : get_option( 'admin_email' );
		$sent      = wp_mail(
			$to,
			'Ioulia Geraskli — SMTP test',
			'If this arrived, wp_mail() is going out through '
				. ( ioulia_smtp_configured() ? IOULIA_SMTP_HOST : 'the local mail() function' )
				. ' as ' . ioulia_smtp_from_address() . '.',
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);

		remove_action( 'wp_mail_failed', $catch, 1 );

		$configured = ioulia_smtp_configured();

		add_action(
			'admin_notices',
			function () use ( $sent, $to, $reason, $configured ) {
				$lines = array();

				$lines[] = $configured
					? 'SMTP is configured: ' . IOULIA_SMTP_HOST . ' port '
						. ( defined( 'IOULIA_SMTP_PORT' ) ? (int) IOULIA_SMTP_PORT : 587 )
						. ' as ' . IOULIA_SMTP_USER . '.'
					: 'SMTP is NOT configured - the three constants are missing from wp-config.php, so this went through the local mail() function.';

				$lines[] = $sent
					? 'The mail server accepted a test message addressed to ' . $to . '. Look in THAT mailbox - inbox and spam. Sending as info@ does not put a copy in info@; add an address to the query argument to send somewhere else.'
					: 'The message was refused. ' . ( '' !== $reason ? $reason : 'No reason was given.' );

				printf(
					'<div class="notice notice-%1$s"><p>%2$s</p></div>',
					esc_attr( $sent && $configured ? 'success' : 'error' ),
					esc_html( implode( ' ', $lines ) )
				);
			}
		);
	}

	add_action( 'admin_init', 'ioulia_smtp_test', 20 );
}
