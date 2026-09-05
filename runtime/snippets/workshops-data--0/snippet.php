<?php
/**
 * Workshop programmes and schedule — the single source of truth.
 *
 * Everything about the booking flow reads from here: the programme list the
 * visitor picks from, which days and times are offered, what a seat costs, and
 * how many seats a session has. The copy on the Workshops page is written from
 * the same numbers, so when a price or a time changes, change it here and update
 * that page to match.
 *
 * Editing a programme
 * -------------------
 *   'slug' => array(
 *       'number'   => shown as the index on the Workshops page,
 *       'title'    => what the visitor sees in the picker,
 *       'summary'  => one line under the title,
 *       'price'    => euros per person, per session, before VAT,
 *       'capacity' => seats per session,
 *       'note'     => optional line shown with the price,
 *       'sessions' => array( array( day, start, end ), ... ),
 *   )
 *
 * 'day' is ISO-8601: 1 is Monday through 7 is Sunday. Times are 24h, in the
 * studio's own timezone, which is whatever WordPress is set to.
 *
 * Adding a programme is adding an entry. Retiring one is setting 'active' to
 * false rather than deleting it, so existing bookings still know what they were
 * for.
 *
 * No backslashes anywhere in this file: Site Studio strips one level on import.
 * See CONVENTIONS.md.
 */

if ( ! function_exists( 'ioulia_workshop_settings' ) ) {
	function ioulia_workshop_settings() {
		return array(
			// A visitor cannot book a session closer than this many days away.
			'lead_days'        => 3,

			// How far ahead the picker offers dates.
			'window_days'      => 56,

			// Prices are quoted before VAT, as on the Workshops page.
			'vat_note'         => 'Στις τιμές δεν συμπεριλαμβάνεται ΦΠΑ 24%.',

			// Bookings are deleted this many months after the session, so personal
			// data is not kept longer than it is needed for.
			'retention_months' => 12,

			// Where new bookings are emailed. Empty falls back to the site admin.
			'notify_email'     => '',
		);
	}
}

if ( ! function_exists( 'ioulia_workshop_defaults' ) ) {
	/**
	 * The programmes as they were written by hand, and the shape every one of
	 * them keeps. This is the seed and the fallback: what the studio actually
	 * offers is edited from /kratiseis/ and lives in an option from then on.
	 *
	 * 'summary' is the one line the booking picker shows. 'description' is the
	 * paragraph the workshops page opens. They were two separate texts in two
	 * places until now - the page had its own copy hardcoded in the canvas - and
	 * that is exactly the duplication this replaces.
	 */
	function ioulia_workshop_defaults() {
		return array(

			'handbuilding' => array(
				'active'   => true,
				// Marked in the picker. One at most: a badge on everything says nothing.
				'popular'  => true,
				'number'   => '01',
				'title'    => 'Workshop Πηλοπλαστικής',
				'summary'  => 'Κατασκευές με τα χέρια και με εργαλεία χειρός: pinch pots, μακαρόνι, φύλλο.',
				'description' => 'Κατασκευές με τη μέθοδο της πηλοπλαστικής (με τα χέρια μας και με εργαλεία χειρός: pinch pots, μακαρόνι/coiling, φύλλο/slab building). Ένας άμεσος, οργανικός τρόπος να πλάσετε τον πηλό και να εξερευνήσετε φόρμες και υφές.',
				'price'    => 25,
				'capacity' => 12,
				'note'     => 'περιλαμβάνονται υλικά και εργαλεία',
				'sessions' => array(
					array( 'day' => 3, 'start' => '11:00', 'end' => '13:30' ),
					array( 'day' => 3, 'start' => '18:00', 'end' => '20:30' ),
					array( 'day' => 5, 'start' => '11:00', 'end' => '13:30' ),
					array( 'day' => 5, 'start' => '18:00', 'end' => '20:30' ),
					array( 'day' => 6, 'start' => '11:00', 'end' => '13:30' ),
					array( 'day' => 6, 'start' => '13:30', 'end' => '16:00' ),
					array( 'day' => 6, 'start' => '16:30', 'end' => '19:00' ),
				),
			),

			'wheel' => array(
				'active'   => true,
				'number'   => '02',
				'title'    => 'Workshop Τροχού',
				'summary'  => 'Ζύμωμα, κεντράρισμα, άνοιγμα και ανέβασμα τοιχωμάτων στον τροχό.',
				'description' => 'Εισαγωγή στη λειτουργία και στα εργαλεία του τροχού. Ζύμωμα πηλού, κεντράρισμα, άνοιγμα και ανέβασμα τοιχωμάτων για τη δημιουργία βασικών σχημάτων (κούπες, μπολ, κυλίνδρους). Εστίαση στον ρυθμό, τη στάση του σώματος και τη συγκέντρωση.',
				'price'    => 30,
				'capacity' => 12,
				'note'     => '',
				'sessions' => array(
					array( 'day' => 1, 'start' => '11:00', 'end' => '13:00' ),
					array( 'day' => 2, 'start' => '18:30', 'end' => '20:30' ),
					array( 'day' => 4, 'start' => '18:30', 'end' => '20:30' ),
					array( 'day' => 5, 'start' => '11:00', 'end' => '13:00' ),
				),
			),

			'kids' => array(
				'active'   => true,
				'number'   => '03',
				'title'    => 'Πηλοπλαστική για Παιδιά',
				'summary'  => 'Για παιδιά 6 έως 11 ετών.',
				'description' => 'Επαφή και κατασκευές με πηλό! Μέσα από την κεραμική το παιδί καλλιεργεί τη φαντασία του, λερώνεται και αφήνεται ελεύθερο. Μαθαίνει να δημιουργεί με τα χέρια του και εξασκείται αισθητηριακά.',
				'price'    => 20,
				'capacity' => 12,
				'note'     => '',
				'sessions' => array(
					array( 'day' => 2, 'start' => '17:00', 'end' => '18:30' ),
					array( 'day' => 4, 'start' => '17:00', 'end' => '18:30' ),
					array( 'day' => 6, 'start' => '09:30', 'end' => '11:00' ),
				),
			),

			'parent-child' => array(
				'active'   => true,
				'number'   => '04',
				'title'    => 'Γονέας & Παιδί',
				'summary'  => 'Για παιδιά 6 έως 15 ετών, μαζί με έναν γονέα.',
				'description' => 'Δημιουργήστε παρέα ένα κεραμικό αντικείμενο από πηλό! Η αλληλεπίδραση, η συνεργασία και η ηρεμία όταν δημιουργούμε μαζί συνθέτουν μια μοναδική εμπειρία που μας φέρνει κοντά.',
				'price'    => 40,
				'capacity' => 12,
				'note'     => 'η τιμή είναι για γονέα και παιδί μαζί',
				'sessions' => array(
					array( 'day' => 1, 'start' => '18:00', 'end' => '20:30' ),
				),
			),

			'paint-and-sip' => array(
				'active'   => true,
				'number'   => '05',
				'title'    => 'Κυριακάτικο & Κονσεπτικό',
				'summary'  => 'Ζωγραφική σε έτοιμο κεραμικό, με ποτό και κέρασμα. Μία ή δύο Κυριακές τον μήνα.',
				'description' => 'Workshop ζωγραφικής σε έτοιμο κεραμικό αντικείμενο του εργαστηρίου μας. Μία ή δύο Κυριακές τον μήνα ελάτε με την παρέα σας να ζωγραφίσετε το δικό σας κεραμικό, συνοδεία ποτού και θεματικών εδεσμάτων. Σε μία εβδομάδα παραλαμβάνετε το κεραμικό σας υαλωμένο και έτοιμο για καθημερινή χρήση. Τα θέματα ανακοινώνονται αρχές κάθε μήνα.',
				'price'    => 40,
				'capacity' => 12,
				'note'     => 'περιλαμβάνεται το κεραμικό, χρώματα, υάλωμα, ψήσιμο και κέρασμα',
				'sessions' => array(
					array( 'day' => 7, 'start' => '12:00', 'end' => '14:00' ),
					array( 'day' => 7, 'start' => '18:00', 'end' => '20:00' ),
				),
			),
		);
	}
}

if ( ! function_exists( 'ioulia_workshop_shape' ) ) {
	/**
	 * One programme, cleaned. Everything that reads a programme can then assume
	 * the keys are there and the types are right, whether the record came from
	 * the defaults above or from a form somebody filled in on a phone.
	 */
	function ioulia_workshop_shape( $raw, $fallback_number = '01' ) {
		$raw = (array) $raw;

		$sessions = array();

		foreach ( (array) ( $raw['sessions'] ?? array() ) as $session ) {
			$day   = isset( $session['day'] ) ? (int) $session['day'] : 0;
			$start = isset( $session['start'] ) ? trim( (string) $session['start'] ) : '';
			$end   = isset( $session['end'] ) ? trim( (string) $session['end'] ) : '';

			if ( $day < 1 || $day > 7 || '' === $start || '' === $end ) {
				continue;
			}

			$sessions[] = array( 'day' => $day, 'start' => $start, 'end' => $end );
		}

		/* Soonest first within a day, and days in week order, so every list of
		   times reads the way a week does. */
		usort(
			$sessions,
			function ( $a, $b ) {
				return $a['day'] === $b['day'] ? strcmp( $a['start'], $b['start'] ) : $a['day'] - $b['day'];
			}
		);

		return array(
			'active'      => ! empty( $raw['active'] ),
			'popular'     => ! empty( $raw['popular'] ),
			'number'      => '' !== trim( (string) ( $raw['number'] ?? '' ) ) ? substr( sanitize_text_field( (string) $raw['number'] ), 0, 4 ) : $fallback_number,
			'title'       => sanitize_text_field( (string) ( $raw['title'] ?? '' ) ),
			'summary'     => sanitize_textarea_field( (string) ( $raw['summary'] ?? '' ) ),
			'description' => sanitize_textarea_field( (string) ( $raw['description'] ?? '' ) ),
			'price'       => max( 0, (float) ( $raw['price'] ?? 0 ) ),
			'capacity'    => max( 1, (int) ( $raw['capacity'] ?? 1 ) ),
			'note'        => sanitize_text_field( (string) ( $raw['note'] ?? '' ) ),
			'sessions'    => $sessions,
		);
	}
}

if ( ! function_exists( 'ioulia_workshop_programmes' ) ) {
	/**
	 * What the studio offers. The option is written by the dashboard; until
	 * somebody edits something there is no option and the defaults stand, so a
	 * fresh site works with nothing set up.
	 */
	function ioulia_workshop_programmes() {
		/* The cache is a global rather than a static so saving can drop it. A
		   static would survive the write and the editor would redraw itself from
		   the values it had just replaced. */
		if ( isset( $GLOBALS['ioulia_workshop_cache'] ) ) {
			return $GLOBALS['ioulia_workshop_cache'];
		}

		$stored = get_option( 'ioulia_workshop_programmes', null );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			$GLOBALS['ioulia_workshop_cache'] = ioulia_workshop_defaults();

			return $GLOBALS['ioulia_workshop_cache'];
		}

		$out    = array();
		$number = 1;

		foreach ( $stored as $slug => $programme ) {
			$slug = sanitize_title( (string) $slug );

			if ( '' === $slug ) {
				continue;
			}

			$out[ $slug ] = ioulia_workshop_shape( $programme, str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) );
			$number++;
		}

		$GLOBALS['ioulia_workshop_cache'] = $out;

		return $out;
	}
}

if ( ! function_exists( 'ioulia_workshop_save_programmes' ) ) {
	/**
	 * Store the whole set at once. Passing the lot rather than one record keeps
	 * their order, which is the order they appear in on the page and in the
	 * picker, and means a half-finished edit can never leave a gap.
	 */
	function ioulia_workshop_save_programmes( $programmes ) {
		$clean  = array();
		$number = 1;

		foreach ( (array) $programmes as $slug => $programme ) {
			$slug = sanitize_title( (string) $slug );

			if ( '' === $slug ) {
				continue;
			}

			$shaped = ioulia_workshop_shape( $programme, str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) );

			if ( '' === $shaped['title'] ) {
				continue;
			}

			$clean[ $slug ] = $shaped;
			$number++;
		}

		if ( empty( $clean ) ) {
			return new WP_Error( 'ioulia_no_programmes', 'Χρειάζεται τουλάχιστον ένα πρόγραμμα με τίτλο.' );
		}

		update_option( 'ioulia_workshop_programmes', $clean, false );
		unset( $GLOBALS['ioulia_workshop_cache'] );

		return true;
	}
}

if ( ! function_exists( 'ioulia_workshop_programme' ) ) {
	/**
	 * One programme by slug, or null. Retired programmes are still returned, so a
	 * booking made against one can still be displayed.
	 */
	function ioulia_workshop_programme( $slug ) {
		$programmes = ioulia_workshop_programmes();

		return isset( $programmes[ $slug ] ) ? $programmes[ $slug ] : null;
	}
}

if ( ! function_exists( 'ioulia_workshop_active_programmes' ) ) {
	function ioulia_workshop_active_programmes() {
		return array_filter(
			ioulia_workshop_programmes(),
			static function ( $programme ) {
				return ! empty( $programme['active'] );
			}
		);
	}
}

/* -------------------------------------------------------------------------
 * The programmes on the workshops page
 *
 * The accordion under "τα προγράμματα μας" used to be written out by hand in
 * the canvas: five items, with their days and times and prices typed a second
 * time. Changing a Wednesday meant remembering to change it in two places, and
 * the page and the booking picker had already drifted apart on one of them.
 *
 * The shortcode prints the same records the picker reads. The canvas keeps the
 * heading and the paragraph above it; only the list comes from here.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_workshop_day_times' ) ) {
	/**
	 * "Τετάρτη: 11:00 – 13:30 & 18:00 – 20:30", one line per day.
	 */
	function ioulia_workshop_day_times( $sessions ) {
		$weekdays = function_exists( 'ioulia_greek_weekdays' )
			? ioulia_greek_weekdays()
			: array( 1 => 'Δευτέρα', 2 => 'Τρίτη', 3 => 'Τετάρτη', 4 => 'Πέμπτη', 5 => 'Παρασκευή', 6 => 'Σάββατο', 7 => 'Κυριακή' );

		$by_day = array();

		foreach ( $sessions as $session ) {
			$by_day[ $session['day'] ][] = $session['start'] . ' – ' . $session['end'];
		}

		ksort( $by_day );

		$lines = array();

		foreach ( $by_day as $day => $times ) {
			$lines[] = $weekdays[ $day ] . ': ' . implode( ' & ', $times );
		}

		return $lines;
	}
}

if ( ! function_exists( 'ioulia_workshop_price_label' ) ) {
	function ioulia_workshop_price_label( $programme ) {
		$price = number_format( (float) $programme['price'], 2, ',', '.' ) . '€ / συνάντηση';
		$notes = array();

		if ( '' !== $programme['note'] ) {
			$notes[] = $programme['note'];
		}

		$notes[] = 'χωρίς ΦΠΑ';

		return array( $price, implode( ' · ', $notes ) );
	}
}

if ( ! function_exists( 'ioulia_programmes_shortcode' ) ) {
	function ioulia_programmes_shortcode() {
		$programmes = array_filter(
			ioulia_workshop_programmes(),
			function ( $programme ) {
				return ! empty( $programme['active'] );
			}
		);

		if ( empty( $programmes ) ) {
			return '';
		}

		$out = '<div class="igw-tech__list">';

		foreach ( $programmes as $slug => $programme ) {
			list( $price, $note ) = ioulia_workshop_price_label( $programme );

			$out .= '<div class="igw-tech__item" id="workshop-' . esc_attr( $slug ) . '">'
				. '<button class="igw-tech__trigger" aria-expanded="false" type="button">'
				. '<span class="igw-tech__num">' . esc_html( $programme['number'] ) . '</span>'
				. '<span class="igw-tech__name">' . esc_html( $programme['title'] ) . '</span>'
				. '<span class="igw-tech__arrow" aria-hidden="true"></span>'
				. '</button>'
				. '<div class="igw-tech__drawer"><div class="igw-tech__drawer-inner">';

			$description = '' !== $programme['description'] ? $programme['description'] : $programme['summary'];

			if ( '' !== $description ) {
				$out .= '<p>' . esc_html( $description ) . '</p>';
			}

			$lines = ioulia_workshop_day_times( $programme['sessions'] );

			if ( ! empty( $lines ) ) {
				$out .= '<p><strong>Ημέρες και ώρες:</strong><br>';

				foreach ( $lines as $line ) {
					$out .= '&bull; ' . esc_html( $line ) . '<br>';
				}

				$out .= '</p>';
			}

			$out .= '<p><strong>Κόστος:</strong> ' . esc_html( $price )
				. ' <em>(' . esc_html( $note ) . ')</em></p>'
				. '</div></div></div>';
		}

		$out .= '</div>';

		return $out;
	}

	add_shortcode( 'ioulia_programmes', 'ioulia_programmes_shortcode' );
}
