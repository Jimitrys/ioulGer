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

if ( ! function_exists( 'ioulia_workshop_programmes' ) ) {
	function ioulia_workshop_programmes() {
		return array(

			'handbuilding' => array(
				'active'   => true,
				'number'   => '01',
				'title'    => 'Workshop Πηλοπλαστικής',
				'summary'  => 'Κατασκευές με τα χέρια και με εργαλεία χειρός: pinch pots, μακαρόνι, φύλλο.',
				'price'    => 25,
				'capacity' => 8,
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
				'price'    => 30,
				'capacity' => 4,
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
				'price'    => 20,
				'capacity' => 8,
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
				'price'    => 40,
				'capacity' => 5,
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
