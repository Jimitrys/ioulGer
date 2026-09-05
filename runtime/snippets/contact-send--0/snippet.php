<?php
/**
 * Contact form — delivery.
 *
 * The contact canvas is static HTML, so it cannot print a nonce of its own,
 * and the page is cached, so a nonce printed into it would go stale. The form
 * asks for a fresh one at send time instead.
 *
 * Nothing is stored. The enquiry is relayed to the studio and acknowledged to
 * the sender, and that is the whole record of it — the least this site can
 * hold and still answer someone.
 *
 * No backslashes anywhere in this file: Site Studio unslashes snippet code on
 * import, so chr( 10 ) stands in for a newline. See CONVENTIONS.md.
 */

if ( ! function_exists( 'ioulia_contact_studio_email' ) ) {
	/**
	 * The address a sender replies to. Separate from who gets notified: the
	 * acknowledgement points at the studio, while the enquiry itself goes to
	 * whoever is on the notification list.
	 */
	function ioulia_contact_studio_email() {
		if ( function_exists( 'ioulia_studio_address' ) ) {
			return ioulia_studio_address();
		}

		$address = apply_filters( 'ioulia_contact_recipient', 'info@iouliageraskliceramics.com' );

		return is_email( $address ) ? $address : get_option( 'admin_email' );
	}
}

if ( ! function_exists( 'ioulia_contact_notify_to' ) ) {
	function ioulia_contact_notify_to() {
		return function_exists( 'ioulia_studio_recipients' )
			? ioulia_studio_recipients()
			: ioulia_contact_studio_email();
	}
}

if ( ! function_exists( 'ioulia_contact_token' ) ) {
	function ioulia_contact_token() {
		nocache_headers();
		wp_send_json_success( array( 'nonce' => wp_create_nonce( 'ioulia_contact' ) ) );
	}

	add_action( 'wp_ajax_ioulia_contact_token', 'ioulia_contact_token' );
	add_action( 'wp_ajax_nopriv_ioulia_contact_token', 'ioulia_contact_token' );
}

if ( ! function_exists( 'ioulia_contact_throttle_key' ) ) {
	/**
	 * Rate limiting needs to tell senders apart without keeping their address.
	 * A salted hash does that and is meaningless on its own.
	 */
	function ioulia_contact_throttle_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

		return 'ioulia_contact_' . md5( wp_salt( 'nonce' ) . $ip );
	}
}

if ( ! function_exists( 'ioulia_contact_field' ) ) {
	function ioulia_contact_field( $key, $multiline = false ) {
		if ( empty( $_POST[ $key ] ) ) {
			return '';
		}

		$raw = wp_unslash( $_POST[ $key ] );

		return $multiline ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	}
}

if ( ! function_exists( 'ioulia_contact_labels' ) ) {
	function ioulia_contact_labels() {
		return array(
			'vase'          => 'Βάζο',
			'bowl'          => 'Μπολ ή πιατέλα',
			'dinnerware'    => 'Σερβίτσιο',
			'sculpture'     => 'Γλυπτό αντικείμενο',
			'tile_surface'  => 'Αρχιτεκτονική επιφάνεια',
			'other'         => 'Κάτι άλλο',
			'workshops'     => 'Μαθήματα κεραμικής',
			'studio_visit'  => 'Επίσκεψη στο εργαστήριο',
			'collaboration' => 'Συνεργασία ή Τύπος',
			'general'       => 'Γενική ερώτηση',
		);
	}
}

if ( ! function_exists( 'ioulia_contact_send' ) ) {
	function ioulia_contact_send() {
		if ( ! check_ajax_referer( 'ioulia_contact', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'expired' ), 403 );
		}

		// A bot fills every field it is given; a person never sees this one.
		if ( '' !== ioulia_contact_field( 'website' ) ) {
			wp_send_json_success( array( 'ok' => true ) );
		}

		$throttle = ioulia_contact_throttle_key();
		$sent     = (int) get_transient( $throttle );

		if ( $sent >= 5 ) {
			wp_send_json_error( array( 'message' => 'too many' ), 429 );
		}

		$name  = ioulia_contact_field( 'full_name' );
		$email = sanitize_email( ioulia_contact_field( 'email_address' ) );

		if ( '' === $name || ! is_email( $email ) || '' === ioulia_contact_field( 'consent' ) ) {
			wp_send_json_error( array( 'message' => 'incomplete' ), 400 );
		}

		$labels  = ioulia_contact_labels();
		$type    = ioulia_contact_field( 'inquiry_type' );
		$custom  = 'general_question' !== $type;
		$phone   = ioulia_contact_field( 'phone_number' );
		$message = $custom
			? ioulia_contact_field( 'piece_description', true )
			: ioulia_contact_field( 'general_message', true );

		if ( '' === $message ) {
			wp_send_json_error( array( 'message' => 'incomplete' ), 400 );
		}

		$lines = array( $custom ? 'Αίτημα για κεραμικό κατά παραγγελία' : 'Γενική ερώτηση', '' );

		if ( $custom ) {
			$category = ioulia_contact_field( 'piece_category' );

			if ( isset( $labels[ $category ] ) ) {
				$lines[] = 'Κεραμικό: ' . $labels[ $category ];
			}

			$size = ioulia_contact_field( 'piece_size_label' );

			if ( '' !== $size ) {
				$lines[] = 'Μέγεθος: ' . $size;
			}

			$finish = ioulia_contact_field( 'color_finish' );

			if ( '' !== $finish ) {
				$lines[] = 'Υάλωμα: ' . $finish;
			}
		} else {
			$topic = ioulia_contact_field( 'inquiry_topic' );

			if ( isset( $labels[ $topic ] ) ) {
				$lines[] = 'Θέμα: ' . $labels[ $topic ];
			}
		}

		$lines[] = '';
		$lines[] = $message;
		$lines[] = '';
		$lines[] = 'Από: ' . $name;
		$lines[] = 'Email: ' . $email;

		if ( '' !== $phone ) {
			$lines[] = 'Τηλέφωνο: ' . $phone;
		}

		$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' );
		$subject = ( $custom ? 'Παραγγελία κεραμικού από ' : 'Μήνυμα από ' ) . $name;

		$delivered = wp_mail( ioulia_contact_notify_to(), $subject, implode( chr( 10 ), $lines ), $headers );

		if ( ! $delivered ) {
			wp_send_json_error( array( 'message' => 'mail failed' ), 500 );
		}

		set_transient( $throttle, $sent + 1, HOUR_IN_SECONDS );

		/* The studio reads Greek; the sender reads whichever side of the site
		   they were on when they wrote. */
		$english = 'en' === ioulia_contact_field( 'lang' );

		$reply = $english
			? array(
				'Thank you for getting in touch.',
				'',
				'Your message has reached the studio and Ioulia will answer it personally. A copy of what you sent is below.',
			)
			: array(
				'Ευχαριστούμε που επικοινώνησες.',
				'',
				'Το μήνυμά σου έφτασε στο εργαστήριο και η Ιουλία θα σου απαντήσει προσωπικά. Πιο κάτω είναι ένα αντίγραφο.',
			);

		$reply[] = '';
		$reply[] = '---';
		$reply[] = '';
		$reply[] = $message;
		$reply[] = '';
		$reply[] = '---';
		$reply[] = '';
		$reply[] = 'Ioulia Geraskli Ceramics';
		$reply[] = home_url( '/' );

		wp_mail(
			$email,
			$english ? 'We received your message' : 'Λάβαμε το μήνυμά σου',
			implode( chr( 10 ), $reply ),
			array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . ioulia_contact_studio_email() )
		);

		wp_send_json_success( array( 'ok' => true ) );
	}

	add_action( 'wp_ajax_ioulia_contact', 'ioulia_contact_send' );
	add_action( 'wp_ajax_nopriv_ioulia_contact', 'ioulia_contact_send' );
}
