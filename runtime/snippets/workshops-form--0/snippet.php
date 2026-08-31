<?php
/**
 * Workshop booking form — what the visitor fills in.
 *
 * Answers [ioulia_workshops], which the home canvas and the Book Workshop page
 * already use, so replacing the old plugin needs no change to either.
 *
 * Two halves. On the page: two studio photographs, the title, the programmes,
 * and the next few dates read as a day with its times beside it. In a dialog:
 * the whole thing, as three steps, with a month calendar rather than a list.
 *
 * A list of every date a programme runs stops being readable at about six rows,
 * and a programme running three days a week fills a screen with rows that all
 * look alike. A calendar says the same thing in a shape people already know.
 *
 * Availability is rendered into the page as data rather than fetched per step,
 * so choosing is instant and no session is offered that would then be refused.
 * The server re-checks all of it on submit regardless.
 *
 * The form never scrolls the page on load. The plugin this replaces called its
 * step handler during setup, which scrolled visitors past the whole home page
 * to reach the form.
 *
 * Requires the "workshops data" and "workshops bookings" snippets.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_workshops_visuals' ) ) {
	/**
	 * The two studio photographs beside the form. Filtered so they can be swapped
	 * without touching this snippet.
	 */
	function ioulia_workshops_visuals() {
		return (array) apply_filters(
			'ioulia_workshops_visuals',
			array(
				array(
					'src' => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/07/image_50419713-scaled-1-1-scaled.jpg',
					'alt' => 'Χέρια που δουλεύουν τον πηλό στο εργαστήριο',
				),
				array(
					'src' => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/07/image_50419713-scaled-2-1-scaled.jpg',
					'alt' => 'Ζωγραφική πάνω σε κεραμικό στο εργαστήριο',
				),
			)
		);
	}
}

if ( ! function_exists( 'ioulia_calendar_words' ) ) {
	/**
	 * Month names and weekday initials for the calendar, in the reading language.
	 */
	function ioulia_calendar_words() {
		$words = ioulia_locale_date_words();

		return array(
			'months'   => array_values( $words['months'] ),
			'weekdays' => array_values( $words['weekdays_abbr'] ),
		);
	}
}

if ( ! function_exists( 'ioulia_workshops_form_copy' ) ) {
	/**
	 * Every string created after page load. The page-wide translation pass skips
	 * scripts by design, so JavaScript reads this already-localised dictionary
	 * instead of carrying a second, hardcoded language inside the script.
	 */
	function ioulia_workshops_form_copy() {
		$sources = array(
			'heading_programme' => 'Τι θέλεις να κάνεις;',
			'lead_programme'    => 'Πέντε εργαστήρια, όλα ανοιχτά και σε αρχάριους.',
			'lead_date'         => 'Διάλεξε ημέρα.',
			'heading_time'      => 'Διάλεξε ώρα',
			'heading_details'   => 'Τα στοιχεία σου',
			'lead_details'      => 'Θα λάβεις email με την επιβεβαίωση.',
			'heading_done'      => 'Η θέση σου κρατήθηκε.',
			'popular'           => 'Δημοφιλές',
			'previous_month'    => 'Προηγούμενος μήνας',
			'next_month'        => 'Επόμενος μήνας',
			'available_times'   => 'Διαθέσιμες ώρες',
			'one_place'         => '1 θέση',
			'many_places'       => '%d θέσεις',
			'next'              => 'Επόμενο',
			'complete'          => 'Ολοκλήρωση κράτησης',
			'maximum'           => 'Αυτές είναι οι διαθέσιμες θέσεις.',
			'vat'               => '+ ΦΠΑ',
			'per_person'        => 'ανά άτομο',
			'people_suffix'     => 'άτομα',
			'vat_note'          => 'Οι τιμές δεν περιλαμβάνουν ΦΠΑ.',
			'sending'           => 'Στέλνουμε...',
			'generic_error'     => 'Κάτι πήγε στραβά.',
			'network_error'     => 'Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.',
		);
		$copy    = array();

		foreach ( $sources as $key => $source ) {
			$copy[ $key ] = function_exists( 'ioulia_maybe_translate' ) ? ioulia_maybe_translate( $source ) : $source;
		}

		return $copy;
	}
}

/* -------------------------------------------------------------------------
 * Submission
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_book_ajax' ) ) {
	function ioulia_book_ajax() {
		check_ajax_referer( 'ioulia_book', 'nonce' );

		// Two quiet bot checks: a field no human sees, and a form that came back
		// impossibly fast. Neither stores anything about the visitor.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success( array( 'message' => ioulia_maybe_translate( 'Ευχαριστούμε.' ) ) );
		}

		$opened = isset( $_POST['opened'] ) ? absint( $_POST['opened'] ) : 0;

		if ( $opened && ( time() - $opened ) < 3 ) {
			wp_send_json_error( array( 'message' => ioulia_maybe_translate( 'Δοκίμασε ξανά σε λίγο.' ) ), 400 );
		}

		$booking = ioulia_create_booking(
			array(
				'programme'    => isset( $_POST['programme'] ) ? wp_unslash( $_POST['programme'] ) : '',
				'starts'       => isset( $_POST['starts'] ) ? wp_unslash( $_POST['starts'] ) : '',
				'participants' => isset( $_POST['participants'] ) ? wp_unslash( $_POST['participants'] ) : 1,
				'name'         => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
				'email'        => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
				'phone'        => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
				'note'         => isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '',
				'consent'      => ! empty( $_POST['consent'] ),
			)
		);

		if ( is_wp_error( $booking ) ) {
			wp_send_json_error( array( 'message' => ioulia_maybe_translate( $booking->get_error_message() ) ), 400 );
		}

		wp_send_json_success(
			array(
				'programme' => $booking['programme_title'],
				'when'      => ioulia_format_session( $booking['starts'] ),
			)
		);
	}
	add_action( 'wp_ajax_ioulia_book', 'ioulia_book_ajax' );
	add_action( 'wp_ajax_nopriv_ioulia_book', 'ioulia_book_ajax' );
}

/* -------------------------------------------------------------------------
 * The form
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_workshops_shortcode' ) ) {
	function ioulia_workshops_shortcode() {
		$availability = ioulia_availability();
		$privacy      = get_privacy_policy_url();
		$visuals      = ioulia_workshops_visuals();
		$copy         = ioulia_workshops_form_copy();

		ob_start();
		?>
		<section class="iwf" data-iwf
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_book' ) ); ?>"
			data-words="<?php echo esc_attr( wp_json_encode( ioulia_calendar_words() ) ); ?>"
			data-copy="<?php echo esc_attr( wp_json_encode( $copy ) ); ?>">

			<div class="iwf__grid">

				<div class="iwf__visuals" aria-hidden="true">
					<?php foreach ( array_slice( $visuals, 0, 2 ) as $index => $visual ) : ?>
						<figure class="iwf__visual iwf__visual--<?php echo 0 === $index ? 'a' : 'b'; ?>" data-iwf-reveal>
							<img src="<?php echo esc_url( $visual['src'] ); ?>" alt="<?php echo esc_attr( ioulia_maybe_translate( $visual['alt'] ) ); ?>" loading="lazy" decoding="async">
						</figure>
					<?php endforeach; ?>
				</div>

				<div class="iwf__col">
					<p class="iwf__eyebrow" data-iwf-reveal>κρατήσεις</p>
					<h2 class="iwf__title" data-iwf-reveal>Εργαστήρια</h2>

					<?php if ( empty( $availability ) ) : ?>

						<p class="iwf__intro" data-iwf-reveal>Αυτή τη στιγμή δεν υπάρχουν διαθέσιμες ημερομηνίες. Γράψε μας και θα βρούμε μαζί μια μέρα.</p>

					<?php else : ?>

						<div class="iwf__chips" data-iwf-programmes data-iwf-reveal role="group" aria-label="Πρόγραμμα"></div>

						<div class="iwf__schedule" data-iwf-schedule data-iwf-reveal></div>

						<p class="iwf__intro" data-iwf-reveal>Μαθήματα πηλοπλαστικής και τροχού για όσους θέλουν να επιβραδύνουν και να δημιουργήσουν κάτι δικό τους. Όλα τα υλικά, τα εργαλεία και τα ψησίματα περιλαμβάνονται.</p>

						<button type="button" class="ioulia-btn ioulia-btn--filled iwf__open" data-iwf-open data-iwf-reveal>Κλείσε τη θέση σου</button>

					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $availability ) ) : ?>
			<div class="iwf-modal" data-iwf-modal hidden>
				<div class="iwf-modal__backdrop" data-iwf-close></div>

				<div class="iwf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="iwf-modal-title" data-lenis-prevent>
					<span class="iwf-modal__grab" aria-hidden="true"></span>

					<header class="iwf-modal__head">
						<span class="iwf-progress" aria-hidden="true">
							<span class="iwf-progress__bar" data-iwf-progress="1"></span>
							<span class="iwf-progress__bar" data-iwf-progress="2"></span>
							<span class="iwf-progress__bar" data-iwf-progress="3"></span>
							<span class="iwf-progress__bar" data-iwf-progress="4"></span>
						</span>

						<button type="button" class="iwf-modal__close" data-iwf-close aria-label="Κλείσιμο">
							<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M3 3 L13 13 M13 3 L3 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						</button>
					</header>

					<div class="iwf-modal__body" data-iwf-scroll data-lenis-prevent data-lenis-prevent-wheel data-lenis-prevent-touch>
						<div class="iwf-modal__title-row">
							<h3 class="iwf-modal__title" id="iwf-modal-title" data-iwf-modal-title>Κλείσε τη θέση σου</h3>
							<span class="iwf__chosen-label" data-iwf-chosen-label hidden>Η επιλογή σου</span>
						</div>
						<p class="iwf-modal__lead" data-iwf-lead></p>
						<div class="iwf__chosen" data-iwf-chosen-wrap hidden>
							<strong data-iwf-chosen-programme></strong>
							<span data-iwf-chosen-date></span>
							<span data-iwf-chosen-time></span>
						</div>

						<div class="iwf-step" data-iwf-step="1">
							<div class="iwf__options" data-iwf-options></div>
						</div>

						<div class="iwf-step" data-iwf-step="2" hidden>
							<div class="iwf-cal" data-iwf-calendar></div>
						</div>

						<div class="iwf-step" data-iwf-step="3" hidden>
							<div class="iwf-times" data-iwf-times hidden>
								<div class="iwf-times__label" data-iwf-times-label></div>
								<div class="iwf__chips iwf__chips--times" data-iwf-times-list role="group" aria-label="Ώρα"></div>
							</div>
						</div>

						<div class="iwf-step" data-iwf-step="4" hidden>
							<form class="iwf__form" data-iwf-form novalidate>
								<div class="iwf__people">
									<span id="iwf-people-label">Άτομα</span>
									<div class="iwf__stepper" role="group" aria-labelledby="iwf-people-label">
										<button type="button" data-iwf-people="-1" aria-label="Λιγότερα άτομα">−</button>
										<output data-iwf-people-value>1</output>
										<button type="button" data-iwf-people="1" aria-label="Περισσότερα άτομα">+</button>
									</div>
								</div>

								<div class="iwf__total" data-iwf-total-wrap>
									<div class="iwf__total-head">
										<span class="iwf__total-label">Σύνολο</span>
										<strong class="iwf__total-value" data-iwf-total></strong>
									</div>
									<span class="iwf__total-note" data-iwf-total-note></span>
								</div>

								<div class="iwf__fields">
									<label class="iwf__field">
										<span>Ονοματεπώνυμο</span>
										<input type="text" name="name" autocomplete="name" required>
									</label>

									<label class="iwf__field">
										<span>Email</span>
										<input type="email" name="email" autocomplete="email" required>
									</label>

									<label class="iwf__field">
										<span>Τηλέφωνο</span>
										<input type="tel" name="phone" autocomplete="tel">
									</label>

									<label class="iwf__field iwf__field--wide">
										<span>Κάτι που πρέπει να ξέρουμε; <em>προαιρετικό</em></span>
										<textarea name="note" rows="2"></textarea>
									</label>
								</div>

								<label class="iwf__consent">
									<input type="checkbox" name="consent" required>
									<span>
										Συμφωνώ να κρατήσετε τα στοιχεία μου για να διαχειριστείτε αυτή την κράτηση.
										<?php if ( $privacy ) : ?>
											<a href="<?php echo esc_url( $privacy ); ?>" target="_blank" rel="noopener">Πολιτική Απορρήτου</a>
										<?php endif; ?>
									</span>
								</label>

								<div class="iwf__trap" aria-hidden="true">
									<label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
								</div>
								<input type="hidden" name="opened" value="<?php echo esc_attr( time() ); ?>">
							</form>
						</div>

						<div class="iwf-step iwf__done" data-iwf-step="done" hidden>
							<p data-iwf-summary></p>
							<p>Σου στείλαμε email με τα στοιχεία. Θα σε περιμένουμε στο εργαστήριο.</p>
						</div>

						<p class="iwf__error" data-iwf-error hidden role="alert"></p>
					</div>

					<footer class="iwf-modal__foot" data-iwf-foot hidden>
						<button type="button" class="iwf-modal__back" data-iwf-back hidden>Πίσω</button>
						<button type="button" class="iwf-modal__next" data-iwf-next>Επόμενο</button>
					</footer>
				</div>
			</div>
			<?php endif; ?>

			<script type="application/json" data-iwf-availability><?php echo wp_json_encode( $availability ); ?></script>
		</section>
		<?php
		ioulia_workshops_form_assets();

		return ob_get_clean();
	}
	add_shortcode( 'ioulia_workshops', 'ioulia_workshops_shortcode' );
}

/* -------------------------------------------------------------------------
 * Styles and behaviour
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_workshops_form_assets' ) ) {
	function ioulia_workshops_form_assets() {
		static $printed = false;

		if ( $printed ) {
			return;
		}

		$printed = true;
		?>
<style id="ioulia-workshops-form-css">
	.iwf {
		--iwf-ink: var(--ioulia-ink, #2B2B2B);
		--iwf-paper: var(--ioulia-paper, #FFFEF7);
		--iwf-line: var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		--iwf-muted: var(--ioulia-ink-80, rgba(43, 43, 43, .8));
		--iwf-ease: cubic-bezier(.2, .82, .2, 1);
		--iwf-spring: cubic-bezier(.2, 1.08, .32, 1);
		--iwf-exit: cubic-bezier(.4, 0, .7, .2);

		/* Times read as their own colour, not as the site's accent. */
		/* Availability is a quiet tint of the ink, and choosing fills it. Colour
		   in a booking flow reads as a warning, which is the opposite of what an
		   open slot means. */
		--iwf-slot-ink: var(--ioulia-ink, #2B2B2B);
		--iwf-slot-bg: rgba(43, 43, 43, .07);

		box-sizing: border-box;
		width: 100%;
		max-width: var(--ioulia-shell, 100%);
		margin-inline: auto;
		padding: clamp(4rem, 10vw, 9rem) var(--ioulia-page-x, 1.25rem);
		/* The header is fixed, so its height is the floor for the top inset. */
		padding-top: max(clamp(4rem, 10vw, 9rem), calc(var(--ioulia-header-h, 176px) + 1.5rem));
		background: var(--iwf-paper);
		color: var(--iwf-ink);
		font-family: var(--ioulia-font, sans-serif);
	}
	.iwf *, .iwf *::before, .iwf *::after { box-sizing: inherit; }

	.iwf__grid { display: grid; gap: clamp(2.5rem, 5vw, 5rem); align-items: start; }

	.iwf__visuals { display: grid; grid-template-columns: 1fr 1.35fr; gap: clamp(.75rem, 2vw, 2.2rem); align-items: start; }
	.iwf__visual { position: relative; margin: 0; overflow: hidden; background: rgba(43, 43, 43, .06); }
	.iwf__visual--a { aspect-ratio: 3 / 4; }
	.iwf__visual--b { aspect-ratio: 4 / 5; margin-top: clamp(1rem, 3vw, 3.5rem); }
	.iwf__visual img {
		display: block;
		width: 100%;
		height: 100%;
		object-fit: cover;
		filter: grayscale(1) contrast(1.04);
		transform: scale(1.06);
		transition: transform 1.4s var(--iwf-ease);
	}
	.iwf__visual.is-in img { transform: scale(1); }

	.iwf__eyebrow { margin: 0; color: var(--iwf-muted); font-size: var(--ioulia-micro); letter-spacing: .16em; text-transform: uppercase; }
	.iwf__title { margin: .25em 0 0; font-size: clamp(2.8rem, 7vw, 5.2rem); font-weight: 300; letter-spacing: -.045em; line-height: 1; }
	.iwf__intro { margin: 2rem 0 0; max-width: 40ch; color: var(--iwf-muted); font-size: var(--ioulia-small); line-height: 1.65; }

	.iwf__chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: clamp(2rem, 4vw, 3rem); }
	.iwf__chips--times { margin-top: .8rem; }

	.iwf__chip {
		padding: .55rem 1rem;
		border: 1px solid var(--iwf-line);
		border-radius: 999px;
		background: transparent;
		color: var(--iwf-muted);
		font: inherit;
		font-size: var(--ioulia-micro);
		cursor: pointer;
		transition: color .25s ease, border-color .25s ease, background-color .25s ease, transform .25s var(--iwf-ease);
	}
	.iwf__chip:hover, .iwf__chip:focus-visible { border-color: var(--iwf-ink); color: var(--iwf-ink); outline: none; transform: translateY(-2px); }
	.iwf__chip.is-current { border-color: var(--iwf-ink); background: var(--iwf-ink); color: var(--iwf-paper); }

	.iwf__schedule { margin-top: clamp(1.6rem, 3vw, 2.4rem); border-top: 1px solid var(--iwf-line); }
	.iwf__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem 0; border-bottom: 1px solid var(--iwf-line); }
	.iwf__row-day { color: var(--iwf-muted); font-size: var(--ioulia-micro); letter-spacing: .12em; text-transform: uppercase; white-space: nowrap; }
	.iwf__row-times { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }

	.iwf__slot {
		padding: .35rem .8rem;
		border: 1px solid transparent;
		border-radius: 999px;
		background: var(--iwf-slot-bg);
		color: var(--iwf-slot-ink);
		font: inherit;
		font-size: var(--ioulia-micro);
		font-variant-numeric: tabular-nums;
		cursor: pointer;
		/* Visible by default, animated from hidden. The other way round leaves the
		   chip invisible whenever the animation does not run. */
		animation: iwf-slot-in .5s var(--iwf-ease) both;
		transition: background-color .25s ease, color .25s ease, transform .25s var(--iwf-ease);
	}
	.iwf__slot:hover, .iwf__slot:focus-visible { background: var(--iwf-slot-ink); color: var(--iwf-paper); outline: none; transform: translateY(-2px); }
	.iwf__slot.is-current { background: var(--iwf-slot-ink); color: var(--iwf-paper); }

	@keyframes iwf-slot-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

	.iwf__open { margin-top: clamp(2rem, 4vw, 3rem); }

	/* Only hide what is going to be revealed once the observer is known to be
	   running. Without the flag the open button starts invisible, and a browser
	   without IntersectionObserver, or one that never fires it, leaves it that
	   way. Fail open here too. */
	.iwf.is-watching [data-iwf-reveal] { opacity: 0; transform: translateY(18px); transition: opacity .8s var(--iwf-ease), transform .8s var(--iwf-ease); }
	.iwf.is-watching [data-iwf-reveal].is-in { opacity: 1; transform: none; }

	@media (min-width: 900px) {
		.iwf__grid { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: clamp(3rem, 6vw, 7rem); }
	}

	/* ---- Dialog ----
	   One bottom drawer at every viewport. The consistent spatial model makes
	   opening and closing feel like the same physical object on phone and
	   desktop, while the step title carries app-like hierarchy. */

	.iwf-modal {
		--iwf-ink: var(--ioulia-ink, #2B2B2B);
		--iwf-paper: var(--ioulia-paper, #FFFEF7);
		--iwf-line: var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		--iwf-muted: var(--ioulia-ink-80, rgba(43, 43, 43, .8));
		--iwf-ease: cubic-bezier(.16, 1, .3, 1);
		/* Availability is a quiet tint of the ink, and choosing fills it. Colour
		   in a booking flow reads as a warning, which is the opposite of what an
		   open slot means. */
		--iwf-slot-ink: var(--ioulia-ink, #2B2B2B);
		--iwf-slot-bg: rgba(43, 43, 43, .07);
		--iwf-shell-radius: 24px;
		--iwf-card: 18px;
		--iwf-control: 12px;
		--iwf-gutter: clamp(1.25rem, 5vw, 2rem);

		position: fixed;
		inset: 0;
		z-index: 100000;
		display: flex;
		align-items: flex-end;
		justify-content: center;
		font-family: var(--ioulia-font, sans-serif);
		color: var(--iwf-ink);
	}
	.iwf-modal[hidden] { display: none; }
	.iwf-modal *, .iwf-modal *::before, .iwf-modal *::after { box-sizing: border-box; }
	/* WordPress themes often decorate prose with an accent rule. Inside this app
	   surface paragraphs are interface copy, never pull quotes. */
	.iwf-modal p { border-inline-start: 0 !important; padding-inline-start: 0 !important; }

	/* Entrance is an animation from a hidden state, not a transition towards a
	   visible one. A transition needs the browser to have resolved the starting
	   style first, and anything that stops that resolution — a throttled tab, a
	   style flush that never happens — leaves the dialog sitting at opacity 0
	   with the page locked behind it. An animation either plays or is skipped,
	   and skipping it lands on the visible state. It fails open. */
	.iwf-modal__backdrop { position: absolute; inset: 0; background: rgba(43, 43, 43, .42); }
	.iwf-modal.is-open .iwf-modal__backdrop { animation: iwf-backdrop-in .34s ease-out both; }
	.iwf-modal.is-closing .iwf-modal__backdrop { animation: iwf-backdrop-out .28s ease-in both; }
	.iwf-modal.is-drag-closing .iwf-modal__backdrop { animation-name: iwf-backdrop-drag-out; }

	.iwf-modal__dialog {
		position: relative;
		display: flex;
		flex-direction: column;
		width: min(680px, 100%);
		/* svh fallback plus dvh, so browser chrome cannot clip the footer. */
		height: min(92svh, 860px);
		height: min(92dvh, 860px);
		max-height: 92svh;
		max-height: 92dvh;
		border-radius: var(--iwf-shell-radius) var(--iwf-shell-radius) 0 0;
		background: var(--iwf-paper);
		box-shadow: 0 -16px 52px rgba(43, 43, 43, .16);
		overflow: hidden;
		transform-origin: 50% 100%;
		contain: layout paint;
	}
	.iwf-modal.is-open .iwf-modal__dialog { animation: iwf-dialog-in .5s var(--iwf-spring) both; }
	.iwf-modal.is-closing .iwf-modal__dialog { animation: iwf-dialog-out .32s var(--iwf-exit) both; }
	.iwf-modal.is-drag-closing .iwf-modal__dialog { animation-name: iwf-dialog-drag-out; }

	@keyframes iwf-backdrop-in { from { opacity: 0; } to { opacity: 1; } }
	@keyframes iwf-backdrop-out { from { opacity: 1; } to { opacity: 0; } }
	@keyframes iwf-backdrop-drag-out { from { opacity: var(--iwf-drag-alpha, 1); } to { opacity: 0; } }
	@keyframes iwf-dialog-in {
		from { opacity: .92; transform: translate3d(0, 100%, 0); }
		72% { opacity: 1; transform: translate3d(0, -5px, 0); }
		to { opacity: 1; transform: none; }
	}
	@keyframes iwf-dialog-out { from { opacity: 1; transform: none; } to { opacity: .94; transform: translate3d(0, 100%, 0); } }
	@keyframes iwf-dialog-drag-out { from { opacity: 1; transform: translateY(var(--iwf-drag-y, 0)); } to { opacity: 0; transform: translateY(100%); } }

	.iwf-modal__grab { display: block; width: 40px; height: 4px; margin: 10px auto 0; border-radius: 999px; background: var(--iwf-line); }
	.iwf-modal__grab, .iwf-modal__head { touch-action: none; }

	.iwf-modal__head { display: grid; grid-template-columns: 44px minmax(0, 150px) 44px; flex: 0 0 auto; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem var(--iwf-gutter) 0; }
	.iwf-modal__head::before { content: ''; width: 44px; height: 44px; }

	/* Where you are, as three marks rather than a sentence. */
	.iwf-progress { display: flex; width: 100%; gap: 5px; }
	.iwf-progress__bar { flex: 1 1 0; height: 3px; border-radius: 999px; background: var(--iwf-line); transform: scaleX(1); transform-origin: left; transition: background-color .34s var(--iwf-ease), transform .34s var(--iwf-spring); }
	.iwf-progress__bar.is-done { background: var(--iwf-ink); }

	.iwf-modal__close {
		display: grid;
		place-items: center;
		width: 44px;
		height: 44px;
		margin-right: -.4rem;
		padding: 0;
		border: 1px solid var(--iwf-line);
		flex: 0 0 auto;
		border-radius: 999px;
		background: transparent;
		color: inherit;
		cursor: pointer;
		transition: border-color .22s ease, background-color .22s ease, transform .22s var(--iwf-spring);
	}
	.iwf-modal__close:hover, .iwf-modal__close:focus-visible { border-color: var(--iwf-ink); background: rgba(43, 43, 43, .05); outline: none; transform: rotate(3deg) scale(1.04); }
	.iwf-modal__close svg { width: 15px; height: 15px; }

	.iwf-modal__body {
		flex: 1 1 0;
		/* Without this a flex child refuses to shrink, the body grows to its
		   content, the dialog overflows, and the page behind scrolls instead. */
		min-height: 0;
		max-height: 100%;
		overflow-y: scroll;
		overscroll-behavior: contain;
		-webkit-overflow-scrolling: touch;
		scrollbar-gutter: stable;
		scrollbar-width: thin;
		scrollbar-color: rgba(43, 43, 43, .22) transparent;
		padding: .75rem var(--iwf-gutter) 2rem;
		touch-action: pan-y;
		isolation: isolate;
	}
	.iwf-modal__body::-webkit-scrollbar { width: 5px; }
	.iwf-modal__body::-webkit-scrollbar-track { background: transparent; }
	.iwf-modal__body::-webkit-scrollbar-thumb { border-radius: 999px; background: rgba(43, 43, 43, .22); }

	.iwf-modal__title-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
	.iwf-modal__title { margin: 0; font-size: clamp(1.85rem, 6.8vw, 2.35rem); font-weight: 500; letter-spacing: -.04em; line-height: 1.08; }
	.iwf-modal__lead { margin: .65rem 0 0; color: var(--iwf-muted); font-size: var(--ioulia-small); line-height: 1.5; }
	.iwf-modal__lead[hidden] { display: none; }

	.iwf-step { margin-top: 1.75rem; }
	.iwf-step[hidden] { display: none; }

	/* No fill on the animation: the resting state is the visible one, so a step
	   that never animates is still a step you can read. */
	.iwf-screen { animation: iwf-screen-in .38s var(--iwf-spring); }
	.iwf-modal.is-back .iwf-screen { animation-name: iwf-screen-back; }

	@keyframes iwf-screen-in { from { opacity: 0; transform: translate3d(18px, 4px, 0); } }
	@keyframes iwf-screen-back { from { opacity: 0; transform: translate3d(-18px, 4px, 0); } }

	/* One column: the action first, the way back beneath it. Side by side they
	   compete for the same glance, and on a phone the thumb reaches the bottom
	   edge first, which is where the least reversible thing should not be. */
	.iwf-modal__foot {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		flex: 0 0 auto;
		padding: .9rem var(--iwf-gutter) calc(.9rem + env(safe-area-inset-bottom));
		border-top: 1px solid var(--iwf-line);
		background: var(--iwf-paper);
	}
	.iwf-modal__foot[hidden] { display: none; }
	.iwf-modal__back { order: 1; width: auto; min-height: 44px; padding: .5rem 0; border: 0; background: none; color: var(--iwf-muted); font: inherit; font-size: var(--ioulia-small); font-weight: 500; cursor: pointer; transition: color .2s ease; }
	.iwf-modal__back:hover { color: var(--iwf-ink); }
	.iwf-modal__back[hidden] { display: none; }

	.iwf-modal__next {
		order: 2;
		width: auto;
		min-width: min(240px, 68vw);
		min-height: clamp(50px, 1.4vw + 2.8rem, 58px);
		padding: 0 1.5rem;
		border: 0;
		border-radius: var(--iwf-control);
		background: var(--iwf-ink);
		color: var(--iwf-paper);
		font: inherit;
		font-size: var(--ioulia-small);
		font-weight: 500;
		cursor: pointer;
		transition: opacity .24s ease, transform .24s var(--iwf-spring), box-shadow .24s ease;
	}
	.iwf-modal__next:hover:not(:disabled), .iwf-modal__next:focus-visible:not(:disabled) { outline: none; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(43, 43, 43, .16); }
	.iwf-modal__next:active:not(:disabled) { transform: translateY(0) scale(.99); box-shadow: none; }
	.iwf-modal__next:disabled { opacity: .3; cursor: default; }
	.iwf-modal__next[hidden] { display: none; }

	@media (min-width: 700px) {
		.iwf-modal { align-items: flex-end; padding: 0 clamp(1.5rem, 4vw, 4rem); }
		.iwf-modal__dialog {
			width: min(720px, calc(100vw - 3rem));
			height: min(92vh, 860px);
			height: min(92dvh, 860px);
			max-height: min(92vh, 860px);
			max-height: min(92dvh, 860px);
			border-radius: var(--iwf-shell-radius) var(--iwf-shell-radius) 0 0;
			box-shadow: 0 -24px 80px rgba(43, 43, 43, .22);
			transform-origin: 50% 100%;
		}
		.iwf-modal__grab { margin-top: 12px; }
		.iwf-modal__head { padding-top: .85rem; }
		.iwf-modal__body { padding-bottom: 2.5rem; }
	}

	@media (min-width: 1100px) {
		.iwf-modal__dialog { width: min(760px, calc(100vw - 5rem)); }
	}

	/* ---- Step 1: programmes ---- */

	.iwf__options { display: grid; gap: .65rem; }
	.iwf__option {
		display: block;
		width: 100%;
		padding: 1rem 1.05rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--iwf-card);
		background: transparent;
		color: inherit;
		font: inherit;
		text-align: left;
		cursor: pointer;
		transition: border-color .2s ease, box-shadow .24s ease, background-color .2s ease, transform .24s var(--iwf-spring);
	}
	.iwf__option:hover, .iwf__option:focus-visible { border-color: var(--iwf-ink); background: rgba(255, 255, 255, .36); outline: none; transform: translateY(-2px); }
	/* Two rings rather than a thicker border: the row must not resize when picked. */
	.iwf__option.is-current { border-color: var(--iwf-ink); background: rgba(255, 255, 255, .5); box-shadow: inset 0 0 0 1px var(--iwf-ink); }

	.iwf__option-top { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
	.iwf__option-title { min-width: 0; font-size: 1rem; font-weight: 500; letter-spacing: -.015em; line-height: 1.3; }
	.iwf__option-controls { display: inline-flex; flex: 0 0 auto; align-items: center; gap: .45rem; }

	.iwf__option-badge {
		flex: 0 0 auto;
		padding: .2em .65em;
		border-radius: 999px;
		background: var(--iwf-ink);
		color: var(--iwf-paper);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		white-space: nowrap;
	}

	/* The radio answers "which one is chosen" on its own, so it is the one
	   thing in the card that moves. */
	.iwf__option-mark {
		display: grid;
		flex: 0 0 auto;
		place-items: center;
		width: 26px;
		height: 26px;
		border: 1.5px solid var(--iwf-line);
		border-radius: 999px;
		color: transparent;
		transition: border-color .24s ease, background-color .24s ease, color .24s ease, transform .3s var(--iwf-spring);
	}
	.iwf__option-mark svg { width: 14px; height: 14px; opacity: 0; transform: scale(.5); transition: opacity .2s ease, transform .3s var(--iwf-ease); }
	.iwf__option.is-current .iwf__option-mark { border-color: var(--iwf-ink); background: var(--iwf-ink); color: var(--iwf-paper); transform: scale(1.03); }
	.iwf__option.is-current .iwf__option-mark svg { opacity: 1; transform: none; }

	.iwf__option-price { display: block; margin-top: .2rem; font-size: var(--ioulia-small); font-weight: 500; }
	.iwf__option-summary { display: block; margin-top: .45rem; color: var(--iwf-muted); font-size: var(--ioulia-small); line-height: 1.5; }

	/* ---- Step 2: calendar ---- */

	.iwf-cal { padding: 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-card); background: rgba(255, 255, 255, .38); }
	.iwf-cal__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .8rem; }
	.iwf-cal__month { font-size: clamp(1.05rem, 3vw, 1.2rem); font-weight: 600; letter-spacing: -.02em; }
	.iwf-cal__nav { display: flex; gap: .5rem; }
	.iwf-cal__nav button {
		display: grid;
		place-items: center;
		width: 44px;
		height: 44px;
		border: 1px solid var(--iwf-line);
		border-radius: 999px;
		background: transparent;
		color: inherit;
		font: inherit;
		cursor: pointer;
		transition: border-color .2s ease, background-color .2s ease, opacity .2s ease, transform .24s var(--iwf-spring);
	}
	.iwf-cal__nav button:hover:not(:disabled), .iwf-cal__nav button:focus-visible:not(:disabled) { border-color: var(--iwf-ink); background: rgba(43, 43, 43, .05); outline: none; transform: scale(1.04); }
	.iwf-cal__nav button:disabled { opacity: .25; cursor: default; }

	.iwf-cal__grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: clamp(2px, .8vw, 6px); }
	.iwf-cal__grid.is-month-next { animation: iwf-month-next .34s var(--iwf-spring) both; }
	.iwf-cal__grid.is-month-prev { animation: iwf-month-prev .34s var(--iwf-spring) both; }
	@keyframes iwf-month-next { from { opacity: 0; transform: translateX(12px); } }
	@keyframes iwf-month-prev { from { opacity: 0; transform: translateX(-12px); } }
	.iwf-cal__dayname { padding: .35rem 0 .6rem; color: rgba(43, 43, 43, .62); font-size: var(--ioulia-micro); font-weight: 600; letter-spacing: .08em; text-align: center; text-transform: uppercase; }

	.iwf-cal__day {
		display: grid;
		place-items: center;
		aspect-ratio: 1;
		min-height: 44px;
		border: 0;
		border-radius: 999px;
		background: transparent;
		color: var(--iwf-muted);
		font: inherit;
		font-size: var(--ioulia-small);
		font-variant-numeric: tabular-nums;
		opacity: .2;
		cursor: default;
		transition: border-color .2s ease, background-color .2s ease, color .2s ease, opacity .2s ease, transform .24s var(--iwf-spring);
	}
	.iwf-cal__day.is-open {
		border: 1px solid transparent;
		background: rgba(43, 43, 43, .055);
		color: var(--iwf-slot-ink);
		font-weight: 500;
		opacity: 1;
		cursor: pointer;
	}
	.iwf-cal__day.is-open:hover, .iwf-cal__day.is-open:focus-visible { border-color: var(--iwf-ink); background: rgba(43, 43, 43, .04); color: var(--iwf-ink); outline: none; transform: scale(1.04); }
	.iwf-cal__day.is-current, .iwf-cal__day.is-current:hover, .iwf-cal__day.is-current:focus-visible { border-color: var(--iwf-ink); background: var(--iwf-slot-ink); color: var(--iwf-paper); transform: scale(1.04); }

	.iwf-times { margin-top: .8rem; padding: 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-card); background: rgba(255, 255, 255, .38); animation: iwf-times-in .34s var(--iwf-spring) both; }
	@keyframes iwf-times-in { from { opacity: 0; transform: translateY(8px) scale(.99); } }
	.iwf-times[hidden] { display: none; }
	.iwf-times__label { margin: 0; border: 0; padding: 0; font-size: var(--ioulia-small); font-weight: 600; letter-spacing: -.01em; }
	.iwf__chips--times { margin-top: .75rem; }
	.iwf__chips--times .iwf__chip {
		padding: .6rem 1.05rem;
		border-radius: 999px;
		border-color: var(--iwf-line);
		background: transparent;
		color: var(--iwf-slot-ink);
		font-size: var(--ioulia-small);
		font-variant-numeric: tabular-nums;
	}
	.iwf__chips--times .iwf__chip:hover,
	.iwf__chips--times .iwf__chip:focus-visible { border-color: var(--iwf-ink); background: rgba(43, 43, 43, .04); color: var(--iwf-ink); }
	.iwf__chips--times .iwf__chip.is-current { border-color: var(--iwf-slot-ink); background: var(--iwf-slot-ink); color: var(--iwf-paper); }

	/* ---- Step 3: details ---- */

	.iwf__chosen { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .35rem 1rem; margin: .7rem 0 0; padding: .65rem 0 .85rem; border: 0; border-bottom: 1px solid var(--iwf-line); background: transparent; color: var(--iwf-muted); font-size: var(--ioulia-micro); line-height: 1.4; }
	.iwf__chosen[hidden], .iwf__chosen-label[hidden] { display: none; }
	.iwf__chosen-label { flex: 0 0 auto; padding: .2em .65em; border-radius: 999px; background: var(--iwf-ink); color: var(--iwf-paper); font-size: var(--ioulia-micro); font-weight: 500; letter-spacing: 0; white-space: nowrap; }
	.iwf__chosen strong { color: var(--iwf-ink); font-size: var(--ioulia-micro); font-weight: 600; }
	.iwf__error { margin: 1.2rem 0 0; padding: .9rem 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-control); background: var(--iwf-slot-bg); color: var(--iwf-slot-ink); font-size: var(--ioulia-small); }
	.iwf__error[hidden] { display: none; }

	.iwf__people { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; padding: .8rem 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-card); background: rgba(255, 255, 255, .42); }
	.iwf__stepper { display: flex; align-items: center; gap: .55rem; }
	.iwf__stepper button { width: 44px; height: 44px; border: 1px solid var(--iwf-line); border-radius: 999px; background: transparent; color: inherit; font: inherit; font-size: var(--ioulia-body); cursor: pointer; transition: border-color .2s ease, background-color .2s ease, transform .24s var(--iwf-spring); }
	.iwf__stepper button:hover, .iwf__stepper button:focus-visible { border-color: currentColor; background: rgba(43, 43, 43, .05); outline: none; transform: scale(1.04); }
	.iwf__stepper button:active { transform: scale(.97); }
	.iwf__stepper output { min-width: 2ch; text-align: center; font-variant-numeric: tabular-nums; }

	.iwf__total { display: flex; flex-direction: column; gap: .3rem; margin-bottom: 1.25rem; padding: .85rem 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-card); background: rgba(255, 255, 255, .42); }
	.iwf__total-head { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; }
	.iwf__total-label { color: var(--iwf-muted); font-size: var(--ioulia-micro); font-weight: 600; }
	.iwf__total-value { font-size: var(--ioulia-body-lg); font-weight: 600; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
	.iwf__total-note { color: var(--iwf-muted); font-size: var(--ioulia-micro); line-height: 1.45; }

	.iwf__fields { display: grid; gap: .8rem; padding: 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-card); background: rgba(255, 255, 255, .42); }
	.iwf__field > span { display: block; margin: 0 0 .4rem .15rem; color: var(--iwf-muted); font-size: var(--ioulia-micro); font-weight: 600; }
	.iwf__field em { font-style: normal; opacity: .7; }
	.iwf__field input, .iwf__field textarea {
		display: block;
		width: 100%;
		min-height: 54px;
		padding: .8rem .95rem;
		border: 1px solid var(--iwf-line);
		border-radius: 14px;
		background: rgba(255, 255, 255, .82);
		color: inherit;
		font: inherit;
		/* 16px keeps iOS from zooming the page when a field takes focus. */
		font-size: 16px;
		resize: vertical;
		transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
	}
	.iwf__field textarea { min-height: 96px; }
	.iwf__field input:hover, .iwf__field textarea:hover { border-color: rgba(43, 43, 43, .34); }
	.iwf__field input:focus, .iwf__field textarea:focus { border-color: currentColor; background: #fff; box-shadow: 0 0 0 3px rgba(43, 43, 43, .08); outline: none; }

	.iwf__consent { display: flex; gap: .75rem; margin-top: .8rem; padding: 1rem; border: 1px solid var(--iwf-line); border-radius: var(--iwf-card); background: rgba(255, 255, 255, .42); color: var(--iwf-muted); font-size: var(--ioulia-small); line-height: 1.5; cursor: pointer; }
	.iwf__consent input { width: 22px; height: 22px; margin: 0; flex: 0 0 auto; accent-color: var(--iwf-slot-ink); }
	.iwf__consent a { color: inherit; }

	.iwf__trap { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }

	.iwf__done p { margin: 0 0 .7rem; color: var(--iwf-muted); font-size: var(--ioulia-small); line-height: 1.55; }
	.iwf__done p:first-child { color: var(--iwf-ink); font-size: var(--ioulia-body); }

	@media (min-width: 560px) {
		.iwf__fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.iwf__field--wide { grid-column: 1 / -1; }
	}

	@media (min-width: 700px) {
		.iwf-step[data-iwf-step="4"] { margin-top: .85rem; }
		.iwf__people { margin-bottom: .7rem; padding-block: .55rem; }
		.iwf__fields { gap: .65rem .8rem; padding: .8rem; }
		.iwf__field input, .iwf__field textarea { min-height: 48px; padding-block: .65rem; }
		.iwf__field textarea { min-height: 64px; }
		.iwf__consent { margin-top: .65rem; padding: .75rem .85rem; line-height: 1.35; }
	}

	@media (max-height: 760px) and (min-width: 700px) {
		.iwf-modal__dialog { height: 94vh; height: 94dvh; max-height: 94vh; max-height: 94dvh; }
		.iwf-modal__body { padding-top: .55rem; padding-bottom: 1.5rem; }
		.iwf-modal__title { font-size: 1.8rem; }
		.iwf-step { margin-top: 1.2rem; }
	}

	@media (prefers-reduced-motion: reduce) {
		[data-iwf-reveal], .iwf__visual img, .iwf__slot, .iwf-modal__dialog, .iwf-modal__backdrop,
		.iwf-screen, .iwf__option-mark svg, .iwf-progress__bar, .iwf-cal__grid, .iwf-times,
		.iwf__option, .iwf-modal__next, .iwf-modal__close, .iwf__stepper button {
			transition: none;
			animation: none;
			opacity: 1;
			transform: none;
		}
	}

	/* Muted tone always comes with more weight. Lower contrast must not also
	   mean lighter strokes, or the text pays for the quietness twice. */
	.iwf__eyebrow, .iwf__intro, .iwf__row-day, .iwf__chip,
	.iwf-modal__lead, .iwf-modal__note,
	.iwf__option-summary, .iwf__field > span, .iwf__consent,
	.iwf-cal__dayname, .iwf__error, .iwf__chosen { font-weight: 500; }
</style>

<script id="ioulia-workshops-form-js">
(function () {
	var root = document.querySelector('[data-iwf]');
	if (!root || root.dataset.iwfReady) { return; }
	root.dataset.iwfReady = '1';

	var payload = root.querySelector('[data-iwf-availability]');
	var programmes = payload ? JSON.parse(payload.textContent || '[]') : [];
	var words = JSON.parse(root.dataset.words || '{}');
	var copy = JSON.parse(root.dataset.copy || '{}');

	/* Blocks settle in as they come into view, staggered by order of arrival. */
	var reveals = root.querySelectorAll('[data-iwf-reveal]');
	if (window.IntersectionObserver) {
		root.classList.add('is-watching');
		var seen = 0;
		var watcher = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) { return; }
				entry.target.style.transitionDelay = Math.min(seen++, 6) * 70 + 'ms';
				entry.target.classList.add('is-in');
				watcher.unobserve(entry.target);
			});
		}, { rootMargin: '0px 0px -12% 0px' });
		reveals.forEach(function (node) { watcher.observe(node); });
	}

	if (!programmes.length) { return; }

	var modal = root.querySelector('[data-iwf-modal]');
	var picked = { programme: programmes[0], date: null, time: null, people: 1 };
	var month = null;
	var step = 1;
	var lastFocused = null;
	var closeTimer = null;

	var el = {
		chips: root.querySelector('[data-iwf-programmes]'),
		schedule: root.querySelector('[data-iwf-schedule]'),
		options: root.querySelector('[data-iwf-options]'),
		calendar: root.querySelector('[data-iwf-calendar]'),
		times: root.querySelector('[data-iwf-times]'),
		timesLabel: root.querySelector('[data-iwf-times-label]'),
		timesList: root.querySelector('[data-iwf-times-list]'),
		title: root.querySelector('[data-iwf-modal-title]'),
		lead: root.querySelector('[data-iwf-lead]'),
		foot: root.querySelector('[data-iwf-foot]'),
		back: root.querySelector('[data-iwf-back]'),
		next: root.querySelector('[data-iwf-next]'),
		form: root.querySelector('[data-iwf-form]'),
		chosenWrap: root.querySelector('[data-iwf-chosen-wrap]'),
		chosenLabel: root.querySelector('[data-iwf-chosen-label]'),
		chosenProgramme: root.querySelector('[data-iwf-chosen-programme]'),
		chosenDate: root.querySelector('[data-iwf-chosen-date]'),
		chosenTime: root.querySelector('[data-iwf-chosen-time]'),
		error: root.querySelector('[data-iwf-error]'),
		summary: root.querySelector('[data-iwf-summary]'),
		people: root.querySelector('[data-iwf-people-value]'),
		total: root.querySelector('[data-iwf-total]'),
		totalNote: root.querySelector('[data-iwf-total-note]'),
		scroll: root.querySelector('[data-iwf-scroll]')
	};

	var STEPS = { 1: true, 2: true, 3: true, 4: true };

	var HEADINGS = {
		1: [copy.heading_programme, copy.lead_programme],
		2: ['', copy.lead_date],
		3: [copy.heading_time, ''],
		4: [copy.heading_details, copy.lead_details],
		done: [copy.heading_done, '']
	};

	function showError(message) {
		el.error.textContent = message || '';
		el.error.hidden = !message;
	}

	function datesByKey(programme) {
		var index = {};
		programme.dates.forEach(function (date) { index[date.date] = date; });
		return index;
	}

	/* ---- On the page ---- */

	/* Nodes are built and kept, never written as markup and looked up again.
	   Setting innerHTML to empty spans and then querySelector-ing them back
	   assumes nothing touches the DOM in between, and on a page where something
	   does, the lookup returns null and takes the whole open path with it. */
	function span(className, text) {
		var node = document.createElement('span');
		node.className = className;
		node.textContent = text;
		return node;
	}

	function renderChips() {
		el.chips.textContent = '';

		programmes.forEach(function (programme) {
			var node = document.createElement('button');
			node.type = 'button';
			node.className = 'iwf__chip' + (programme === picked.programme ? ' is-current' : '');
			node.textContent = programme.title;
			node.addEventListener('click', function () {
				picked.programme = programme;
				picked.date = null;
				picked.time = null;
				renderChips();
				renderSchedule();
			});
			el.chips.appendChild(node);
		});
	}

	function renderSchedule() {
		el.schedule.textContent = '';

		picked.programme.dates.slice(0, 3).forEach(function (date, index) {
			var row = document.createElement('div');
			row.className = 'iwf__row';

			var day = document.createElement('span');
			day.className = 'iwf__row-day';
			day.textContent = date.day + ' ' + date.num + ' ' + date.month;

			var times = document.createElement('span');
			times.className = 'iwf__row-times';

			date.times.forEach(function (time, position) {
				var slot = document.createElement('button');
				slot.type = 'button';
				slot.className = 'iwf__slot';
				slot.textContent = time.label.split(' ')[0];
				slot.style.animationDelay = ((index * 3) + position) * 35 + 'ms';
				slot.addEventListener('click', function () {
					chooseDate(date);
					chooseTime(time);
					openModal(4);
				});
				times.appendChild(slot);
			});

			row.appendChild(day);
			row.appendChild(times);
			el.schedule.appendChild(row);
		});
	}

	/* ---- Step 1 ---- */

	function renderOptions() {
		el.options.textContent = '';

		programmes.forEach(function (programme) {
			var node = document.createElement('button');
			node.type = 'button';
			node.className = 'iwf__option' + (programme === picked.programme ? ' is-current' : '');
			node.setAttribute('aria-pressed', programme === picked.programme ? 'true' : 'false');

			var top = document.createElement('span');
			top.className = 'iwf__option-top';
			top.appendChild(span('iwf__option-title', programme.title));

			var controls = document.createElement('span');
			controls.className = 'iwf__option-controls';

			if (programme.popular) {
				controls.appendChild(span('iwf__option-badge', copy.popular));
			}

			var mark = document.createElement('span');
			mark.className = 'iwf__option-mark';
			mark.innerHTML = '<svg viewBox="0 0 14 14" aria-hidden="true" focusable="false">' +
				'<path d="M2.5 7.4 L5.6 10.4 L11.5 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			controls.appendChild(mark);
			top.appendChild(controls);

			node.appendChild(top);
			node.appendChild(span('iwf__option-price', programme.price + ' € ' + copy.vat));
			node.appendChild(span('iwf__option-summary', programme.summary));

			node.addEventListener('click', function () {
				picked.programme = programme;
				picked.date = null;
				picked.time = null;
				month = null;
				renderChips();
				renderSchedule();
				renderOptions();
				goTo(2);
			});

			el.options.appendChild(node);
		});
	}

	/* ---- Step 2: a month at a time ---- */

	function monthKey(date) { return date.slice(0, 7); }

	function availableMonths() {
		var months = [];
		picked.programme.dates.forEach(function (date) {
			var key = monthKey(date.date);
			if (months.indexOf(key) === -1) { months.push(key); }
		});
		return months;
	}

	function renderCalendar(direction) {
		var months = availableMonths();
		if (!months.length) { return; }

		if (months.indexOf(month) === -1) { month = months[0]; }

		var open = datesByKey(picked.programme);
		var year = Number(month.slice(0, 4));
		var index = Number(month.slice(5, 7)) - 1;
		var first = new Date(Date.UTC(year, index, 1));
		var days = new Date(Date.UTC(year, index + 1, 0)).getUTCDate();
		/* Weeks start on Monday, so Sunday moves from 0 to the seventh slot. */
		var offset = (first.getUTCDay() + 6) % 7;
		var position = months.indexOf(month);

		el.calendar.textContent = '';

		var head = document.createElement('div');
		head.className = 'iwf-cal__head';

		var label = document.createElement('span');
		label.className = 'iwf-cal__month';
		label.textContent = words.months[index] + ' ' + year;

		var nav = document.createElement('span');
		nav.className = 'iwf-cal__nav';

		[['‹', -1, position === 0], ['›', 1, position === months.length - 1]].forEach(function (spec) {
			var button = document.createElement('button');
			button.type = 'button';
			button.textContent = spec[0];
			button.disabled = spec[2];
			button.setAttribute('aria-label', spec[1] < 0 ? copy.previous_month : copy.next_month);
			button.addEventListener('click', function () {
				month = months[position + spec[1]];
				renderCalendar(spec[1]);
			});
			nav.appendChild(button);
		});

		head.appendChild(label);
		head.appendChild(nav);
		el.calendar.appendChild(head);

		var grid = document.createElement('div');
		grid.className = 'iwf-cal__grid' +
			(direction > 0 ? ' is-month-next' : '') +
			(direction < 0 ? ' is-month-prev' : '');

		words.weekdays.forEach(function (name) {
			var cell = document.createElement('span');
			cell.className = 'iwf-cal__dayname';
			cell.textContent = name;
			grid.appendChild(cell);
		});

		for (var blank = 0; blank < offset; blank += 1) {
			var spacer = document.createElement('span');
			spacer.className = 'iwf-cal__day iwf-cal__day--blank';
			grid.appendChild(spacer);
		}

		for (var day = 1; day <= days; day += 1) {
			var key = month + '-' + (day < 10 ? '0' + day : day);
			var date = open[key];
			var cell = document.createElement(date ? 'button' : 'span');

			cell.className = 'iwf-cal__day' +
				(date ? ' is-open' : '') +
				(picked.date && picked.date.date === key ? ' is-current' : '');
			cell.textContent = day;

			if (date) {
				cell.type = 'button';
				cell.setAttribute('aria-label', date.full);
				cell.setAttribute('aria-pressed', picked.date && picked.date.date === key ? 'true' : 'false');
				cell.addEventListener('click', function (chosen) {
					return function () { chooseDate(chosen); goTo(3); };
				}(date));
			}

			grid.appendChild(cell);
		}

		el.calendar.appendChild(grid);
	}

	function renderTimesFor(date) {
		el.timesLabel.textContent = copy.available_times;
		el.timesList.textContent = '';

		date.times.forEach(function (time, position) {
			var node = document.createElement('button');
			node.type = 'button';
			node.className = 'iwf__chip' + (picked.time && picked.time.starts === time.starts ? ' is-current' : '');
			node.textContent = time.label;
			node.title = time.left === 1 ? copy.one_place : copy.many_places.replace('%d', time.left);
			node.setAttribute('aria-pressed', picked.time && picked.time.starts === time.starts ? 'true' : 'false');
			node.style.animationDelay = position * 40 + 'ms';
			node.addEventListener('click', function () {
				chooseTime(time);
				goTo(4);
			});
			el.timesList.appendChild(node);
		});

		el.times.hidden = false;
	}

	function chooseDate(date) {
		picked.date = date;
		picked.time = null;
		month = monthKey(date.date);
		renderTimesFor(date);
	}

	function chooseTime(time) {
		picked.time = time;
		picked.people = 1;
		el.people.textContent = '1';
		el.chosenProgramme.textContent = picked.programme.title;
		el.chosenDate.textContent = picked.date.full;
		el.chosenTime.textContent = time.label;
	}

	/* ---- Steps ---- */

	/* Replaying a CSS animation needs the old one removed and layout forced in
	   between, otherwise the browser coalesces both into no change at all. */
	function replay(nodes) {
		nodes.forEach(function (node) {
			if (!node) { return; }
			node.classList.remove('iwf-screen');
			void node.offsetWidth;
			node.classList.add('iwf-screen');
		});
	}

	function goTo(next) {
		var back = STEPS[step] && STEPS[next] && Number(next) < Number(step);

		modal.classList.toggle('is-back', !!back);
		step = next;

		modal.querySelectorAll('[data-iwf-step]').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-iwf-step') !== String(next);
		});

		if (next === 2) { renderCalendar(); }
		if (next === 3 && picked.date) { renderTimesFor(picked.date); }
		if (Number(next) === 4) { renderTotal(); }

		modal.querySelectorAll('[data-iwf-progress]').forEach(function (bar) {
			bar.classList.toggle('is-done', Number(bar.getAttribute('data-iwf-progress')) <= Number(next));
		});
		el.title.textContent = HEADINGS[next] ? HEADINGS[next][0] : picked.programme.title;
		el.lead.textContent = HEADINGS[next] ? HEADINGS[next][1] : '';
		el.lead.hidden = !el.lead.textContent;

		if (next === 2) { el.title.textContent = picked.programme.title; }
		if (next === 3 && picked.date) { el.lead.textContent = picked.date.full; el.lead.hidden = false; }

		el.back.hidden = next === 1 || next === 'done';
		el.next.hidden = 4 !== Number(next);
		el.next.textContent = copy.complete;
		el.foot.hidden = next === 1 || next === 'done';
		el.chosenLabel.hidden = 4 !== Number(next);
		el.chosenWrap.hidden = 4 !== Number(next);
		showError('');

		/* Each step starts at the top of the dialog, never of the page. */
		el.scroll.scrollTop = 0;

		replay([ el.title, el.lead, modal.querySelector('[data-iwf-step="' + next + '"]') ]);
	}

	/* ---- Dialog ---- */

	/* Locking by taking the page out of flow means restoring its scroll by hand,
	   and any disagreement about where it was reads as a jump. Hiding the
	   overflow leaves the page exactly where it is; the width the scrollbar gave
	   back is paid straight back as padding so nothing reflows either. */
	function lockPage() {
		var page = document.documentElement;
		var bar = window.innerWidth - page.clientWidth;

		page.style.overflow = 'hidden';

		if (bar > 0) { page.style.paddingRight = bar + 'px'; }
	}

	function unlockPage() {
		document.documentElement.style.overflow = '';
		document.documentElement.style.paddingRight = '';
	}

	function openModal(at) {
		if (closeTimer) {
			window.clearTimeout(closeTimer);
			closeTimer = null;
		}

		lastFocused = document.activeElement;
		modal.hidden = false;
		modal.classList.remove('is-open', 'is-closing', 'is-drag-closing');
		modal.style.removeProperty('--iwf-drag-y');
		modal.style.removeProperty('--iwf-drag-alpha');
		modal.querySelector('.iwf-modal__dialog').style.animation = '';
		modal.querySelector('.iwf-modal__backdrop').style.animation = '';
		lockPage();
		renderOptions();
		goTo(at || (picked.time ? 4 : 1));

		/* Force layout with the dialog still at opacity 0, then reveal in the same
		   task. requestAnimationFrame would read better, but a browser stops
		   servicing it in a background tab, and a dialog that opens invisible is
		   worse than one that opens without its transition. */
		void modal.offsetHeight;
		modal.classList.add('is-open');

		var close = modal.querySelector('.iwf-modal__close');
		if (close) { close.focus(); }
	}

	function closeModal(drag) {
		if (modal.hidden || modal.classList.contains('is-closing')) { return; }
		var dialog = modal.querySelector('.iwf-modal__dialog');
		var backdrop = modal.querySelector('.iwf-modal__backdrop');
		dialog.style.animation = '';
		dialog.style.transition = '';
		dialog.style.transform = '';
		backdrop.style.animation = '';
		backdrop.style.opacity = '';

		if (drag) {
			modal.style.setProperty('--iwf-drag-y', drag.offset + 'px');
			modal.style.setProperty('--iwf-drag-alpha', String(drag.alpha));
			modal.classList.add('is-drag-closing');
		}

		/* The entrance and exit classes must never compete for the animation
		   shorthand. Some theme optimizers reorder equivalent rules, which made
		   the sheet disappear as soon as hidden was applied. */
		modal.classList.remove('is-open');
		modal.classList.add('is-closing');
		var wait = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 380;

		closeTimer = window.setTimeout(function () {
			modal.hidden = true;
			modal.classList.remove('is-open', 'is-closing', 'is-drag-closing');
			modal.style.removeProperty('--iwf-drag-y');
			modal.style.removeProperty('--iwf-drag-alpha');
			unlockPage();
			closeTimer = null;
			if (lastFocused) { lastFocused.focus(); }
		}, wait);
	}

	/* The handle promises a sheet you can push away, so it has to be one. Drag
	   follows the finger, release past a quarter of the dialog dismisses, and
	   anything shorter springs back. Only where a sheet is what it is: on a
	   desktop it is a centred card and nothing about it invites a drag. */
	(function () {
		var dialog = modal.querySelector('.iwf-modal__dialog');
		var backdrop = modal.querySelector('.iwf-modal__backdrop');
		var startY = 0;
		var offset = 0;
		var velocity = 0;
		var lastY = 0;
		var lastAt = 0;
		var dragging = false;

		function isSheet() { return true; }

		function begin(event) {
			if (!isSheet() || event.button) { return; }

			var from = event.target.closest('.iwf-modal__grab, .iwf-modal__head');
			if (!from || event.target.closest('button')) { return; }

			dragging = true;
			startY = event.clientY;
			lastY = event.clientY;
			lastAt = event.timeStamp;
			offset = 0;
			velocity = 0;
			dialog.style.animation = 'none';
			backdrop.style.animation = 'none';
			dialog.style.transition = 'none';
			dialog.setPointerCapture && dialog.setPointerCapture(event.pointerId);
		}

		function move(event) {
			if (!dragging) { return; }

			var elapsed = Math.max(1, event.timeStamp - lastAt);
			velocity = (event.clientY - lastY) / elapsed;
			lastY = event.clientY;
			lastAt = event.timeStamp;
			offset = Math.max(0, event.clientY - startY);
			dialog.style.transform = 'translateY(' + offset + 'px)';
			backdrop.style.opacity = String(Math.max(0, 1 - (offset / dialog.offsetHeight) * 1.5));
		}

		function end() {
			if (!dragging) { return; }

			dragging = false;
			var alpha = Math.max(0, 1 - (offset / dialog.offsetHeight) * 1.5);
			var dismiss = offset > dialog.offsetHeight * .22 || (velocity > .65 && offset > 40);

			if (dismiss) {
				closeModal({ offset: offset, alpha: alpha });
				return;
			}

			dialog.style.transition = 'transform .44s var(--iwf-spring)';
			backdrop.style.transition = 'opacity .34s ease-out';
			window.requestAnimationFrame(function () {
				dialog.style.transform = '';
				backdrop.style.opacity = '';
			});
			window.setTimeout(function () {
				dialog.style.transition = '';
				backdrop.style.transition = '';
			}, 460);
		}

		dialog.addEventListener('pointerdown', begin);
		dialog.addEventListener('pointermove', move);
		dialog.addEventListener('pointerup', end);
		dialog.addEventListener('pointercancel', end);
	}());

	/* Keep wheel and touch scrolling inside the drawer. This complements the
	   data-lenis-prevent attributes for the site's bundled smooth-scroll layer
	   and also protects against generic document-level wheel handlers. */
	[ 'wheel', 'touchmove' ].forEach(function (type) {
		el.scroll.addEventListener(type, function (event) { event.stopPropagation(); }, { passive: true });
	});

	root.querySelector('[data-iwf-open]').addEventListener('click', function () { openModal(1); });

	modal.querySelectorAll('[data-iwf-close]').forEach(function (node) {
		node.addEventListener('click', function () { closeModal(); });
	});

	el.back.addEventListener('click', function () { goTo(Math.max(1, Number(step) - 1)); });

	document.addEventListener('keydown', function (event) {
		if ('Escape' === event.key && modal.classList.contains('is-open')) { closeModal(); }
	});

	modal.addEventListener('keydown', function (event) {
		if ('Tab' !== event.key) { return; }

		var focusable = Array.prototype.filter.call(
			modal.querySelectorAll('button:not(:disabled), input, textarea, a[href]'),
			function (node) { return node.offsetParent !== null; }
		);
		if (!focusable.length) { return; }

		var first = focusable[0];
		var last = focusable[focusable.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	});

	/* ---- Step 4: booking details ---- */

	/* Programme prices are per person and quoted before VAT, so the total says
	   both: what the participant count comes to, and that VAT is still to be
	   added on top of it. */
	function renderTotal() {
		if (!el.total) { return; }

		var price = Number(picked.programme.price) || 0;
		var parts = [ price + ' € ' + copy.per_person ];

		if (picked.people > 1) {
			parts.push(picked.people + ' ' + copy.people_suffix);
		}

		parts.push(copy.vat_note);

		el.total.textContent = (price * picked.people) + ' € ' + copy.vat;
		el.totalNote.textContent = parts.join('  ·  ');
	}

	root.querySelectorAll('[data-iwf-people]').forEach(function (control) {
		control.addEventListener('click', function () {
			var change = Number(control.getAttribute('data-iwf-people'));
			var most = picked.time ? picked.time.left : 1;

			picked.people = Math.min(most, Math.max(1, picked.people + change));
			el.people.textContent = String(picked.people);
			renderTotal();
			showError(picked.people === most && change > 0 ? copy.maximum : '');
		});
	});

	el.form.addEventListener('submit', function (event) {
		event.preventDefault();

		var data = new FormData(el.form);
		data.append('action', 'ioulia_book');
		data.append('nonce', root.dataset.nonce);
		data.append('programme', picked.programme.slug);
		data.append('starts', picked.time.starts);
		data.append('participants', picked.people);

		var submit = el.next;
		submit.disabled = true;
		submit.textContent = copy.sending;

		fetch(root.dataset.ajax, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (response) { return response.json(); })
			.then(function (result) {
				submit.disabled = false;
				submit.textContent = copy.complete;

				if (!result || !result.success) {
					showError((result && result.data && result.data.message) || copy.generic_error);
					return;
				}

				/* The server keeps booking records and emails in Greek. The public
				   confirmation reuses the already-localised selection on screen. */
				el.summary.textContent = picked.programme.title + '  ·  ' + picked.date.full + ', ' + picked.time.label;
				goTo('done');
			})
			.catch(function () {
				submit.disabled = false;
				submit.textContent = copy.complete;
				showError(copy.network_error);
			});
	});

	el.next.addEventListener('click', function () {
		var current = Number(step);

		if (4 === current) {
			/* Ask the browser to check the fields first, so a missing email is a
			   native message beside the field rather than a sentence at the top. */
			if (el.form.reportValidity && !el.form.reportValidity()) {
				return;
			}

			el.form.requestSubmit ? el.form.requestSubmit() : el.form.dispatchEvent(new Event('submit', { cancelable: true }));
		}
	});

	renderChips();
	renderSchedule();
}());
</script>
		<?php
	}
}
