<?php
/**
 * The look of the mail this site sends.
 *
 * One builder, used by the contact form and by the workshop bookings, so an
 * enquiry and a booking arrive looking like the same studio wrote them.
 *
 * Email is not the web and this is written for that, not against it:
 *
 *   - Tables, not flex or grid. The Outlook desktop clients render through
 *     Word, which has no support for either.
 *   - Every style inline. Gmail strips a <style> block in several contexts,
 *     and there are no classes to hang one on anyway.
 *   - No web font. Montserrat is the site's face and almost no mail client
 *     will fetch it, so the stack starts at the system UI font rather than
 *     naming something that will silently fall back to Times.
 *   - Hex colours, not rgba. Older clients drop the whole declaration.
 *   - The button is a padded link, not a <button>. A form control in an email
 *     is either stripped or dead.
 *
 * border-radius is ignored by Word, so the corner the rest of the site has is
 * a corner most people will see and Outlook users will not. That is the right
 * way round: the button still reads as a button when it is square.
 *
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_email_palette' ) ) {
	function ioulia_email_palette() {
		return array(
			'paper' => '#FFFFFF',
			'ink'   => '#2B2B2B',
			'muted' => '#77746D',
			'line'  => '#E6E3DA',
		);
	}
}

if ( ! function_exists( 'ioulia_email_font' ) ) {
	function ioulia_email_font() {
		return '-apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif';
	}
}

if ( ! function_exists( 'ioulia_email_logo' ) ) {
	/**
	 * The mark at the top of every message.
	 *
	 * A hosted PNG, not the circle-and-border the site header draws with CSS:
	 * Outlook renders through Word, which has no border-radius, so a drawn
	 * circle arrives as a square. Not an SVG either - Gmail strips those.
	 *
	 * Most clients block remote images until the reader allows them, so the alt
	 * text is the studio's name rather than the word "logo": with images off,
	 * the name is what appears, which is the point of a masthead.
	 *
	 * Filter the URL to swap the file without touching this:
	 *
	 *     add_filter( 'ioulia_email_logo_url', function () {
	 *         return 'https://.../wordmark.png';
	 *     } );
	 */
	function ioulia_email_logo_url() {
		return apply_filters(
			'ioulia_email_logo_url',
			'https://iouliageraskliceramics.com/wp-content/uploads/2020/03/cropped-DarkIGLOGO-3.png'
		);
	}
}

if ( ! function_exists( 'ioulia_email_masthead' ) ) {
	function ioulia_email_masthead() {
		$c    = ioulia_email_palette();
		$font = ioulia_email_font();
		$url  = ioulia_email_logo_url();

		if ( '' === $url ) {
			return sprintf(
				'<p style="margin:0;color:%1$s;font-family:%2$s;font-size:13px;font-weight:600;line-height:1.5;letter-spacing:.08em;text-transform:lowercase;">ioulia geraskli<br>ceramic lab</p>',
				esc_attr( $c['ink'] ),
				esc_attr( $font )
			);
		}

		/* width and height on the tag itself, not only in the style: Outlook
		   reads the attributes and ignores the CSS. */
		return sprintf(
			'<a href="%1$s" style="display:inline-block;margin-left:-8px;text-decoration:none;"><img src="%2$s" width="84" height="87" alt="Ioulia Geraskli Ceramic Lab" style="display:block;width:84px;height:auto;border:0;outline:none;text-decoration:none;"></a>',
			esc_url( home_url( '/' ) ),
			esc_url( $url )
		);
	}
}

if ( ! function_exists( 'ioulia_email_button' ) ) {
	/**
	 * One button. 'outline' is the quieter of the two - used where a message
	 * carries both a main action and a way to undo it, so the two do not shout
	 * at each other.
	 */
	function ioulia_email_button( $label, $url, $variant = 'filled' ) {
		$c    = ioulia_email_palette();
		$font = ioulia_email_font();

		$background = 'outline' === $variant ? 'transparent' : $c['ink'];
		$colour     = 'outline' === $variant ? $c['ink'] : $c['paper'];
		$border     = 'outline' === $variant ? $c['ink'] : $c['ink'];

		return sprintf(
			'<a href="%1$s" style="display:inline-block;padding:15px 26px;border:1px solid %2$s;border-radius:12px;background:%3$s;color:%4$s;font-family:%5$s;font-size:14px;font-weight:600;line-height:1;letter-spacing:.02em;text-decoration:none;">%6$s</a>',
			esc_url( $url ),
			esc_attr( $border ),
			esc_attr( $background ),
			esc_attr( $colour ),
			esc_attr( $font ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'ioulia_email_html' ) ) {
	/**
	 * $args:
	 *   title    - the heading
	 *   intro    - one or more paragraphs
	 *   rows     - label => value pairs, printed as a list of facts
	 *   quote    - a block of the visitor's own words, set apart
	 *   buttons  - array of array( label, url, variant )
	 *   footnote - the quiet line under the buttons
	 */
	function ioulia_email_html( $args ) {
		$c    = ioulia_email_palette();
		$font = ioulia_email_font();

		$args = array_merge(
			array(
				'title'    => '',
				'intro'    => array(),
				'rows'     => array(),
				'quote'    => '',
				'buttons'  => array(),
				'footnote' => '',
			),
			(array) $args
		);

		$body = '';

		if ( '' !== $args['title'] ) {
			$body .= sprintf(
				'<h1 style="margin:0 0 20px;color:%1$s;font-family:%2$s;font-size:30px;font-weight:400;line-height:1.15;letter-spacing:-.02em;">%3$s</h1>',
				esc_attr( $c['ink'] ),
				esc_attr( $font ),
				esc_html( $args['title'] )
			);
		}

		foreach ( (array) $args['intro'] as $paragraph ) {
			$body .= sprintf(
				'<p style="margin:0 0 16px;color:%1$s;font-family:%2$s;font-size:15px;font-weight:400;line-height:1.62;">%3$s</p>',
				esc_attr( $c['ink'] ),
				esc_attr( $font ),
				esc_html( $paragraph )
			);
		}

		/* Somebody's own words come before the facts about them: in an enquiry
		   the message is the point of the email, and in a cancellation the
		   reason is. */
		if ( '' !== $args['quote'] ) {
			$body .= sprintf(
				'<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%%" style="width:100%%;margin:0 0 26px;border-collapse:collapse;"><tr><td style="padding:2px 0 2px 18px;border-left:2px solid %1$s;color:%2$s;font-family:%3$s;font-size:15px;font-weight:400;line-height:1.62;white-space:pre-wrap;">%4$s</td></tr></table>',
				esc_attr( $c['line'] ),
				esc_attr( $c['ink'] ),
				esc_attr( $font ),
				esc_html( $args['quote'] )
			);
		}

		/* The facts. A hairline above and below rather than a box, the way the
		   summary on the cart reads. */
		if ( ! empty( $args['rows'] ) ) {
			$rows = '';

			foreach ( $args['rows'] as $label => $value ) {
				if ( '' === trim( (string) $value ) ) {
					continue;
				}

				/* Written on one line on purpose: the whitespace between tags in a
				   multi-line string survives into the plain-text part, where it
				   turns every row into three ragged lines. */
				$rows .= sprintf(
					'<tr><td style="padding:7px 12px 7px 0;color:%1$s;font-family:%2$s;font-size:14px;font-weight:400;line-height:1.5;vertical-align:top;white-space:nowrap;">%3$s</td><td style="padding:7px 0;color:%4$s;font-family:%2$s;font-size:15px;font-weight:500;line-height:1.5;text-align:right;vertical-align:top;">%5$s</td></tr>',
					esc_attr( $c['muted'] ),
					esc_attr( $font ),
					esc_html( $label ),
					esc_attr( $c['ink'] ),
					esc_html( $value )
				);
			}

			if ( '' !== $rows ) {
				$body .= sprintf(
					'<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%%" style="width:100%%;margin:30px 0 34px;padding-top:22px;border-top:1px solid %1$s;border-collapse:collapse;">%2$s</table>',
					esc_attr( $c['line'] ),
					$rows
				);
			}
		}

		if ( ! empty( $args['buttons'] ) ) {
			$buttons = '';

			foreach ( $args['buttons'] as $button ) {
				if ( empty( $button['url'] ) || empty( $button['label'] ) ) {
					continue;
				}

				$buttons .= '<td style="padding:0 10px 10px 0;">'
					. ioulia_email_button( $button['label'], $button['url'], isset( $button['variant'] ) ? $button['variant'] : 'filled' )
					. '</td>';
			}

			if ( '' !== $buttons ) {
				$body .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 8px;border-collapse:collapse;"><tr>' . $buttons . '</tr></table>';
			}
		}

		if ( '' !== $args['footnote'] ) {
			$body .= sprintf(
				'<p style="margin:18px 0 0;color:%1$s;font-family:%2$s;font-size:13px;font-weight:400;line-height:1.55;">%3$s</p>',
				esc_attr( $c['muted'] ),
				esc_attr( $font ),
				esc_html( $args['footnote'] )
			);
		}

		/* The name is in the masthead now, so the foot carries the things a
		   masthead cannot: where the studio is, and how to reach it. */
		$signature = sprintf(
			'<p style="margin:0;color:%1$s;font-family:%2$s;font-size:12px;font-weight:500;line-height:1.7;letter-spacing:.02em;">Προμπονά 42, Άνω Πατήσια, 111 43 Αθήνα<br><a href="mailto:%3$s" style="color:%1$s;text-decoration:none;">%3$s</a> &nbsp;·&nbsp; <a href="%4$s" style="color:%1$s;text-decoration:none;">iouliageraskliceramics.com</a></p>',
			esc_attr( $c['muted'] ),
			esc_attr( $font ),
			esc_attr( function_exists( 'ioulia_studio_address' ) ? ioulia_studio_address() : 'info@iouliageraskliceramics.com' ),
			esc_url( home_url( '/' ) )
		);

		/* color-scheme tells a client in dark mode that this page has already
		   chosen its colours, which stops the better ones inverting them. */
		return '<!doctype html>'
			. '<html><head><meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1">'
			. '<meta name="color-scheme" content="light">'
			. '<meta name="supported-color-schemes" content="light">'
			. '</head>'
			. '<body style="margin:0;padding:0;background:' . esc_attr( $c['paper'] ) . ';">'
			/* One gutter, stated once, so the mark, the words and the sign-off all
			   start on the same vertical line. The masthead and the sign-off are
			   held apart by space rather than by rules: the only line in the
			   message is the one above the facts. */
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;background:' . esc_attr( $c['paper'] ) . ';border-collapse:collapse;">'
			. '<tr><td align="center" style="padding:36px 16px 56px;">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="width:560px;max-width:100%;border-collapse:collapse;">'
			. '<tr><td style="padding:0 28px;">' . ioulia_email_masthead() . '</td></tr>'
			. '<tr><td style="padding:36px 28px 0;">' . $body . '</td></tr>'
			. '<tr><td style="padding:44px 28px 0;">' . $signature . '</td></tr>'
			. '</table>'
			. '</td></tr></table>'
			. '</body></html>';
	}
}

if ( ! function_exists( 'ioulia_send_html_mail' ) ) {
	/**
	 * wp_mail with the content type set for this message only. The filter is
	 * removed straight after, so one HTML message does not turn every later
	 * message in the same request into HTML.
	 */
	function ioulia_send_html_mail( $to, $subject, $html, $reply_to = '' ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		if ( is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$type = function () {
			return 'text/html';
		};

		add_filter( 'wp_mail_content_type', $type );
		$sent = wp_mail( $to, $subject, $html, $headers );
		remove_filter( 'wp_mail_content_type', $type );

		return $sent;
	}
}
