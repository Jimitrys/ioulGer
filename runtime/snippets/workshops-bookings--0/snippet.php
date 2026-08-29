<?php
/**
 * Workshop bookings — storage, seat counting, email and cancellation.
 *
 * This is the engine. It knows nothing about how a booking is collected or
 * displayed: the visitor form and Ioulia's dashboard both go through the
 * functions here, so there is one place where a seat is taken and one place
 * where an email is sent.
 *
 * A booking is a private post of type ioulia_booking. Nothing about it is
 * public: the post type is not queryable on the front end and the dashboard is
 * behind a capability check.
 *
 * Statuses are confirmed and cancelled. There is no approval step: a visitor who
 * books a free seat has it, and Ioulia cancels if she needs to, which sends the
 * visitor an email saying so. Cancelled bookings release their seats.
 *
 * Personal data: only name, email, phone and an optional note are kept, together
 * with the timestamp of the consent the visitor gave. Bookings are deleted
 * automatically once the session is far enough in the past, per the retention
 * setting in the workshops data snippet.
 *
 * Requires the "workshops data" snippet.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! defined( 'IOULIA_BOOKING_TYPE' ) ) {
	define( 'IOULIA_BOOKING_TYPE', 'ioulia_booking' );
}

/* -------------------------------------------------------------------------
 * Storage
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_register_booking_type' ) ) {
	function ioulia_register_booking_type() {
		register_post_type(
			IOULIA_BOOKING_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Κρατήσεις',
					'singular_name' => 'Κράτηση',
					'menu_name'     => 'Κρατήσεις',
					'all_items'     => 'Όλες οι κρατήσεις',
					'search_items'  => 'Αναζήτηση κρατήσεων',
					'not_found'     => 'Καμία κράτηση.',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-calendar-alt',
				'menu_position'       => 26,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'delete_with_user'    => false,
			)
		);
	}
	add_action( 'init', 'ioulia_register_booking_type', 5 );
}

/* -------------------------------------------------------------------------
 * Reading bookings
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_booking_fields' ) ) {
	/**
	 * A booking as a plain array, or null. Meta keys are an implementation
	 * detail: nothing outside this snippet should read them directly.
	 */
	function ioulia_booking_fields( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || IOULIA_BOOKING_TYPE !== $post->post_type ) {
			return null;
		}

		$programme_slug = (string) get_post_meta( $post->ID, '_ioulia_programme', true );
		$programme      = ioulia_workshop_programme( $programme_slug );

		return array(
			'id'              => $post->ID,
			'programme'       => $programme_slug,
			'programme_title' => $programme ? $programme['title'] : $programme_slug,
			'starts'          => (string) get_post_meta( $post->ID, '_ioulia_starts', true ),
			'ends'            => (string) get_post_meta( $post->ID, '_ioulia_ends', true ),
			'participants'    => max( 1, (int) get_post_meta( $post->ID, '_ioulia_participants', true ) ),
			'name'            => (string) get_post_meta( $post->ID, '_ioulia_name', true ),
			'email'           => (string) get_post_meta( $post->ID, '_ioulia_email', true ),
			'phone'           => (string) get_post_meta( $post->ID, '_ioulia_phone', true ),
			'note'            => (string) get_post_meta( $post->ID, '_ioulia_note', true ),
			'status'          => (string) get_post_meta( $post->ID, '_ioulia_status', true ),
			'consent_at'      => (string) get_post_meta( $post->ID, '_ioulia_consent_at', true ),
			'created'         => $post->post_date,
		);
	}
}

if ( ! function_exists( 'ioulia_get_bookings' ) ) {
	/**
	 * Bookings, soonest session first.
	 *
	 * $args accepts 'status' (confirmed, cancelled, or any), 'from' and 'until'
	 * as Y-m-d H:i:s strings, 'programme', and 'limit'.
	 */
	function ioulia_get_bookings( $args = array() ) {
		$args = array_merge(
			array(
				'status'    => 'confirmed',
				'from'      => '',
				'until'     => '',
				'programme' => '',
				'limit'     => 200,
			),
			$args
		);

		$meta = array( 'relation' => 'AND' );

		if ( 'any' !== $args['status'] ) {
			$meta[] = array(
				'key'   => '_ioulia_status',
				'value' => $args['status'],
			);
		}

		if ( '' !== $args['programme'] ) {
			$meta[] = array(
				'key'   => '_ioulia_programme',
				'value' => $args['programme'],
			);
		}

		if ( '' !== $args['from'] ) {
			$meta[] = array(
				'key'     => '_ioulia_starts',
				'value'   => $args['from'],
				'compare' => '>=',
				'type'    => 'DATETIME',
			);
		}

		if ( '' !== $args['until'] ) {
			$meta[] = array(
				'key'     => '_ioulia_starts',
				'value'   => $args['until'],
				'compare' => '<=',
				'type'    => 'DATETIME',
			);
		}

		$posts = get_posts(
			array(
				'post_type'        => IOULIA_BOOKING_TYPE,
				'post_status'      => 'private',
				'posts_per_page'   => (int) $args['limit'],
				'meta_key'         => '_ioulia_starts',
				'orderby'          => 'meta_value',
				'order'            => 'ASC',
				'meta_query'       => $meta,
				'suppress_filters' => false,
			)
		);

		return array_values( array_filter( array_map( 'ioulia_booking_fields', $posts ) ) );
	}
}

/* -------------------------------------------------------------------------
 * Seats
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_seats_taken' ) ) {
	/**
	 * How many people are already booked into one session. Cancelled bookings
	 * release their seats, so they are not counted.
	 */
	function ioulia_seats_taken( $programme_slug, $starts ) {
		$taken = 0;

		foreach ( ioulia_get_bookings( array( 'programme' => $programme_slug, 'from' => $starts, 'until' => $starts ) ) as $booking ) {
			$taken += $booking['participants'];
		}

		return $taken;
	}
}

if ( ! function_exists( 'ioulia_seats_left' ) ) {
	function ioulia_seats_left( $programme_slug, $starts ) {
		$programme = ioulia_workshop_programme( $programme_slug );

		if ( ! $programme ) {
			return 0;
		}

		return max( 0, (int) $programme['capacity'] - ioulia_seats_taken( $programme_slug, $starts ) );
	}
}

/* -------------------------------------------------------------------------
 * Creating a booking
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_create_booking' ) ) {
	/**
	 * Validate and store a booking, then email the studio and the visitor.
	 *
	 * Everything is re-checked here rather than trusted from the form: the
	 * programme exists, the session is one the programme actually runs, it is far
	 * enough away, and the seats are free. Returns the booking array or WP_Error.
	 */
	function ioulia_create_booking( $input ) {
		$settings  = ioulia_workshop_settings();
		$slug      = isset( $input['programme'] ) ? sanitize_key( $input['programme'] ) : '';
		$programme = ioulia_workshop_programme( $slug );

		if ( ! $programme || empty( $programme['active'] ) ) {
			return new WP_Error( 'ioulia_programme', 'Δεν βρήκαμε αυτό το πρόγραμμα.' );
		}

		$starts = isset( $input['starts'] ) ? trim( (string) $input['starts'] ) : '';
		$slot   = ioulia_match_session( $programme, $starts );

		if ( ! $slot ) {
			return new WP_Error( 'ioulia_session', 'Αυτή η ώρα δεν είναι διαθέσιμη για το συγκεκριμένο πρόγραμμα.' );
		}

		$earliest = ioulia_booking_earliest_start();

		if ( strtotime( $starts ) < strtotime( $earliest ) ) {
			return new WP_Error(
				'ioulia_lead_time',
				sprintf( 'Οι κρατήσεις γίνονται τουλάχιστον %d ημέρες πριν τη συνάντηση.', (int) $settings['lead_days'] )
			);
		}

		$participants = max( 1, (int) ( isset( $input['participants'] ) ? $input['participants'] : 1 ) );
		$left         = ioulia_seats_left( $slug, $starts );

		if ( $participants > $left ) {
			return new WP_Error(
				'ioulia_capacity',
				0 === $left
					? 'Αυτή η συνάντηση είναι πλήρης. Διάλεξε άλλη ημέρα ή ώρα.'
					: sprintf( 'Έμειναν %d θέσεις σε αυτή τη συνάντηση.', $left )
			);
		}

		$name  = sanitize_text_field( isset( $input['name'] ) ? $input['name'] : '' );
		$email = sanitize_email( isset( $input['email'] ) ? $input['email'] : '' );
		$phone = sanitize_text_field( isset( $input['phone'] ) ? $input['phone'] : '' );
		$note  = sanitize_textarea_field( isset( $input['note'] ) ? $input['note'] : '' );

		if ( '' === $name ) {
			return new WP_Error( 'ioulia_name', 'Γράψε το ονοματεπώνυμό σου.' );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'ioulia_email', 'Γράψε ένα έγκυρο email.' );
		}

		if ( empty( $input['consent'] ) ) {
			return new WP_Error( 'ioulia_consent', 'Χρειαζόμαστε τη συγκατάθεσή σου για να κρατήσουμε τα στοιχεία σου.' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => IOULIA_BOOKING_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s — %s — %s', $name, $programme['title'], ioulia_format_session( $starts ) ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$meta = array(
			'_ioulia_programme'    => $slug,
			'_ioulia_starts'       => $starts,
			'_ioulia_ends'         => $slot['ends'],
			'_ioulia_participants' => $participants,
			'_ioulia_name'         => $name,
			'_ioulia_email'        => $email,
			'_ioulia_phone'        => $phone,
			'_ioulia_note'         => $note,
			'_ioulia_status'       => 'confirmed',
			'_ioulia_consent_at'   => current_time( 'mysql', true ),
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$booking = ioulia_booking_fields( $post_id );

		ioulia_email_studio_new_booking( $booking );
		ioulia_email_visitor_confirmation( $booking );

		return $booking;
	}
}

if ( ! function_exists( 'ioulia_match_session' ) ) {
	/**
	 * Check a requested start against the programme's weekly sessions, and return
	 * the slot with its computed end. Null if the programme does not run then.
	 */
	function ioulia_match_session( $programme, $starts ) {
		$stamp = strtotime( $starts );

		if ( ! $stamp || $starts !== gmdate( 'Y-m-d H:i:s', $stamp ) ) {
			return null;
		}

		$weekday = (int) gmdate( 'N', $stamp );
		$time    = gmdate( 'H:i', $stamp );

		foreach ( $programme['sessions'] as $session ) {
			if ( (int) $session['day'] === $weekday && $session['start'] === $time ) {
				return array(
					'starts' => $starts,
					'ends'   => gmdate( 'Y-m-d ', $stamp ) . $session['end'] . ':00',
				);
			}
		}

		return null;
	}
}

if ( ! function_exists( 'ioulia_booking_earliest_start' ) ) {
	function ioulia_booking_earliest_start() {
		$settings = ioulia_workshop_settings();

		return gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( (int) $settings['lead_days'] * DAY_IN_SECONDS ) );
	}
}

/* -------------------------------------------------------------------------
 * Cancelling
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_cancel_booking' ) ) {
	/**
	 * Cancel a booking and tell the visitor. The seat is released immediately.
	 */
	function ioulia_cancel_booking( $post_id, $reason = '' ) {
		$booking = ioulia_booking_fields( $post_id );

		if ( ! $booking ) {
			return new WP_Error( 'ioulia_missing', 'Δεν βρήκαμε αυτή την κράτηση.' );
		}

		if ( 'cancelled' === $booking['status'] ) {
			return $booking;
		}

		update_post_meta( $post_id, '_ioulia_status', 'cancelled' );
		update_post_meta( $post_id, '_ioulia_cancelled_at', current_time( 'mysql', true ) );

		$booking['status'] = 'cancelled';

		ioulia_email_visitor_cancellation( $booking, sanitize_textarea_field( $reason ) );

		return $booking;
	}
}

/* -------------------------------------------------------------------------
 * Formatting
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_greek_weekdays' ) ) {
	function ioulia_greek_weekdays() {
		return array( 1 => 'Δευτέρα', 2 => 'Τρίτη', 3 => 'Τετάρτη', 4 => 'Πέμπτη', 5 => 'Παρασκευή', 6 => 'Σάββατο', 7 => 'Κυριακή' );
	}
}

if ( ! function_exists( 'ioulia_greek_months' ) ) {
	function ioulia_greek_months() {
		return array(
			1 => 'Ιανουαρίου', 2 => 'Φεβρουαρίου', 3 => 'Μαρτίου', 4 => 'Απριλίου',
			5 => 'Μαΐου', 6 => 'Ιουνίου', 7 => 'Ιουλίου', 8 => 'Αυγούστου',
			9 => 'Σεπτεμβρίου', 10 => 'Οκτωβρίου', 11 => 'Νοεμβρίου', 12 => 'Δεκεμβρίου',
		);
	}
}

if ( ! function_exists( 'ioulia_format_session' ) ) {
	/**
	 * "Σάββατο 6 Σεπτεμβρίου, 11:00". WordPress' own date_i18n is avoided so the
	 * wording does not follow the request locale: bookings are internal and stay
	 * Greek even when the visitor is browsing the English site.
	 */
	function ioulia_format_session( $starts, $with_weekday = true ) {
		$stamp = strtotime( $starts );

		if ( ! $stamp ) {
			return $starts;
		}

		$weekdays = ioulia_greek_weekdays();
		$months   = ioulia_greek_months();
		$day      = (int) gmdate( 'j', $stamp );
		$month    = $months[ (int) gmdate( 'n', $stamp ) ];
		$time     = gmdate( 'H:i', $stamp );

		$prefix = $with_weekday ? $weekdays[ (int) gmdate( 'N', $stamp ) ] . ' ' : '';

		return sprintf( '%s%d %s, %s', $prefix, $day, $month, $time );
	}
}

/* -------------------------------------------------------------------------
 * Email
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_studio_email' ) ) {
	function ioulia_studio_email() {
		$settings = ioulia_workshop_settings();

		return is_email( $settings['notify_email'] ) ? $settings['notify_email'] : get_option( 'admin_email' );
	}
}

if ( ! function_exists( 'ioulia_mail' ) ) {
	/**
	 * Plain text mail. Lines arrive as an array so the snippet needs no escapes.
	 */
	function ioulia_mail( $to, $subject, $lines, $reply_to = '' ) {
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		if ( is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		return wp_mail( $to, $subject, implode( chr( 10 ), $lines ), $headers );
	}
}

if ( ! function_exists( 'ioulia_booking_summary_lines' ) ) {
	function ioulia_booking_summary_lines( $booking ) {
		$lines = array(
			'Πρόγραμμα: ' . $booking['programme_title'],
			'Ημερομηνία: ' . ioulia_format_session( $booking['starts'] ),
			'Άτομα: ' . $booking['participants'],
		);

		if ( '' !== $booking['note'] ) {
			$lines[] = 'Σημείωση: ' . $booking['note'];
		}

		return $lines;
	}
}

if ( ! function_exists( 'ioulia_email_studio_new_booking' ) ) {
	function ioulia_email_studio_new_booking( $booking ) {
		$lines = array_merge(
			array( 'Νέα κράτηση.', '' ),
			ioulia_booking_summary_lines( $booking ),
			array(
				'',
				'Στοιχεία επικοινωνίας',
				'Όνομα: ' . $booking['name'],
				'Email: ' . $booking['email'],
				'Τηλέφωνο: ' . ( '' !== $booking['phone'] ? $booking['phone'] : '—' ),
			)
		);

		ioulia_mail(
			ioulia_studio_email(),
			sprintf( 'Κράτηση: %s — %s', $booking['programme_title'], ioulia_format_session( $booking['starts'] ) ),
			$lines,
			$booking['email']
		);
	}
}

if ( ! function_exists( 'ioulia_email_visitor_confirmation' ) ) {
	function ioulia_email_visitor_confirmation( $booking ) {
		$lines = array_merge(
			array( 'Γεια σου ' . $booking['name'] . ',', '', 'Η θέση σου κρατήθηκε.', '' ),
			ioulia_booking_summary_lines( $booking ),
			array(
				'',
				'Θα σε περιμένουμε στο εργαστήριο:',
				'Προμπονά 42, Άνω Πατήσια, 111 43 Αθήνα',
				'',
				'Αν χρειαστεί να αλλάξεις ή να ακυρώσεις, απάντησε σε αυτό το email.',
				'',
				'Ioulia Geraskli Ceramic Lab',
			)
		);

		ioulia_mail(
			$booking['email'],
			'Η κράτησή σου — ' . $booking['programme_title'],
			$lines,
			ioulia_studio_email()
		);
	}
}

if ( ! function_exists( 'ioulia_email_visitor_cancellation' ) ) {
	function ioulia_email_visitor_cancellation( $booking, $reason = '' ) {
		$lines = array( 'Γεια σου ' . $booking['name'] . ',', '', 'Η κράτησή σου ακυρώθηκε.', '' );
		$lines = array_merge( $lines, ioulia_booking_summary_lines( $booking ) );

		if ( '' !== $reason ) {
			$lines[] = '';
			$lines[] = $reason;
		}

		$lines = array_merge(
			$lines,
			array(
				'',
				'Αν θέλεις να κλείσεις άλλη ημέρα, απάντησε σε αυτό το email και το κανονίζουμε.',
				'',
				'Ioulia Geraskli Ceramic Lab',
			)
		);

		ioulia_mail(
			$booking['email'],
			'Ακύρωση κράτησης — ' . $booking['programme_title'],
			$lines,
			ioulia_studio_email()
		);
	}
}

/* -------------------------------------------------------------------------
 * Retention
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_schedule_booking_cleanup' ) ) {
	function ioulia_schedule_booking_cleanup() {
		if ( ! wp_next_scheduled( 'ioulia_purge_old_bookings' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ioulia_purge_old_bookings' );
		}
	}
	add_action( 'init', 'ioulia_schedule_booking_cleanup', 20 );
}

if ( ! function_exists( 'ioulia_purge_old_bookings' ) ) {
	/**
	 * Delete bookings whose session is far enough in the past. Personal details
	 * are not kept beyond what the studio needs them for.
	 */
	function ioulia_purge_old_bookings() {
		$settings = ioulia_workshop_settings();
		$months   = max( 1, (int) $settings['retention_months'] );
		$cutoff   = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $months * 30 * DAY_IN_SECONDS ) );

		foreach ( ioulia_get_bookings( array( 'status' => 'any', 'until' => $cutoff, 'limit' => 200 ) ) as $booking ) {
			wp_delete_post( $booking['id'], true );
		}
	}
	add_action( 'ioulia_purge_old_bookings', 'ioulia_purge_old_bookings' );
}
