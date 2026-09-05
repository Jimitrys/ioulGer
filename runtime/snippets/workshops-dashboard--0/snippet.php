<?php
/**
 * Bookings dashboard — Ioulia's own page, built for a phone.
 *
 * Lives at /kratiseis/. She sees her bookings as a list of days, taps a phone
 * number to call, and cancels with a message that reaches the visitor by email.
 *
 * Greek only, on purpose. This is an internal tool, so it is left out of the
 * English site and out of search engines entirely.
 *
 * ---------------------------------------------------------------------------
 * A PIN, not a WordPress account
 *
 * She was signing in through wp_login_form, which meant a username, a password
 * and a password manager to reach a page she opens on her phone between two
 * classes. It is a PIN now.
 *
 * A four-digit PIN is a weak secret and this page holds customers' names,
 * addresses of a sort, phone numbers and email addresses. So it is not just
 * stored and compared:
 *
 *   - Only a hash is kept, through wp_hash_password, never the digits.
 *   - Attempts are counted per address and the door shuts for fifteen minutes
 *     after five wrong ones, which is what makes ten thousand combinations
 *     unreachable rather than an afternoon's work.
 *   - The session is a random token in a cookie, checked against a transient.
 *     The PIN itself is never stored anywhere the browser can reach.
 *   - The page is never cached and never indexed.
 *
 * That is a fair trade for a tool one person opens on a phone. It is not the
 * right protection for anything more than this. A longer PIN costs nothing:
 * the field takes up to eight digits.
 *
 * The starting PIN is 1234 and it is meant to be changed - there is a button
 * on the page for it.
 * ---------------------------------------------------------------------------
 *
 * Actions go over AJAX or a plain form post. Site Studio's snippet validator
 * blocks exit, so a post-redirect-get round trip is not available to us; the
 * forms are written to be harmless when a phone re-sends them.
 *
 * Requires the "workshops data" and "workshops bookings" snippets.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! defined( 'IOULIA_DASHBOARD_SLUG' ) ) {
	define( 'IOULIA_DASHBOARD_SLUG', 'kratiseis' );
}

if ( ! defined( 'IOULIA_DASHBOARD_COOKIE' ) ) {
	define( 'IOULIA_DASHBOARD_COOKIE', 'ioulia_kratiseis' );
}

if ( ! function_exists( 'ioulia_dashboard_capability' ) ) {
	/**
	 * A signed-in editor or administrator still gets in without the PIN, so a
	 * forgotten PIN is never a locked door.
	 */
	function ioulia_dashboard_capability() {
		return apply_filters( 'ioulia_dashboard_capability', 'edit_others_posts' );
	}
}

/* -------------------------------------------------------------------------
 * The PIN
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_pin_hash' ) ) {
	function ioulia_dashboard_pin_hash() {
		$hash = (string) get_option( 'ioulia_dashboard_pin', '' );

		if ( '' === $hash ) {
			$hash = wp_hash_password( '1234' );
			update_option( 'ioulia_dashboard_pin', $hash, false );
		}

		return $hash;
	}
}

if ( ! function_exists( 'ioulia_dashboard_pin_matches' ) ) {
	function ioulia_dashboard_pin_matches( $pin ) {
		return wp_check_password( (string) $pin, ioulia_dashboard_pin_hash() );
	}
}

if ( ! function_exists( 'ioulia_dashboard_set_pin' ) ) {
	/**
	 * Four to eight digits. Longer is better and costs the same to type once.
	 */
	function ioulia_dashboard_set_pin( $pin ) {
		$pin = preg_replace( '#[^0-9]#', '', (string) $pin );

		if ( strlen( $pin ) < 4 || strlen( $pin ) > 8 ) {
			return new WP_Error( 'ioulia_pin_length', 'Το PIN θέλει από 4 έως 8 ψηφία.' );
		}

		update_option( 'ioulia_dashboard_pin', wp_hash_password( $pin ), false );

		return true;
	}
}

/* -------------------------------------------------------------------------
 * Attempts
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_attempt_key' ) ) {
	/**
	 * Counting attempts needs to tell one caller from another without keeping
	 * their address. A salted hash does that and means nothing on its own.
	 */
	function ioulia_dashboard_attempt_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

		return 'ioulia_pin_' . md5( wp_salt( 'nonce' ) . $ip );
	}
}

if ( ! function_exists( 'ioulia_dashboard_locked' ) ) {
	function ioulia_dashboard_locked() {
		return (int) get_transient( ioulia_dashboard_attempt_key() ) >= 5;
	}
}

if ( ! function_exists( 'ioulia_dashboard_note_failure' ) ) {
	function ioulia_dashboard_note_failure() {
		$key = ioulia_dashboard_attempt_key();

		set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
	}
}

/* -------------------------------------------------------------------------
 * The session
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_session_start' ) ) {
	/**
	 * The cookie holds a random token and nothing else. What it means is kept
	 * server side, so a stolen cookie stops working the moment it is signed out
	 * or the transient expires.
	 */
	function ioulia_dashboard_session_start() {
		$token = wp_generate_password( 40, false, false );

		set_transient( 'ioulia_dash_' . hash( 'sha256', $token ), 1, 30 * DAY_IN_SECONDS );

		setcookie(
			IOULIA_DASHBOARD_COOKIE,
			$token,
			array(
				'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		$_COOKIE[ IOULIA_DASHBOARD_COOKIE ] = $token;
	}
}

if ( ! function_exists( 'ioulia_dashboard_session_end' ) ) {
	function ioulia_dashboard_session_end() {
		if ( isset( $_COOKIE[ IOULIA_DASHBOARD_COOKIE ] ) ) {
			$token = sanitize_text_field( wp_unslash( $_COOKIE[ IOULIA_DASHBOARD_COOKIE ] ) );
			delete_transient( 'ioulia_dash_' . hash( 'sha256', $token ) );
		}

		setcookie(
			IOULIA_DASHBOARD_COOKIE,
			'',
			array(
				'expires'  => time() - DAY_IN_SECONDS,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		unset( $_COOKIE[ IOULIA_DASHBOARD_COOKIE ] );
	}
}

if ( ! function_exists( 'ioulia_dashboard_authed' ) ) {
	function ioulia_dashboard_authed() {
		if ( is_user_logged_in() && current_user_can( ioulia_dashboard_capability() ) ) {
			return true;
		}

		if ( empty( $_COOKIE[ IOULIA_DASHBOARD_COOKIE ] ) ) {
			return false;
		}

		$token = sanitize_text_field( wp_unslash( $_COOKIE[ IOULIA_DASHBOARD_COOKIE ] ) );

		return (bool) get_transient( 'ioulia_dash_' . hash( 'sha256', $token ) );
	}
}

/* -------------------------------------------------------------------------
 * The page
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_ensure_dashboard_page' ) ) {
	/**
	 * Create the page once, then remember its id so this costs nothing per request.
	 */
	function ioulia_ensure_dashboard_page() {
		$stored = (int) get_option( 'ioulia_dashboard_page_id', 0 );

		if ( $stored && 'page' === get_post_type( $stored ) ) {
			return;
		}

		$existing = get_page_by_path( IOULIA_DASHBOARD_SLUG, OBJECT, 'page' );

		if ( $existing ) {
			update_option( 'ioulia_dashboard_page_id', $existing->ID, false );
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => 'Κρατήσεις',
				'post_name'      => IOULIA_DASHBOARD_SLUG,
				'post_content'   => '[ioulia_bookings_dashboard]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( 'ioulia_dashboard_page_id', (int) $page_id, false );
		}
	}
	add_action( 'init', 'ioulia_ensure_dashboard_page', 30 );
}

if ( ! function_exists( 'ioulia_is_dashboard' ) ) {
	function ioulia_is_dashboard() {
		return ! is_admin() && is_page( IOULIA_DASHBOARD_SLUG );
	}
}

if ( ! function_exists( 'ioulia_dashboard_keep_private' ) ) {
	/**
	 * An internal tool has no business in search results or in a cache. The
	 * site's own header and footer stay: it is one of her pages, and looking
	 * like the rest of the site is the point.
	 */
	function ioulia_dashboard_keep_private() {
		if ( ! ioulia_is_dashboard() ) {
			return;
		}

		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter( 'show_admin_bar', '__return_false' );
		nocache_headers();
	}
	add_action( 'template_redirect', 'ioulia_dashboard_keep_private', 1 );
}

if ( ! function_exists( 'ioulia_dashboard_not_translatable' ) ) {
	/**
	 * Keep /en/kratiseis/ from existing at all.
	 */
	function ioulia_dashboard_not_translatable( $translatable, $path ) {
		return 0 === strpos( ltrim( (string) $path, '/' ), IOULIA_DASHBOARD_SLUG ) ? false : $translatable;
	}
	add_filter( 'ioulia_path_is_translatable', 'ioulia_dashboard_not_translatable', 10, 2 );
	add_filter( 'igc_i18n_translatable_path', 'ioulia_dashboard_not_translatable', 10, 2 );
}

/* -------------------------------------------------------------------------
 * Form posts
 *
 * Handled on template_redirect because a cookie has to be set before anything
 * is printed. The outcome is left in a static for the shortcode to render,
 * rather than redirecting: the validator blocks exit, so a redirect here would
 * send a header and then carry on rendering the page underneath it.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_notice' ) ) {
	function ioulia_dashboard_notice( $set = null ) {
		static $notice = array();

		if ( null !== $set ) {
			$notice = $set;
		}

		return $notice;
	}
}

if ( ! function_exists( 'ioulia_dashboard_handle_post' ) ) {
	function ioulia_dashboard_handle_post() {
		if ( ! ioulia_is_dashboard() || empty( $_POST['iwd_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['iwd_action'] ) );
		$nonce  = isset( $_POST['iwd_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['iwd_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ioulia_dashboard_' . $action ) ) {
			ioulia_dashboard_notice( array( 'tone' => 'error', 'text' => 'Η σελίδα έληξε. Δοκίμασε ξανά.' ) );
			return;
		}

		if ( 'signout' === $action ) {
			ioulia_dashboard_session_end();
			ioulia_dashboard_notice( array( 'tone' => 'ok', 'text' => 'Αποσυνδέθηκες.' ) );
			return;
		}

		if ( 'signin' === $action ) {
			if ( ioulia_dashboard_locked() ) {
				ioulia_dashboard_notice( array( 'tone' => 'error', 'text' => 'Πολλές λάθος προσπάθειες. Δοκίμασε ξανά σε λίγο.' ) );
				return;
			}

			$pin = isset( $_POST['iwd_pin'] ) ? preg_replace( '#[^0-9]#', '', (string) wp_unslash( $_POST['iwd_pin'] ) ) : '';

			if ( '' !== $pin && ioulia_dashboard_pin_matches( $pin ) ) {
				delete_transient( ioulia_dashboard_attempt_key() );
				ioulia_dashboard_session_start();
				return;
			}

			ioulia_dashboard_note_failure();
			ioulia_dashboard_notice( array( 'tone' => 'error', 'text' => 'Λάθος PIN.' ) );
			return;
		}

		if ( 'programmes' === $action ) {
			if ( ! ioulia_dashboard_authed() || ! function_exists( 'ioulia_programmes_handle_post' ) ) {
				return;
			}

			$saved = ioulia_programmes_handle_post();

			if ( is_wp_error( $saved ) ) {
				ioulia_dashboard_notice( array( 'tone' => 'error', 'text' => $saved->get_error_message() ) );
				return;
			}

			ioulia_dashboard_notice( array( 'tone' => 'ok', 'text' => 'Τα προγράμματα αποθηκεύτηκαν. Η σελίδα workshops και το popup κρατήσεων τα δείχνουν ήδη.' ) );
			return;
		}

		if ( 'pin' === $action ) {
			if ( ! ioulia_dashboard_authed() ) {
				return;
			}

			$current = isset( $_POST['iwd_pin_current'] ) ? (string) wp_unslash( $_POST['iwd_pin_current'] ) : '';

			if ( ! ioulia_dashboard_pin_matches( preg_replace( '#[^0-9]#', '', $current ) ) ) {
				ioulia_dashboard_notice( array( 'tone' => 'error', 'text' => 'Το τρέχον PIN δεν είναι σωστό.' ) );
				return;
			}

			$next   = isset( $_POST['iwd_pin_new'] ) ? (string) wp_unslash( $_POST['iwd_pin_new'] ) : '';
			$result = ioulia_dashboard_set_pin( $next );

			if ( is_wp_error( $result ) ) {
				ioulia_dashboard_notice( array( 'tone' => 'error', 'text' => $result->get_error_message() ) );
				return;
			}

			ioulia_dashboard_notice( array( 'tone' => 'ok', 'text' => 'Το PIN άλλαξε.' ) );
		}
	}
	add_action( 'template_redirect', 'ioulia_dashboard_handle_post', 2 );
}

/* -------------------------------------------------------------------------
 * Cancelling, over AJAX
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_cancel_ajax' ) ) {
	function ioulia_dashboard_cancel_ajax() {
		check_ajax_referer( 'ioulia_dashboard', 'nonce' );

		if ( ! ioulia_dashboard_authed() ) {
			wp_send_json_error( array( 'message' => 'Η σύνδεση έληξε. Ξαναμπές με το PIN.' ), 403 );
		}

		$booking_id = isset( $_POST['booking'] ) ? absint( $_POST['booking'] ) : 0;
		$reason     = isset( $_POST['reason'] ) ? (string) wp_unslash( $_POST['reason'] ) : '';
		$cancelled  = ioulia_cancel_booking( $booking_id, $reason, 'studio' );

		if ( is_wp_error( $cancelled ) ) {
			wp_send_json_error( array( 'message' => $cancelled->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => 'Η κράτηση ακυρώθηκε και στάλθηκε email στον πελάτη.',
				'id'      => $booking_id,
			)
		);
	}
	add_action( 'wp_ajax_ioulia_cancel_booking', 'ioulia_dashboard_cancel_ajax' );
	add_action( 'wp_ajax_nopriv_ioulia_cancel_booking', 'ioulia_dashboard_cancel_ajax' );
}

/* -------------------------------------------------------------------------
 * How full each day is
 *
 * The list answers "who is coming on Saturday". It cannot answer "which of the
 * next three weeks is empty", which is the question you ask when you are
 * deciding whether to open another session or take a day off. That is what the
 * calendar is for, and this is the number behind each of its dots.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_day_capacity' ) ) {
	/**
	 * Every seat the studio offers on a given weekday, across all the programmes
	 * that run on it. Sessions are weekly, so this depends on the weekday alone.
	 */
	function ioulia_dashboard_day_capacity( $weekday ) {
		static $cache = array();

		if ( isset( $cache[ $weekday ] ) ) {
			return $cache[ $weekday ];
		}

		$seats = 0;

		foreach ( ioulia_workshop_active_programmes() as $programme ) {
			foreach ( $programme['sessions'] as $session ) {
				if ( (int) $session['day'] === (int) $weekday ) {
					$seats += (int) $programme['capacity'];
				}
			}
		}

		$cache[ $weekday ] = $seats;

		return $seats;
	}
}

if ( ! function_exists( 'ioulia_dashboard_load_map' ) ) {
	/**
	 * date => how full it is, for the whole window the calendar can show.
	 *
	 * 'state' is what the dot means:
	 *   free  - under a third taken, or nothing booked at all
	 *   some  - filling up
	 *   full  - no seat left
	 *   shut  - the studio runs nothing that weekday, so there is no dot
	 */
	function ioulia_dashboard_load_map( $bookings, $days = 70 ) {
		$taken = array();

		foreach ( $bookings as $booking ) {
			if ( 'cancelled' === $booking['status'] ) {
				continue;
			}

			$date = substr( $booking['starts'], 0, 10 );

			$taken[ $date ] = ( isset( $taken[ $date ] ) ? $taken[ $date ] : 0 ) + (int) $booking['participants'];
		}

		$map   = array();
		$start = current_time( 'timestamp' );

		for ( $i = 0; $i < $days; $i++ ) {
			$stamp    = $start + ( $i * DAY_IN_SECONDS );
			$date     = gmdate( 'Y-m-d', $stamp );
			$capacity = ioulia_dashboard_day_capacity( (int) gmdate( 'N', $stamp ) );
			$booked   = isset( $taken[ $date ] ) ? $taken[ $date ] : 0;

			if ( $capacity < 1 ) {
				$map[ $date ] = array( 'state' => 'shut', 'taken' => $booked, 'capacity' => 0 );
				continue;
			}

			$ratio = $booked / $capacity;

			if ( $booked >= $capacity ) {
				$state = 'full';
			} elseif ( $ratio >= 0.34 ) {
				$state = 'some';
			} else {
				$state = 'free';
			}

			$map[ $date ] = array( 'state' => $state, 'taken' => $booked, 'capacity' => $capacity );
		}

		return $map;
	}
}

if ( ! function_exists( 'ioulia_dashboard_calendar_words' ) ) {
	function ioulia_dashboard_calendar_words() {
		/* ioulia_greek_months() is the genitive, because it exists to say
		   "8 Σεπτεμβρίου". A calendar heading names the month instead, so it
		   needs the nominative: "Σεπτέμβριος 2026", not "Σεπτεμβρίου 2026". */
		return array(
			'months'   => array(
				'Ιανουάριος', 'Φεβρουάριος', 'Μάρτιος', 'Απρίλιος',
				'Μάιος', 'Ιούνιος', 'Ιούλιος', 'Αύγουστος',
				'Σεπτέμβριος', 'Οκτώβριος', 'Νοέμβριος', 'Δεκέμβριος',
			),
			'weekdays' => array( 'Δε', 'Τρ', 'Τε', 'Πε', 'Πα', 'Σα', 'Κυ' ),
			'today'    => gmdate( 'Y-m-d', current_time( 'timestamp' ) ),
		);
	}
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_is_demo' ) ) {
	/**
	 * /kratiseis/?demo=1 draws the page from invented bookings so the calendar
	 * and the cards can be judged before there is a season's worth of real ones.
	 *
	 * Nothing is written and nothing is read: the demo list is built in memory
	 * and thrown away with the request.
	 *
	 * The page carries no badge saying so, because it is shown to clients. What
	 * keeps it from being mistaken for the real diary is the URL: ?demo=1 has to
	 * be typed on purpose, and it is behind the same PIN as everything else, so
	 * nobody arrives here by accident and /kratiseis/ on its own is always the
	 * real one.
	 */
	function ioulia_dashboard_is_demo() {
		return ! empty( $_GET['demo'] ) && ioulia_dashboard_authed();
	}
}

if ( ! function_exists( 'ioulia_dashboard_demo_bookings' ) ) {
	function ioulia_dashboard_demo_bookings() {
		$programmes = ioulia_workshop_active_programmes();

		if ( empty( $programmes ) ) {
			return array();
		}

		$slugs = array_keys( $programmes );
		$today = current_time( 'timestamp' );

		/* Days ahead, then who is on them. Written out rather than randomised so
		   the picture is the same every time it is looked at: an empty start of
		   the week, a Saturday that is full, and a couple in between. */
		$plan = array(
			array( 1, '11:00', 'Μαρία Παπαδοπούλου', 2, 'Είμαστε δύο, πρώτη φορά.' ),
			array( 2, '18:00', 'Γιώργος Αντωνίου', 1, '' ),
			array( 3, '11:00', 'Ελένη Κωνσταντίνου', 4, 'Παιδικά γενέθλια, αν γίνεται νωρίς.' ),
			array( 3, '18:00', 'Νίκος Δ.', 2, '' ),
			array( 5, '11:00', 'Άννα Βλάχου', 3, '' ),
			array( 5, '11:00', 'Στέλιος Μ.', 2, '' ),
			array( 5, '18:00', 'Ζωή Παπαδάκη', 3, 'Δώρο για την αδερφή μου.' ),
			array( 5, '18:00', 'Κατερίνα Λ.', 5, '' ),
			array( 6, '11:00', 'Θανάσης Ρ.', 1, '' ),
			array( 8, '18:00', 'Δήμητρα Σ.', 2, 'Πρώτη φορά σε τροχό.' ),
			array( 10, '11:00', 'Παύλος Γ.', 2, '' ),
			array( 11, '11:00', 'Ιωάννα Κ.', 6, 'Ομάδα από τη δουλειά.' ),
		);

		$bookings = array();
		$id       = 900001;

		foreach ( $plan as $index => $row ) {
			list( $offset, $time, $name, $people, $note ) = $row;

			$slug      = $slugs[ $index % count( $slugs ) ];
			$programme = $programmes[ $slug ];
			$date      = gmdate( 'Y-m-d', $today + ( $offset * DAY_IN_SECONDS ) );

			$bookings[] = array(
				'id'              => $id,
				'programme'       => $slug,
				'programme_title' => $programme['title'],
				'starts'          => $date . ' ' . $time . ':00',
				'ends'            => $date . ' ' . $time . ':00',
				'participants'    => $people,
				'name'            => $name,
				'email'           => 'demo@example.com',
				'phone'           => '6940000000',
				'note'            => $note,
				'status'          => 'confirmed',
				'consent_at'      => '',
				'cancel_token'    => '',
				'created'         => '',
			);

			$id++;
		}

		return $bookings;
	}
}

if ( ! function_exists( 'ioulia_dashboard_group_by_day' ) ) {
	function ioulia_dashboard_group_by_day( $bookings ) {
		$days = array();

		foreach ( $bookings as $booking ) {
			$key = substr( $booking['starts'], 0, 10 );

			if ( ! isset( $days[ $key ] ) ) {
				$days[ $key ] = array();
			}

			$days[ $key ][] = $booking;
		}

		return $days;
	}
}

if ( ! function_exists( 'ioulia_dashboard_day_label' ) ) {
	function ioulia_dashboard_day_label( $date ) {
		$stamp    = strtotime( $date . ' 00:00:00' );
		$today    = gmdate( 'Y-m-d', current_time( 'timestamp' ) );
		$tomorrow = gmdate( 'Y-m-d', current_time( 'timestamp' ) + DAY_IN_SECONDS );

		if ( $date === $today ) {
			return 'Σήμερα';
		}

		if ( $date === $tomorrow ) {
			return 'Αύριο';
		}

		$weekdays = ioulia_greek_weekdays();
		$months   = ioulia_greek_months();

		return sprintf(
			'%s %d %s',
			$weekdays[ (int) gmdate( 'N', $stamp ) ],
			(int) gmdate( 'j', $stamp ),
			$months[ (int) gmdate( 'n', $stamp ) ]
		);
	}
}

if ( ! function_exists( 'ioulia_dashboard_card' ) ) {
	function ioulia_dashboard_card( $booking ) {
		$programme = ioulia_workshop_programme( $booking['programme'] );
		$capacity  = $programme ? (int) $programme['capacity'] : 0;
		$taken     = ioulia_seats_taken( $booking['programme'], $booking['starts'] );
		$cancelled = 'cancelled' === $booking['status'];
		$phone     = preg_replace( '#[^0-9+]#', '', $booking['phone'] );
		?>
		<article class="iwd-card<?php echo $cancelled ? ' is-cancelled' : ''; ?>" data-booking="<?php echo esc_attr( $booking['id'] ); ?>">
			<div class="iwd-card__top">
				<span class="iwd-pill"><?php echo esc_html( substr( $booking['starts'], 11, 5 ) ); ?></span>
				<?php if ( $capacity ) : ?>
					<span class="iwd-card__seats"><?php echo esc_html( $taken . '/' . $capacity ); ?> θέσεις</span>
				<?php endif; ?>
				<?php if ( $cancelled ) : ?>
					<span class="iwd-card__flag">Ακυρώθηκε</span>
				<?php endif; ?>
			</div>

			<h3 class="iwd-card__name"><?php echo esc_html( $booking['name'] ); ?></h3>

			<p class="iwd-card__meta">
				<?php echo esc_html( $booking['programme_title'] ); ?>
				<span aria-hidden="true">·</span>
				<?php echo esc_html( 1 === $booking['participants'] ? '1 άτομο' : $booking['participants'] . ' άτομα' ); ?>
			</p>

			<?php if ( '' !== $booking['note'] ) : ?>
				<p class="iwd-card__note"><?php echo esc_html( $booking['note'] ); ?></p>
			<?php endif; ?>

			<div class="iwd-card__actions">
				<?php if ( '' !== $phone ) : ?>
					<a class="ioulia-btn ioulia-btn--outline ioulia-btn--sm" href="tel:<?php echo esc_attr( $phone ); ?>">Κλήση</a>
				<?php endif; ?>
				<a class="ioulia-btn ioulia-btn--outline ioulia-btn--sm" href="mailto:<?php echo esc_attr( $booking['email'] ); ?>">Email</a>
				<?php if ( ! $cancelled ) : ?>
					<button type="button" class="ioulia-btn ioulia-btn--outline ioulia-btn--sm iwd-danger" data-iwd-cancel>Ακύρωση</button>
				<?php endif; ?>
			</div>

			<div class="iwd-cancel" hidden>
				<label for="iwd-reason-<?php echo esc_attr( $booking['id'] ); ?>">Μήνυμα στον πελάτη (προαιρετικό)</label>
				<textarea id="iwd-reason-<?php echo esc_attr( $booking['id'] ); ?>" rows="2" data-iwd-reason placeholder="π.χ. το εργαστήριο είναι κλειστό εκείνη τη μέρα"></textarea>
				<div class="iwd-cancel__buttons">
					<button type="button" class="ioulia-btn ioulia-btn--outline ioulia-btn--sm" data-iwd-abort>Πίσω</button>
					<button type="button" class="ioulia-btn ioulia-btn--sm" data-iwd-confirm>Ακύρωση και email</button>
				</div>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'ioulia_dashboard_list' ) ) {
	function ioulia_dashboard_list( $bookings, $empty ) {
		if ( empty( $bookings ) ) {
			echo '<p class="iwd-empty">' . esc_html( $empty ) . '</p>';
			return;
		}

		foreach ( ioulia_dashboard_group_by_day( $bookings ) as $date => $day ) {
			?>
			<section class="iwd-day" id="iwd-day-<?php echo esc_attr( $date ); ?>" data-iwd-day="<?php echo esc_attr( $date ); ?>">
				<h2 class="iwd-day__label"><?php echo esc_html( ioulia_dashboard_day_label( $date ) ); ?></h2>
				<?php foreach ( $day as $booking ) : ?>
					<?php ioulia_dashboard_card( $booking ); ?>
				<?php endforeach; ?>
			</section>
			<?php
		}
	}
}

if ( ! function_exists( 'ioulia_dashboard_flash' ) ) {
	function ioulia_dashboard_flash() {
		$notice = ioulia_dashboard_notice();

		if ( empty( $notice['text'] ) ) {
			return;
		}

		printf(
			'<p class="iwd-notice iwd-notice--%1$s">%2$s</p>',
			esc_attr( 'error' === $notice['tone'] ? 'error' : 'ok' ),
			esc_html( $notice['text'] )
		);
	}
}

if ( ! function_exists( 'ioulia_dashboard_shortcode' ) ) {
	function ioulia_dashboard_shortcode() {
		ob_start();

		if ( ! ioulia_dashboard_authed() ) {
			ioulia_dashboard_gate();

			return ob_get_clean();
		}

		$now  = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		$demo = ioulia_dashboard_is_demo();

		if ( $demo ) {
			$upcoming = ioulia_dashboard_demo_bookings();
			$past     = array();
			$off      = array();
		} else {
			$upcoming = ioulia_get_bookings( array( 'from' => $now, 'limit' => 300 ) );
			$past     = array_reverse( ioulia_get_bookings( array( 'until' => $now, 'limit' => 100 ) ) );
			$off      = ioulia_get_bookings( array( 'status' => 'cancelled', 'limit' => 100 ) );
		}

		$people = 0;

		foreach ( $upcoming as $booking ) {
			$people += $booking['participants'];
		}

		$load = ioulia_dashboard_load_map( $upcoming );
		?>
		<div class="iwd" data-iwd
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_dashboard' ) ); ?>"
			<?php echo $demo ? ' data-demo="1"' : ''; ?>
			data-load="<?php echo esc_attr( wp_json_encode( $load ) ); ?>"
			data-words="<?php echo esc_attr( wp_json_encode( ioulia_dashboard_calendar_words() ) ); ?>">

			<header class="iwd-head">
				<h1 class="iwd-head__title">Κρατήσεις</h1>
				<p class="iwd-head__count"><?php echo esc_html( sprintf( '%d κρατήσεις · %d άτομα', count( $upcoming ), $people ) ); ?></p>
			</header>

			<?php ioulia_dashboard_flash(); ?>
			<p class="iwd-notice iwd-notice--ok" data-iwd-flash hidden></p>

			<nav class="iwd-tabs" role="tablist" aria-label="Κρατήσεις">
				<button type="button" class="iwd-tab is-current" data-iwd-tab="upcoming" role="tab" aria-selected="true">Επόμενες</button>
				<button type="button" class="iwd-tab" data-iwd-tab="calendar" role="tab" aria-selected="false">Ημερολόγιο</button>
				<button type="button" class="iwd-tab" data-iwd-tab="past" role="tab" aria-selected="false">Περασμένες</button>
				<button type="button" class="iwd-tab" data-iwd-tab="cancelled" role="tab" aria-selected="false">Ακυρωμένες</button>
				<?php if ( function_exists( 'ioulia_programmes_panel' ) ) : ?>
					<button type="button" class="iwd-tab" data-iwd-tab="programmes" role="tab" aria-selected="false">Προγράμματα</button>
				<?php endif; ?>
			</nav>

			<div class="iwd-panel" data-iwd-panel="upcoming">
				<?php ioulia_dashboard_list( $upcoming, 'Καμία επόμενη κράτηση.' ); ?>
			</div>
			<div class="iwd-panel" data-iwd-panel="calendar" hidden>
				<div class="iwd-cal" data-iwd-calendar></div>

				<ul class="iwd-key">
					<li><span class="iwd-dot iwd-dot--free"></span>Λίγες κρατήσεις</li>
					<li><span class="iwd-dot iwd-dot--some"></span>Γεμίζει</li>
					<li><span class="iwd-dot iwd-dot--full"></span>Γεμάτη</li>
					<li class="iwd-key__note">Οι σβηστές μέρες δεν έχουν καμία κράτηση.</li>
				</ul>
			</div>

			<div class="iwd-panel" data-iwd-panel="past" hidden>
				<?php ioulia_dashboard_list( $past, 'Καμία περασμένη κράτηση.' ); ?>
			</div>
			<div class="iwd-panel" data-iwd-panel="cancelled" hidden>
				<?php ioulia_dashboard_list( $off, 'Καμία ακυρωμένη κράτηση.' ); ?>
			</div>

			<?php if ( function_exists( 'ioulia_programmes_panel' ) ) : ?>
				<div class="iwd-panel" data-iwd-panel="programmes" hidden>
					<?php echo ioulia_programmes_panel(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built in the editor snippet. ?>
				</div>
			<?php endif; ?>

			<footer class="iwd-foot">
				<details class="iwd-pin">
					<summary class="iwd-link">Αλλαγή PIN</summary>
					<form class="iwd-pin__form" method="post">
						<?php wp_nonce_field( 'ioulia_dashboard_pin', 'iwd_nonce' ); ?>
						<input type="hidden" name="iwd_action" value="pin">
						<label for="iwd-pin-current">Τρέχον PIN</label>
						<input id="iwd-pin-current" name="iwd_pin_current" type="password" inputmode="numeric" autocomplete="current-password" required>
						<label for="iwd-pin-new">Νέο PIN (4 έως 8 ψηφία)</label>
						<input id="iwd-pin-new" name="iwd_pin_new" type="password" inputmode="numeric" autocomplete="new-password" minlength="4" maxlength="8" required>
						<button class="ioulia-btn" type="submit">Αποθήκευση</button>
					</form>
				</details>

				<form method="post" class="iwd-signout">
					<?php wp_nonce_field( 'ioulia_dashboard_signout', 'iwd_nonce' ); ?>
					<input type="hidden" name="iwd_action" value="signout">
					<button type="submit" class="iwd-link">Έξοδος</button>
				</form>
			</footer>
		</div>
		<?php
		ioulia_dashboard_assets();

		return ob_get_clean();
	}
	add_shortcode( 'ioulia_bookings_dashboard', 'ioulia_dashboard_shortcode' );
}

if ( ! function_exists( 'ioulia_dashboard_gate' ) ) {
	function ioulia_dashboard_gate() {
		$locked = ioulia_dashboard_locked();
		?>
		<div class="iwd iwd--gate">
			<header class="iwd-head">
				<h1 class="iwd-head__title">Κρατήσεις</h1>
				<p class="iwd-head__count">Βάλε το PIN για να δεις τις κρατήσεις.</p>
			</header>

			<?php ioulia_dashboard_flash(); ?>

			<?php if ( ! $locked ) : ?>
				<form class="iwd-gate__form" method="post">
					<?php wp_nonce_field( 'ioulia_dashboard_signin', 'iwd_nonce' ); ?>
					<input type="hidden" name="iwd_action" value="signin">
					<label for="iwd-pin">PIN</label>
					<input
						id="iwd-pin"
						name="iwd_pin"
						type="password"
						inputmode="numeric"
						autocomplete="current-password"
						autofocus
						required>
					<button class="ioulia-btn" type="submit">Σύνδεση</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
		ioulia_dashboard_assets();
	}
}

/* -------------------------------------------------------------------------
 * Styles and behaviour
 *
 * The page sits inside the site's own shell now - header, footer, tokens and
 * button system all come from the global stylesheet, so what is here is only
 * what makes this a list of bookings. It used to hide the header and footer
 * and re-declare a palette of its own.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_assets' ) ) {
	function ioulia_dashboard_assets() {
		static $printed = false;

		if ( $printed ) {
			return;
		}

		$printed = true;
		?>
<style id="ioulia-dashboard-css">
	.iwd {
		--iwd-line: var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		--iwd-muted: var(--ioulia-ink-65, rgba(43, 43, 43, .65));
		--iwd-danger: #8F3939;

		box-sizing: border-box;
		width: 100%;
		max-width: 720px;
		margin-inline: auto;
		color: var(--ioulia-ink);
	}

	.iwd *, .iwd *::before, .iwd *::after { box-sizing: border-box; }

	.iwd-head { margin-bottom: clamp(1.75rem, 4vh, 2.5rem); }

	.iwd-head__title {
		margin: 0 0 .5rem;
		font-size: var(--ioulia-h2);
		font-weight: 400;
		line-height: .98;
		letter-spacing: -.045em;
	}

	.iwd-head__count {
		margin: 0;
		color: var(--iwd-muted);
		font-size: var(--ioulia-small);
		font-weight: 500;
	}

	/* --- Notices --------------------------------------------------------- */

	.iwd-notice {
		margin: 0 0 1.25rem;
		padding: .85rem 1.1rem;
		border: 1px solid var(--iwd-line);
		border-radius: 12px;
		font-size: var(--ioulia-small);
		font-weight: 500;
		line-height: 1.45;
	}
	.iwd-notice[hidden] { display: none; }
	.iwd-notice--error { border-color: rgba(143, 57, 57, .3); background: rgba(143, 57, 57, .06); color: var(--iwd-danger); }
	.iwd-notice--ok { background: var(--ioulia-ink-07, rgba(43, 43, 43, .07)); }

	/* --- Tabs. Words with a rule under the current one, not three buttons
	       competing with the one button on the page. ---------------------- */

	.iwd-tabs {
		display: flex;
		margin-bottom: clamp(1.5rem, 3vh, 2rem);
		padding-bottom: .85rem;
		border-bottom: 1px solid var(--iwd-line);
		gap: clamp(1.1rem, 4vw, 1.75rem);
		overflow-x: auto;
		overflow-y: hidden;
		touch-action: pan-x;
		scrollbar-width: none;
	}
	.iwd-tabs::-webkit-scrollbar { display: none; }

	.iwd-tab {
		appearance: none;
		padding: 0;
		border: 0;
		background: none;
		color: var(--iwd-muted);
		font: inherit;
		font-size: var(--ioulia-small);
		font-weight: 500;
		white-space: nowrap;
		cursor: pointer;
		transition: color .22s ease;
	}
	.iwd-tab:hover { color: var(--ioulia-ink); }
	.iwd-tab.is-current {
		color: var(--ioulia-ink);
		text-decoration: underline;
		text-underline-offset: 6px;
		text-decoration-thickness: 1px;
	}

	/* --- Days and cards -------------------------------------------------- */

	.iwd-day { margin-bottom: clamp(2rem, 5vh, 3rem); }

	.iwd-day__label {
		margin: 0 0 .85rem;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		letter-spacing: .055em;
		text-transform: uppercase;
	}

	/* Each booking is its own card. Hairlines read beautifully for four rows
	   and turn into an undifferentiated wall at forty: with a hundred bookings
	   the eye needs an edge to land on before it can find a name. */
	.iwd-card {
		padding: clamp(1.1rem, 3vw, 1.5rem);
		border: 1px solid var(--iwd-line);
		border-radius: 18px;
		background: rgba(255, 255, 255, .55);
		transition: border-color .22s ease, background-color .22s ease, transform .22s ease;
	}
	.iwd-card + .iwd-card { margin-top: .75rem; }
	.iwd-card:hover {
		border-color: var(--ioulia-ink-22, rgba(43, 43, 43, .22));
		background: rgba(255, 255, 255, .85);
	}
	.iwd-card.is-cancelled {
		background: none;
		opacity: .55;
	}
	.iwd-card.is-cancelled:hover { border-color: var(--iwd-line); background: none; }

	.iwd-card__top {
		display: flex;
		margin-bottom: .85rem;
		gap: .75rem;
		align-items: center;
	}

	/* The time is the one thing she scans for, so it is the one thing set in
	   ink on paper rather than paper on ink. */
	.iwd-pill {
		display: inline-flex;
		min-height: 30px;
		padding: 0 .7rem;
		border-radius: 999px;
		background: var(--ioulia-ink);
		color: var(--ioulia-paper);
		font-size: var(--ioulia-micro);
		font-weight: 600;
		line-height: 1;
		letter-spacing: .02em;
		font-variant-numeric: tabular-nums;
		align-items: center;
	}

	.iwd-card__seats {
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		font-variant-numeric: tabular-nums;
		white-space: nowrap;
		margin-left: auto;
	}

	.iwd-card__flag {
		padding: .25rem .6rem;
		border: 1px solid var(--iwd-line);
		border-radius: 999px;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		white-space: nowrap;
		margin-left: auto;
	}
	.iwd-card__seats + .iwd-card__flag { margin-left: 0; }

	.iwd-card__name {
		margin: 0;
		font-size: var(--ioulia-body-lg);
		font-weight: 500;
		line-height: 1.25;
		letter-spacing: -.02em;
	}

	.iwd-card__meta {
		margin: .3rem 0 0;
		color: var(--iwd-muted);
		font-size: var(--ioulia-small);
		font-weight: 500;
	}
	.iwd-card__meta span { padding: 0 .35em; }

	/* The visitor's own words, set apart the way they are in the email. */
	.iwd-card__note {
		margin: .85rem 0 0;
		padding-left: .9rem;
		border-left: 2px solid var(--iwd-line);
		color: var(--ioulia-ink-80, rgba(43, 43, 43, .8));
		font-size: var(--ioulia-small);
		font-weight: 500;
		line-height: 1.5;
	}

	.iwd-card__actions {
		display: flex;
		margin-top: 1.1rem;
		gap: .5rem;
		align-items: center;
		flex-wrap: wrap;
	}

	/* Cancelling is the one action here that cannot be undone, so it carries
	   the only colour on the page - and it is pushed away from the two that
	   are safe to press by accident. */
	.iwd-card__actions .iwd-danger {
		margin-left: auto;
		border-color: rgba(143, 57, 57, .3) !important;
		color: var(--iwd-danger) !important;
	}
	.iwd-card__actions .iwd-danger:is(:hover, :focus-visible) {
		border-color: var(--iwd-danger) !important;
		background: rgba(143, 57, 57, .06) !important;
		color: var(--iwd-danger) !important;
	}

	/* --- Links that do things. Not buttons: there is one button on this page
	       and it is the one that sends an email. ------------------------- */

	.iwd-link {
		appearance: none;
		display: inline-block;
		padding: 0;
		border: 0;
		background: none;
		color: var(--ioulia-ink);
		font: inherit;
		font-size: var(--ioulia-small);
		font-weight: 500;
		text-decoration: none;
		text-underline-offset: 4px;
		cursor: pointer;
		transition: color .22s ease;
	}
	.iwd-link:is(:hover, :focus-visible) { text-decoration: underline; }
	.iwd-link--danger { color: var(--iwd-danger); }
	.iwd-link--muted { color: var(--iwd-muted); cursor: default; }
	.iwd-link--muted:hover { text-decoration: none; }

	/* --- The calendar ----------------------------------------------------
	   The same shape as the one in the booking popup, because it is the same
	   thing seen from the other side. What it adds is the dot: the list says
	   who is coming on Saturday, the dot says which week is worth opening
	   another session for. */

	.iwd-cal {
		padding: clamp(.85rem, 3vw, 1.25rem);
		border: 1px solid var(--iwd-line);
		border-radius: 18px;
		background: rgba(255, 255, 255, .55);
	}

	.iwd-cal__head {
		display: flex;
		margin-bottom: .9rem;
		align-items: center;
		justify-content: space-between;
	}

	.iwd-cal__month {
		font-size: var(--ioulia-body);
		font-weight: 500;
		letter-spacing: -.02em;
	}

	.iwd-cal__nav { display: flex; gap: .4rem; }

	.iwd-cal__nav button {
		display: grid;
		width: 40px;
		height: 40px;
		border: 1px solid var(--iwd-line);
		border-radius: 999px;
		background: transparent;
		color: inherit;
		font: inherit;
		cursor: pointer;
		place-items: center;
		transition: border-color .2s ease, background-color .2s ease, opacity .2s ease;
	}
	.iwd-cal__nav button:hover:not(:disabled) { border-color: var(--ioulia-ink); background: var(--ioulia-ink-07, rgba(43, 43, 43, .07)); }
	.iwd-cal__nav button:disabled { opacity: .25; cursor: default; }

	.iwd-cal__grid {
		display: grid;
		grid-template-columns: repeat(7, minmax(0, 1fr));
		gap: clamp(2px, .8vw, 5px);
	}

	.iwd-cal__dayname {
		padding: .3rem 0 .5rem;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		letter-spacing: .08em;
		text-align: center;
		text-transform: uppercase;
	}

	/* The cell is a column: the number, then room for the dot under it. Every
	   cell reserves that room whether or not it has a dot, so the numbers stay
	   on one line across the month. */
	.iwd-cal__day {
		display: flex;
		aspect-ratio: 1;
		min-height: 44px;
		padding: 0;
		border: 1px solid transparent;
		border-radius: 999px;
		background: transparent;
		color: var(--iwd-muted);
		font: inherit;
		font-size: var(--ioulia-small);
		font-variant-numeric: tabular-nums;
		line-height: 1;
		opacity: .3;
		cursor: default;
		gap: 3px;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		transition: border-color .2s ease, background-color .2s ease, color .2s ease, opacity .2s ease;
	}

	.iwd-cal__day.is-open {
		background: var(--ioulia-ink-07, rgba(43, 43, 43, .07));
		color: var(--ioulia-ink);
		font-weight: 500;
		opacity: 1;
	}
	.iwd-cal__day.has-bookings { cursor: pointer; }
	.iwd-cal__day.has-bookings:hover,
	.iwd-cal__day.has-bookings:focus-visible {
		border-color: var(--ioulia-ink);
		outline: none;
	}
	.iwd-cal__day.is-today {
		border-color: var(--ioulia-ink);
	}

	.iwd-dot {
		display: block;
		width: 6px;
		height: 6px;
		border-radius: 999px;
		background: currentColor;
	}
	.iwd-cal__day .iwd-dot { margin-bottom: -9px; }

	/* Three states, and they have to survive being read by someone who cannot
	   tell green from red - so they differ in lightness as well as in hue, and
	   the day is also readable from its number against the seats. */
	.iwd-dot--free { background: #4B7A4E; }
	.iwd-dot--some { background: #C07A28; }
	.iwd-dot--full { background: #8F3939; }

	.iwd-key {
		display: flex;
		margin: 1rem 0 0;
		padding: 0;
		gap: clamp(.9rem, 4vw, 1.5rem);
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		list-style: none;
		flex-wrap: wrap;
	}
	.iwd-key li { display: flex; gap: .45rem; align-items: center; }
	.iwd-key__note { flex-basis: 100%; }
	.iwd-key .iwd-dot { margin: 0; }

	/* The day the calendar sent us to, held for a moment so the eye finds it. */
	.iwd-day.is-target .iwd-day__label { color: var(--ioulia-ink); }
	.iwd-day.is-target > .iwd-card:first-of-type { border-color: var(--ioulia-ink); }

	/* --- Cancel --------------------------------------------------------- */

	.iwd-cancel { margin-top: .9rem; }
	.iwd-cancel[hidden] { display: none; }

	.iwd-cancel label {
		display: block;
		margin-bottom: .45rem;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
	}

	.iwd textarea,
	.iwd input[type="password"] {
		width: 100%;
		padding: .8rem 1rem;
		border: 1px solid var(--iwd-line);
		border-radius: 12px;
		outline: none;
		background: rgba(255, 255, 255, .6);
		color: var(--ioulia-ink);
		font-family: inherit;
		/* Literal 16px: anything smaller makes iOS zoom the page on focus. */
		font-size: 16px;
		line-height: 1.5;
		transition: border-color .22s ease, box-shadow .22s ease;
	}
	.iwd textarea { min-height: 76px; resize: vertical; }
	.iwd textarea:focus,
	.iwd input[type="password"]:focus {
		border-color: var(--ioulia-ink);
		box-shadow: 0 0 0 3px var(--ioulia-ink-07, rgba(43, 43, 43, .07));
	}

	.iwd-cancel__buttons {
		display: flex;
		margin-top: .8rem;
		gap: 1.25rem;
		align-items: center;
		flex-wrap: wrap;
	}

	.iwd-empty {
		margin: 0;
		padding: clamp(2rem, 6vh, 3rem) 0;
		color: var(--iwd-muted);
		font-size: var(--ioulia-small);
		font-weight: 500;
	}

	/* --- The programmes editor ------------------------------------------- */

	.iwe-item {
		margin-bottom: .6rem;
		border: 1px solid var(--iwd-line);
		border-radius: 18px;
		background: rgba(255, 255, 255, .55);
	}

	.iwe-item__summary {
		display: flex;
		padding: clamp(.9rem, 2.5vw, 1.2rem);
		gap: .75rem;
		align-items: center;
		cursor: pointer;
		list-style: none;
	}
	.iwe-item__summary::-webkit-details-marker { display: none; }

	.iwe-item__num {
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		font-variant-numeric: tabular-nums;
	}

	.iwe-item__title {
		font-size: var(--ioulia-body);
		font-weight: 500;
		letter-spacing: -.015em;
		flex: 1 1 auto;
		min-width: 0;
	}

	.iwe-item__body {
		padding: 0 clamp(.9rem, 2.5vw, 1.2rem) clamp(1.1rem, 3vw, 1.4rem);
	}

	.iwe label,
	.iwe legend {
		display: block;
		margin: 1rem 0 .4rem;
		padding: 0;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		letter-spacing: .055em;
		text-transform: uppercase;
	}

	.iwe input[type="text"],
	.iwe input[type="number"],
	.iwe input[type="time"],
	.iwe select,
	.iwe textarea {
		width: 100%;
		padding: .7rem .9rem;
		border: 1px solid var(--iwd-line);
		border-radius: 12px;
		outline: none;
		background: rgba(255, 255, 255, .7);
		color: var(--ioulia-ink);
		font-family: inherit;
		/* Literal 16px: anything smaller makes iOS zoom the page on focus. */
		font-size: 16px;
		line-height: 1.4;
		transition: border-color .22s ease, box-shadow .22s ease;
	}
	.iwe textarea { line-height: 1.55; resize: vertical; }
	.iwe :is(input, select, textarea):focus {
		border-color: var(--ioulia-ink);
		box-shadow: 0 0 0 3px var(--ioulia-ink-07, rgba(43, 43, 43, .07));
	}

	.iwe-pair { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 .75rem; }

	.iwe-sessions { margin: 1.25rem 0 0; padding: 0; border: 0; }

	/* Day, from, to, and a way to take the row away. The remove button sits at
	   the end so the three fields stay in the order they are spoken. */
	.iwe-session {
		display: grid;
		grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr) 36px;
		margin-bottom: .5rem;
		gap: .4rem;
		align-items: center;
	}

	.iwe-remove {
		display: grid;
		width: 36px;
		height: 36px;
		padding: 0;
		border: 1px solid var(--iwd-line);
		border-radius: 999px;
		background: none;
		color: var(--iwd-muted);
		font: inherit;
		font-size: 17px;
		line-height: 1;
		cursor: pointer;
		place-items: center;
		transition: border-color .22s ease, color .22s ease;
	}
	.iwe-remove:hover { border-color: var(--iwd-danger); color: var(--iwd-danger); }

	.iwe-flags { margin-top: 1.25rem; }

	.iwe-check {
		display: flex;
		margin: 0 0 .6rem;
		gap: .6rem;
		align-items: flex-start;
		color: var(--ioulia-ink);
		font-size: var(--ioulia-small);
		font-weight: 500;
		letter-spacing: 0;
		text-transform: none;
		cursor: pointer;
	}
	.iwe-check input { width: 18px; height: 18px; margin-top: .1rem; accent-color: var(--ioulia-ink); flex: 0 0 auto; }

	.iwe-actions {
		display: flex;
		margin-top: 1.25rem;
		gap: .6rem;
		align-items: center;
		flex-wrap: wrap;
	}

	.iwe-hint {
		margin: 1rem 0 0;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		line-height: 1.55;
	}

	@media (max-width: 480px) {
		.iwe-pair { grid-template-columns: 1fr; }
		.iwe-session { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 36px; }
		.iwe-session select { grid-column: 1 / -1; }
	}

	/* --- The gate ------------------------------------------------------- */

	.iwd--gate { max-width: 340px; }

	.iwd-gate__form label,
	.iwd-pin__form label {
		display: block;
		margin: 0 0 .45rem;
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		letter-spacing: .055em;
		text-transform: uppercase;
	}

	.iwd-gate__form input { margin-bottom: 1.25rem; letter-spacing: .3em; text-align: center; }
	.iwd-gate__form .ioulia-btn { width: 100%; }

	/* --- The foot: change the PIN, or leave ------------------------------ */

	.iwd-foot {
		display: flex;
		margin-top: clamp(2.5rem, 6vh, 4rem);
		padding-top: clamp(1.25rem, 3vh, 1.75rem);
		border-top: 1px solid var(--iwd-line);
		gap: 1.5rem;
		align-items: flex-start;
		justify-content: space-between;
	}

	.iwd-pin { min-width: 0; }
	.iwd-pin summary { list-style: none; }
	.iwd-pin summary::-webkit-details-marker { display: none; }

	.iwd-pin__form { margin-top: 1.1rem; max-width: 280px; }
	.iwd-pin__form input { margin-bottom: 1rem; }
	.iwd-pin__form .ioulia-btn { width: 100%; }

	.iwd-signout { margin: 0; }

	@media (max-width: 600px) {
		.iwd-foot { flex-direction: column; gap: 1.25rem; }

		/* Three controls will not sit on one line at this width, so the two
		   safe ones share the first and the cancel takes its own. Keeping it
		   full width there is deliberate: it is easier to hit on purpose and
		   harder to hit while reaching for Email. */
		.iwd-card__actions .iwd-danger {
			width: 100%;
			margin-left: 0;
			margin-top: .35rem;
		}
		.iwd-cancel__buttons > * { flex: 1 1 auto; }
	}
</style>

<script id="ioulia-dashboard-js">
(function () {
	var root = document.querySelector('[data-iwd]');
	if (!root || root.dataset.iwdReady) { return; }
	root.dataset.iwdReady = '1';

	var flash = root.querySelector('[data-iwd-flash]');

	function say(message, bad) {
		if (!flash) { return; }
		flash.textContent = message;
		flash.className = 'iwd-notice iwd-notice--' + (bad ? 'error' : 'ok');
		flash.hidden = false;
	}

	/* Tabs */
	function showTab(name) {
		root.querySelectorAll('[data-iwd-tab]').forEach(function (other) {
			var current = other.getAttribute('data-iwd-tab') === name;
			other.classList.toggle('is-current', current);
			other.setAttribute('aria-selected', current ? 'true' : 'false');
		});

		root.querySelectorAll('[data-iwd-panel]').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-iwd-panel') !== name;
		});
	}

	root.querySelectorAll('[data-iwd-tab]').forEach(function (tab) {
		tab.addEventListener('click', function () {
			showTab(tab.getAttribute('data-iwd-tab'));
		});
	});

	/* ---------------------------------------------------------------------
	   The calendar.

	   One month at a time, drawn from the load map the page carried down. The
	   dot is the whole point of it, so a day with no session at all gets none
	   and stays dim: an empty Monday and a Monday the studio does not open are
	   different things and must not look the same.
	   ------------------------------------------------------------------ */

	var calendar = root.querySelector('[data-iwd-calendar]');
	var load = {};
	var words = { months: [], weekdays: [], today: '' };

	try {
		load = JSON.parse(root.dataset.load || '{}');
		words = JSON.parse(root.dataset.words || '{}');
	} catch (error) {}

	function pad(value) { return value < 10 ? '0' + value : String(value); }

	function iso(year, month, day) {
		return year + '-' + pad(month + 1) + '-' + pad(day);
	}

	/* Which days actually have someone booked on them, so a day with nothing to
	   show does not offer to take her somewhere empty. */
	var withBookings = {};
	root.querySelectorAll('[data-iwd-day]').forEach(function (section) {
		withBookings[section.getAttribute('data-iwd-day')] = section;
	});

	function goToDay(date) {
		var section = withBookings[date];
		if (!section) { return; }

		showTab('upcoming');

		root.querySelectorAll('.iwd-day.is-target').forEach(function (node) {
			node.classList.remove('is-target');
		});
		section.classList.add('is-target');

		/* Hidden until a moment ago, so the position is only right once the
		   browser has laid the panel out. */
		window.requestAnimationFrame(function () {
			var top = section.getBoundingClientRect().top + window.pageYOffset;
			var header = parseFloat(
				getComputedStyle(document.documentElement).getPropertyValue('--ioulia-header-h')
			) || 140;

			window.scrollTo({ top: Math.max(0, top - header - 16), behavior: 'smooth' });
		});
	}

	var cursor = null;

	function drawMonth() {
		if (!calendar || !cursor) { return; }

		var year = cursor.getFullYear();
		var month = cursor.getMonth();
		var first = new Date(year, month, 1);
		/* Monday-first, like the rest of the site. */
		var lead = (first.getDay() + 6) % 7;
		var length = new Date(year, month + 1, 0).getDate();

		calendar.textContent = '';

		var head = document.createElement('div');
		head.className = 'iwd-cal__head';

		var label = document.createElement('span');
		label.className = 'iwd-cal__month';
		label.textContent = (words.months[month] || '') + ' ' + year;
		head.appendChild(label);

		var nav = document.createElement('div');
		nav.className = 'iwd-cal__nav';

		[['prev', '‹', -1], ['next', '›', 1]].forEach(function (item) {
			var button = document.createElement('button');
			button.type = 'button';
			button.textContent = item[1];
			button.setAttribute('aria-label', item[0] === 'prev' ? 'Προηγούμενος μήνας' : 'Επόμενος μήνας');
			button.addEventListener('click', function () {
				cursor = new Date(year, month + item[2], 1);
				drawMonth();
			});
			nav.appendChild(button);
		});

		head.appendChild(nav);
		calendar.appendChild(head);

		var grid = document.createElement('div');
		grid.className = 'iwd-cal__grid';

		(words.weekdays || []).forEach(function (name) {
			var cell = document.createElement('div');
			cell.className = 'iwd-cal__dayname';
			cell.textContent = name;
			grid.appendChild(cell);
		});

		for (var blank = 0; blank < lead; blank++) {
			grid.appendChild(document.createElement('div'));
		}

		for (var day = 1; day <= length; day++) {
			var date = iso(year, month, day);
			var info = load[date];
			var cell = document.createElement('button');

			cell.type = 'button';
			cell.className = 'iwd-cal__day';

			var number = document.createElement('span');
			number.textContent = String(day);
			cell.appendChild(number);

			/* A day is solid when there is somebody on it, and faded otherwise.
			   Every open day used to carry a dot, which meant a green dot on
			   every empty Tuesday for the next two months: a calendar covered
			   in green that said nothing. The dot is now what it sounds like -
			   there are people that day, and this is how full it is. */
			if (withBookings[date]) {
				cell.classList.add('is-open', 'has-bookings');

				var dot = document.createElement('span');
				dot.className = 'iwd-dot iwd-dot--' + ((info && info.state) || 'free');
				cell.appendChild(dot);

				if (info) {
					cell.setAttribute(
						'aria-label',
						day + ' ' + (words.months[month] || '') + ' — ' + info.taken + ' από ' + info.capacity + ' θέσεις'
					);
					cell.title = info.taken + '/' + info.capacity + ' θέσεις';
				}

				cell.addEventListener('click', (function (target) {
					return function () { goToDay(target); };
				}(date)));
			} else {
				cell.disabled = true;
			}

			if (date === words.today) { cell.classList.add('is-today'); }

			grid.appendChild(cell);
		}

		calendar.appendChild(grid);
	}

	if (calendar) {
		cursor = new Date();
		cursor.setDate(1);
		drawMonth();
	}

	/* ---------------------------------------------------------------------
	   The programmes editor: rows of times, and whole programmes, added and
	   taken away without a round trip. The names are indexed, so a new one only
	   has to take the next free index and PHP receives it like any other.
	   ------------------------------------------------------------------ */

	var editor = root.querySelector('[data-iwe]');

	if (editor) {
		var list = editor.querySelector('[data-iwe-list]');
		var template = editor.querySelector('[data-iwe-template]');

		function addSessionRow(box) {
			var rows = box.querySelectorAll('[data-iwe-session]');
			var last = rows[rows.length - 1];
			if (!last) { return; }

			var next = parseInt(box.getAttribute('data-next'), 10) || rows.length;
			var copy = last.cloneNode(true);

			/* Split rather than a regular expression: Site Studio unslashes this
			   file on import, so an escaped bracket would arrive as a bare one
			   and the pattern would become a character class. */
			copy.querySelectorAll('[name]').forEach(function (field) {
				var parts = field.name.split('[sessions][');
				if (parts.length !== 2) { return; }

				var tail = parts[1].substring(parts[1].indexOf(']') + 1);
				field.name = parts[0] + '[sessions][' + next + ']' + tail;
			});

			box.appendChild(copy);
			box.setAttribute('data-next', String(next + 1));
		}

		editor.addEventListener('click', function (event) {
			if (event.target.hasAttribute('data-iwe-add-session')) {
				addSessionRow(event.target.closest('.iwe-sessions').querySelector('[data-iwe-sessions]'));
				return;
			}

			if (event.target.hasAttribute('data-iwe-remove')) {
				var box = event.target.closest('[data-iwe-sessions]');
				var row = event.target.closest('[data-iwe-session]');

				/* Never take the last one away: a programme with no times is a
				   programme nobody can book. */
				if (box.querySelectorAll('[data-iwe-session]').length > 1) {
					row.remove();
				} else {
					row.querySelectorAll('input[type="time"]').forEach(function (field) { field.value = ''; });
				}
				return;
			}

			if (event.target.hasAttribute('data-iwe-add') && list && template) {
				var index = parseInt(list.getAttribute('data-next'), 10) || list.children.length;
				var markup = template.innerHTML.split('__i__').join(String(index));

				var holder = document.createElement('div');
				holder.innerHTML = markup;

				var item = holder.firstElementChild;
				item.open = true;
				list.appendChild(item);
				list.setAttribute('data-next', String(index + 1));

				var title = item.querySelector('[data-iwe-title]');
				if (title) { title.focus(); }
			}
		});

		/* The heading of a folded programme follows the title as it is typed, so
		   a new one stops saying "Νέο πρόγραμμα" before it is saved. */
		editor.addEventListener('input', function (event) {
			if (!event.target.hasAttribute('data-iwe-title')) { return; }

			var item = event.target.closest('[data-iwe-item]');
			var label = item && item.querySelector('[data-iwe-label]');

			if (label) { label.textContent = event.target.value || 'Νέο πρόγραμμα'; }
		});
	}

	/* Cancelling */
	root.addEventListener('click', function (event) {
		var card = event.target.closest('.iwd-card');
		if (!card) { return; }

		var panel = card.querySelector('.iwd-cancel');

		if (event.target.hasAttribute('data-iwd-cancel')) {
			panel.hidden = false;
			panel.querySelector('[data-iwd-reason]').focus();
			return;
		}

		if (event.target.hasAttribute('data-iwd-abort')) {
			panel.hidden = true;
			return;
		}

		if (!event.target.hasAttribute('data-iwd-confirm')) { return; }

		var button = event.target;

		/* In a demo there is nothing on the server to cancel, and the request
		   would come back "we could not find that booking" in front of whoever
		   is being shown the page. The card behaves as it would. */
		if (root.hasAttribute('data-demo')) {
			panel.hidden = true;
			card.classList.add('is-cancelled');
			card.querySelector('.iwd-card__actions').innerHTML =
				'<span class="iwd-card__flag">Ακυρώθηκε</span>';
			say('Η κράτηση ακυρώθηκε και στάλθηκε email στον πελάτη.');
			return;
		}

		var body = new FormData();
		body.append('action', 'ioulia_cancel_booking');
		body.append('nonce', root.dataset.nonce);
		body.append('booking', card.getAttribute('data-booking'));
		body.append('reason', panel.querySelector('[data-iwd-reason]').value);

		button.disabled = true;
		button.textContent = 'Ακύρωση...';

		fetch(root.dataset.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (response) { return response.json(); })
			.then(function (result) {
				if (!result || !result.success) {
					button.disabled = false;
					button.textContent = 'Ακύρωση και email';
					say((result && result.data && result.data.message) || 'Κάτι πήγε στραβά.', true);
					return;
				}

				panel.hidden = true;
				card.classList.add('is-cancelled');
				card.querySelector('.iwd-card__actions').innerHTML =
					'<span class="iwd-card__flag">Ακυρώθηκε</span>';
				say(result.data.message);
			})
			.catch(function () {
				button.disabled = false;
				button.textContent = 'Ακύρωση και email';
				say('Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.', true);
			});
	});
}());
</script>
		<?php
	}
}
