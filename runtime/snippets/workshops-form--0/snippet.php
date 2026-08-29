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

/* -------------------------------------------------------------------------
 * Submission
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_book_ajax' ) ) {
	function ioulia_book_ajax() {
		check_ajax_referer( 'ioulia_book', 'nonce' );

		// Two quiet bot checks: a field no human sees, and a form that came back
		// impossibly fast. Neither stores anything about the visitor.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success( array( 'message' => 'Ευχαριστούμε.' ) );
		}

		$opened = isset( $_POST['opened'] ) ? absint( $_POST['opened'] ) : 0;

		if ( $opened && ( time() - $opened ) < 3 ) {
			wp_send_json_error( array( 'message' => 'Δοκίμασε ξανά σε λίγο.' ), 400 );
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
			wp_send_json_error( array( 'message' => $booking->get_error_message() ), 400 );
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

		ob_start();
		?>
		<section class="iwf" data-iwf
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_book' ) ); ?>"
			data-words="<?php echo esc_attr( wp_json_encode( ioulia_calendar_words() ) ); ?>">

			<div class="iwf__grid">

				<div class="iwf__visuals" aria-hidden="true">
					<?php foreach ( array_slice( $visuals, 0, 2 ) as $index => $visual ) : ?>
						<figure class="iwf__visual iwf__visual--<?php echo 0 === $index ? 'a' : 'b'; ?>" data-iwf-reveal>
							<img src="<?php echo esc_url( $visual['src'] ); ?>" alt="<?php echo esc_attr( $visual['alt'] ); ?>" loading="lazy" decoding="async">
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

				<div class="iwf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="iwf-modal-title">
					<span class="iwf-modal__grab" aria-hidden="true"></span>

					<header class="iwf-modal__head">
						<p class="iwf-modal__step" data-iwf-steplabel></p>
						<button type="button" class="iwf-modal__close" data-iwf-close aria-label="Κλείσιμο">
							<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M2 2 L14 14 M14 2 L2 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
						</button>
					</header>

					<div class="iwf-modal__body" data-iwf-scroll>
						<h3 class="iwf-modal__title" id="iwf-modal-title" data-iwf-modal-title>Κλείσε τη θέση σου</h3>
						<p class="iwf-modal__lead" data-iwf-lead></p>

						<div class="iwf-step" data-iwf-step="1">
							<div class="iwf__options" data-iwf-options></div>
						</div>

						<div class="iwf-step" data-iwf-step="2" hidden>
							<div class="iwf-cal" data-iwf-calendar></div>
							<div class="iwf-times" data-iwf-times hidden>
								<p class="iwf-times__label" data-iwf-times-label></p>
								<div class="iwf__chips iwf__chips--times" data-iwf-times-list role="group" aria-label="Ώρα"></div>
							</div>
						</div>

						<div class="iwf-step" data-iwf-step="3" hidden>
							<form class="iwf__form" data-iwf-form novalidate>
								<p class="iwf__chosen" data-iwf-chosen></p>

								<div class="iwf__people">
									<span id="iwf-people-label">Άτομα</span>
									<div class="iwf__stepper" role="group" aria-labelledby="iwf-people-label">
										<button type="button" data-iwf-people="-1" aria-label="Λιγότερα άτομα">−</button>
										<output data-iwf-people-value>1</output>
										<button type="button" data-iwf-people="1" aria-label="Περισσότερα άτομα">+</button>
									</div>
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

								<button type="submit" class="ioulia-btn ioulia-btn--filled iwf__submit">Ολοκλήρωση κράτησης</button>
							</form>
						</div>

						<div class="iwf-step iwf__done" data-iwf-step="done" hidden>
							<p data-iwf-summary></p>
							<p>Σου στείλαμε email με τα στοιχεία. Θα σε περιμένουμε στο εργαστήριο.</p>
						</div>

						<p class="iwf__error" data-iwf-error hidden role="alert"></p>
					</div>

					<footer class="iwf-modal__foot">
						<button type="button" class="iwf-modal__back" data-iwf-back hidden>Πίσω</button>
						<p class="iwf-modal__note" data-iwf-foot-note></p>
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
		--iwf-muted: var(--ioulia-ink-65, rgba(43, 43, 43, .65));
		--iwf-ease: cubic-bezier(.16, 1, .3, 1);

		/* Times read as their own colour, not as the site's accent. */
		--iwf-slot-ink: #813527;
		--iwf-slot-bg: rgba(129, 53, 39, .12);

		box-sizing: border-box;
		width: 100%;
		max-width: var(--ioulia-shell, 100%);
		margin-inline: auto;
		padding: clamp(4rem, 10vw, 9rem) var(--ioulia-page-x, 1.25rem);
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

	.iwf__eyebrow { margin: 0; color: var(--iwf-muted); font-size: .74rem; letter-spacing: .16em; text-transform: uppercase; }
	.iwf__title { margin: .25em 0 0; font-size: clamp(2.8rem, 7vw, 5.2rem); font-weight: 300; letter-spacing: -.045em; line-height: 1; }
	.iwf__intro { margin: 2rem 0 0; max-width: 40ch; color: var(--iwf-muted); font-size: .9rem; line-height: 1.65; }

	.iwf__chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: clamp(2rem, 4vw, 3rem); }
	.iwf__chips--times { margin-top: .8rem; }

	.iwf__chip {
		padding: .55rem 1rem;
		border: 1px solid var(--iwf-line);
		border-radius: 999px;
		background: transparent;
		color: var(--iwf-muted);
		font: inherit;
		font-size: .78rem;
		cursor: pointer;
		transition: color .25s ease, border-color .25s ease, background-color .25s ease, transform .25s var(--iwf-ease);
	}
	.iwf__chip:hover, .iwf__chip:focus-visible { border-color: var(--iwf-ink); color: var(--iwf-ink); outline: none; transform: translateY(-2px); }
	.iwf__chip.is-current { border-color: var(--iwf-ink); background: var(--iwf-ink); color: var(--iwf-paper); }

	.iwf__schedule { margin-top: clamp(1.6rem, 3vw, 2.4rem); border-top: 1px solid var(--iwf-line); }
	.iwf__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem 0; border-bottom: 1px solid var(--iwf-line); }
	.iwf__row-day { color: var(--iwf-muted); font-size: .74rem; letter-spacing: .12em; text-transform: uppercase; white-space: nowrap; }
	.iwf__row-times { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }

	.iwf__slot {
		padding: .35rem .8rem;
		border: 1px solid transparent;
		border-radius: 999px;
		background: var(--iwf-slot-bg);
		color: var(--iwf-slot-ink);
		font: inherit;
		font-size: .76rem;
		font-variant-numeric: tabular-nums;
		cursor: pointer;
		opacity: 0;
		transform: translateY(6px);
		animation: iwf-slot-in .5s var(--iwf-ease) forwards;
		transition: background-color .25s ease, color .25s ease, transform .25s var(--iwf-ease);
	}
	.iwf__slot:hover, .iwf__slot:focus-visible { background: var(--iwf-slot-ink); color: var(--iwf-paper); outline: none; transform: translateY(-2px); }
	.iwf__slot.is-current { background: var(--iwf-slot-ink); color: var(--iwf-paper); }

	@keyframes iwf-slot-in { to { opacity: 1; transform: translateY(0); } }

	.iwf__open { margin-top: clamp(2rem, 4vw, 3rem); min-height: 52px; padding-inline: 2rem; }

	[data-iwf-reveal] { opacity: 0; transform: translateY(18px); transition: opacity .8s var(--iwf-ease), transform .8s var(--iwf-ease); }
	[data-iwf-reveal].is-in { opacity: 1; transform: none; }

	@media (min-width: 900px) {
		.iwf__grid { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: clamp(3rem, 6vw, 7rem); }
	}

	/* ---- Dialog ----
	   A sheet on a phone and a card on a desktop, from one set of rules. The
	   step's own title carries the hierarchy rather than a bar across the top,
	   which is what makes it read as a screen instead of a window. */

	.iwf-modal {
		--iwf-ink: var(--ioulia-ink, #2B2B2B);
		--iwf-paper: var(--ioulia-paper, #FFFEF7);
		--iwf-line: var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		--iwf-muted: var(--ioulia-ink-65, rgba(43, 43, 43, .65));
		--iwf-ease: cubic-bezier(.16, 1, .3, 1);
		--iwf-slot-ink: #813527;
		--iwf-slot-bg: rgba(129, 53, 39, .12);
		--iwf-card: 14px;
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

	.iwf-modal__backdrop { position: absolute; inset: 0; background: rgba(43, 43, 43, .4); opacity: 0; transition: opacity .4s ease; }
	.iwf-modal.is-open .iwf-modal__backdrop { opacity: 1; }

	.iwf-modal__dialog {
		position: relative;
		display: flex;
		flex-direction: column;
		width: min(600px, 100%);
		/* svh, so mobile browser chrome cannot clip the footer. */
		max-height: 92svh;
		border-radius: 22px 22px 0 0;
		background: var(--iwf-paper);
		box-shadow: 0 -8px 40px rgba(43, 43, 43, .14);
		transform: translateY(28px);
		opacity: 0;
		transition: transform .5s var(--iwf-ease), opacity .3s ease;
	}
	.iwf-modal.is-open .iwf-modal__dialog { transform: none; opacity: 1; }

	.iwf-modal__grab { display: block; width: 36px; height: 4px; margin: 10px auto 0; border-radius: 999px; background: var(--iwf-line); }

	.iwf-modal__head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem var(--iwf-gutter) 0; }
	.iwf-modal__step { margin: 0; color: var(--iwf-muted); font-size: .68rem; letter-spacing: .16em; font-variant-numeric: tabular-nums; }

	.iwf-modal__close {
		display: grid;
		place-items: center;
		width: 40px;
		height: 40px;
		margin-right: -8px;
		padding: 0;
		border: 0;
		border-radius: 999px;
		background: transparent;
		color: inherit;
		cursor: pointer;
		transition: background-color .2s ease;
	}
	.iwf-modal__close:hover, .iwf-modal__close:focus-visible { background: rgba(43, 43, 43, .07); outline: none; }
	.iwf-modal__close svg { width: 15px; height: 15px; }

	.iwf-modal__body {
		flex: 1 1 auto;
		/* Without this a flex child refuses to shrink, the body grows to its
		   content, the dialog overflows, and the page behind scrolls instead. */
		min-height: 0;
		overflow-y: auto;
		overscroll-behavior: contain;
		-webkit-overflow-scrolling: touch;
		padding: .5rem var(--iwf-gutter) 1.5rem;
	}

	.iwf-modal__title { margin: 0; font-size: clamp(1.6rem, 5.5vw, 2.15rem); font-weight: 500; letter-spacing: -.035em; line-height: 1.1; }
	.iwf-modal__lead { margin: .5rem 0 0; color: var(--iwf-muted); font-size: .92rem; line-height: 1.5; }
	.iwf-modal__lead[hidden] { display: none; }

	.iwf-step { margin-top: 1.75rem; }
	.iwf-step[hidden] { display: none; }

	.iwf-modal__foot {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		padding: .9rem var(--iwf-gutter) calc(.9rem + env(safe-area-inset-bottom));
		border-top: 1px solid var(--iwf-line);
		background: var(--iwf-paper);
	}
	.iwf-modal__back { padding: .5rem 0; border: 0; background: none; color: var(--iwf-ink); font: inherit; font-size: .86rem; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
	.iwf-modal__back[hidden] { display: none; }
	.iwf-modal__note { margin: 0 0 0 auto; color: var(--iwf-muted); font-size: .76rem; text-align: right; }

	@media (min-width: 700px) {
		.iwf-modal { align-items: center; padding: 2rem; }
		.iwf-modal__dialog { max-height: 86vh; border-radius: var(--iwf-card); box-shadow: 0 24px 70px rgba(43, 43, 43, .2); }
		.iwf-modal__grab { display: none; }
		.iwf-modal__head { padding-top: 1rem; }
	}

	/* ---- Step 1: programmes ---- */

	.iwf__options { display: grid; gap: .6rem; }
	.iwf__option {
		display: block;
		width: 100%;
		padding: 1.1rem 1.15rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--iwf-card);
		background: transparent;
		color: inherit;
		font: inherit;
		text-align: left;
		cursor: pointer;
		transition: border-color .22s ease, box-shadow .22s ease, transform .22s var(--iwf-ease);
	}
	.iwf__option:hover, .iwf__option:focus-visible { border-color: var(--iwf-ink); outline: none; transform: translateY(-2px); }
	/* Two rings rather than a thicker border: the row must not resize when picked. */
	.iwf__option.is-current { border-color: var(--iwf-ink); box-shadow: inset 0 0 0 1px var(--iwf-ink); }

	.iwf__option-top { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; }
	.iwf__option-title { font-size: 1.08rem; font-weight: 500; letter-spacing: -.015em; }
	.iwf__option-price { color: var(--iwf-slot-ink); font-size: .88rem; white-space: nowrap; }
	.iwf__option-summary { display: block; margin-top: .3rem; color: var(--iwf-muted); font-size: .86rem; line-height: 1.5; }

	/* ---- Step 2: calendar ---- */

	.iwf-cal__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
	.iwf-cal__month { font-size: 1rem; font-weight: 500; letter-spacing: -.015em; }
	.iwf-cal__nav { display: flex; gap: .4rem; }
	.iwf-cal__nav button {
		display: grid;
		place-items: center;
		width: 40px;
		height: 40px;
		border: 1px solid var(--iwf-line);
		border-radius: 999px;
		background: transparent;
		color: inherit;
		font: inherit;
		cursor: pointer;
		transition: border-color .2s ease, opacity .2s ease;
	}
	.iwf-cal__nav button:hover:not(:disabled) { border-color: var(--iwf-ink); }
	.iwf-cal__nav button:disabled { opacity: .25; cursor: default; }

	.iwf-cal__grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; }
	.iwf-cal__dayname { padding: .3rem 0 .5rem; color: var(--iwf-muted); font-size: .66rem; letter-spacing: .06em; text-align: center; text-transform: uppercase; }

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
		font-size: .92rem;
		font-variant-numeric: tabular-nums;
		opacity: .3;
		cursor: default;
	}
	.iwf-cal__day.is-open {
		background: var(--iwf-slot-bg);
		color: var(--iwf-slot-ink);
		font-weight: 500;
		opacity: 1;
		cursor: pointer;
		transition: background-color .2s ease, color .2s ease, transform .2s var(--iwf-ease);
	}
	.iwf-cal__day.is-open:hover, .iwf-cal__day.is-open:focus-visible { background: var(--iwf-slot-ink); color: var(--iwf-paper); outline: none; transform: scale(1.06); }
	.iwf-cal__day.is-current { background: var(--iwf-slot-ink); color: var(--iwf-paper); }

	.iwf-times { margin-top: 1.6rem; padding-top: 1.3rem; border-top: 1px solid var(--iwf-line); }
	.iwf-times[hidden] { display: none; }
	.iwf-times__label { margin: 0; font-size: .95rem; font-weight: 500; letter-spacing: -.01em; }
	.iwf__chips--times { margin-top: .75rem; }
	.iwf__chips--times .iwf__chip {
		padding: .6rem 1.05rem;
		border-radius: 999px;
		border-color: transparent;
		background: var(--iwf-slot-bg);
		color: var(--iwf-slot-ink);
		font-size: .88rem;
		font-variant-numeric: tabular-nums;
	}
	.iwf__chips--times .iwf__chip:hover,
	.iwf__chips--times .iwf__chip:focus-visible,
	.iwf__chips--times .iwf__chip.is-current { background: var(--iwf-slot-ink); color: var(--iwf-paper); }

	/* ---- Step 3: details ---- */

	.iwf__chosen { margin: 0 0 1.2rem; padding-left: .8rem; border-left: 2px solid var(--iwf-slot-ink); color: var(--iwf-muted); font-size: .84rem; }
	.iwf__error { margin: 1.2rem 0 0; padding: .8rem 1rem; background: var(--iwf-slot-bg); color: var(--iwf-slot-ink); font-size: .84rem; }
	.iwf__error[hidden] { display: none; }

	.iwf__people { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1.2rem; }
	.iwf__stepper { display: flex; align-items: center; gap: .4rem; }
	.iwf__stepper button { width: 44px; height: 44px; border: 1px solid var(--iwf-line); border-radius: var(--ioulia-radius, 5px); background: transparent; color: inherit; font: inherit; font-size: 1.1rem; cursor: pointer; transition: border-color .2s ease; }
	.iwf__stepper button:hover { border-color: currentColor; }
	.iwf__stepper output { min-width: 2ch; text-align: center; font-variant-numeric: tabular-nums; }

	.iwf__fields { display: grid; gap: 1rem; }
	.iwf__field > span { display: block; margin-bottom: .35rem; color: var(--iwf-muted); font-size: .76rem; }
	.iwf__field em { font-style: normal; opacity: .7; }
	.iwf__field input, .iwf__field textarea {
		display: block;
		width: 100%;
		padding: .75rem .85rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--ioulia-radius, 5px);
		background: #fff;
		color: inherit;
		font: inherit;
		/* 16px keeps iOS from zooming the page when a field takes focus. */
		font-size: 16px;
		resize: vertical;
		transition: border-color .2s ease;
	}
	.iwf__field input:focus, .iwf__field textarea:focus { border-color: currentColor; outline: none; }

	.iwf__consent { display: flex; gap: .7rem; margin-top: 1.3rem; color: var(--iwf-muted); font-size: .8rem; line-height: 1.5; }
	.iwf__consent input { width: 22px; height: 22px; margin: 0; flex: 0 0 auto; accent-color: var(--iwf-slot-ink); }
	.iwf__consent a { color: inherit; }

	.iwf__trap { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
	.iwf__submit { width: 100%; margin-top: 1.6rem; min-height: 52px; }

	.iwf__done p { margin: 0 0 .7rem; color: var(--iwf-muted); font-size: .9rem; line-height: 1.55; }
	.iwf__done p:first-child { color: var(--iwf-ink); font-size: 1rem; }

	@media (min-width: 560px) {
		.iwf__fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.iwf__field--wide { grid-column: 1 / -1; }
	}

	@media (prefers-reduced-motion: reduce) {
		[data-iwf-reveal], .iwf__visual img, .iwf__slot, .iwf-modal__dialog, .iwf-modal__backdrop {
			transition: none;
			animation: none;
			opacity: 1;
			transform: none;
		}
	}
</style>

<script id="ioulia-workshops-form-js">
(function () {
	var root = document.querySelector('[data-iwf]');
	if (!root || root.dataset.iwfReady) { return; }
	root.dataset.iwfReady = '1';

	var payload = root.querySelector('[data-iwf-availability]');
	var programmes = payload ? JSON.parse(payload.textContent || '[]') : [];
	var words = JSON.parse(root.dataset.words || '{}');

	/* Blocks settle in as they come into view, staggered by order of arrival. */
	var reveals = root.querySelectorAll('[data-iwf-reveal]');
	if (window.IntersectionObserver) {
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
	} else {
		reveals.forEach(function (node) { node.classList.add('is-in'); });
	}

	if (!programmes.length) { return; }

	var modal = root.querySelector('[data-iwf-modal]');
	var picked = { programme: programmes[0], date: null, time: null, people: 1 };
	var month = null;
	var step = 1;
	var lastFocused = null;

	var el = {
		chips: root.querySelector('[data-iwf-programmes]'),
		schedule: root.querySelector('[data-iwf-schedule]'),
		options: root.querySelector('[data-iwf-options]'),
		calendar: root.querySelector('[data-iwf-calendar]'),
		times: root.querySelector('[data-iwf-times]'),
		timesLabel: root.querySelector('[data-iwf-times-label]'),
		timesList: root.querySelector('[data-iwf-times-list]'),
		stepLabel: root.querySelector('[data-iwf-steplabel]'),
		lead: root.querySelector('[data-iwf-lead]'),
		title: root.querySelector('[data-iwf-modal-title]'),
		back: root.querySelector('[data-iwf-back]'),
		footNote: root.querySelector('[data-iwf-foot-note]'),
		form: root.querySelector('[data-iwf-form]'),
		chosen: root.querySelector('[data-iwf-chosen]'),
		error: root.querySelector('[data-iwf-error]'),
		summary: root.querySelector('[data-iwf-summary]'),
		people: root.querySelector('[data-iwf-people-value]'),
		scroll: root.querySelector('[data-iwf-scroll]')
	};

	var STEPS = { 1: 'πρόγραμμα', 2: 'ημερομηνία', 3: 'στοιχεία' };

	var HEADINGS = {
		1: ['Τι θέλεις να κάνεις;', 'Πέντε εργαστήρια, όλα ανοιχτά και σε αρχάριους.'],
		2: ['', 'Διάλεξε ημέρα και ώρα.'],
		3: ['Τα στοιχεία σου', 'Θα λάβεις email με την επιβεβαίωση.'],
		done: ['Η θέση σου κρατήθηκε.', '']
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
					openModal(3);
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
			node.innerHTML =
				'<span class="iwf__option-top">' +
					'<span class="iwf__option-title"></span>' +
					'<span class="iwf__option-price"></span>' +
				'</span>' +
				'<span class="iwf__option-summary"></span>';

			node.querySelector('.iwf__option-title').textContent = programme.title;
			node.querySelector('.iwf__option-price').textContent = programme.price + ' €';
			node.querySelector('.iwf__option-summary').textContent = programme.summary;

			node.addEventListener('click', function () {
				picked.programme = programme;
				picked.date = null;
				picked.time = null;
				month = null;
				renderChips();
				renderSchedule();
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

	function renderCalendar() {
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
			button.setAttribute('aria-label', spec[1] < 0 ? 'Προηγούμενος μήνας' : 'Επόμενος μήνας');
			button.addEventListener('click', function () {
				month = months[position + spec[1]];
				renderCalendar();
			});
			nav.appendChild(button);
		});

		head.appendChild(label);
		head.appendChild(nav);
		el.calendar.appendChild(head);

		var grid = document.createElement('div');
		grid.className = 'iwf-cal__grid';

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
				cell.addEventListener('click', function (chosen) {
					return function () { chooseDate(chosen); renderCalendar(); };
				}(date));
			}

			grid.appendChild(cell);
		}

		el.calendar.appendChild(grid);
	}

	function chooseDate(date) {
		picked.date = date;
		picked.time = null;
		month = monthKey(date.date);

		el.timesLabel.textContent = date.full;
		el.timesList.textContent = '';

		date.times.forEach(function (time) {
			var node = document.createElement('button');
			node.type = 'button';
			node.className = 'iwf__chip';
			node.textContent = time.label;
			node.title = time.left === 1 ? '1 θέση' : time.left + ' θέσεις';
			node.addEventListener('click', function () {
				chooseTime(time);
				goTo(3);
			});
			el.timesList.appendChild(node);
		});

		el.times.hidden = false;
		updateFoot();
	}

	function chooseTime(time) {
		picked.time = time;
		picked.people = 1;
		el.people.textContent = '1';
		el.chosen.textContent = [picked.programme.title, picked.date.full, time.label].join('  ·  ');
		updateFoot();
	}

	function updateFoot() {
		el.footNote.textContent = [
			picked.programme ? picked.programme.title : '',
			picked.date ? picked.date.full : '',
			picked.time ? picked.time.label : ''
		].filter(Boolean).join('  ·  ');
	}

	/* ---- Steps ---- */

	function goTo(next) {
		step = next;

		modal.querySelectorAll('[data-iwf-step]').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-iwf-step') !== String(next);
		});

		if (next === 2) { renderCalendar(); }

		el.stepLabel.textContent = STEPS[next] ? '0' + next + ' / 03' : '';
		el.title.textContent = HEADINGS[next] ? HEADINGS[next][0] : picked.programme.title;
		el.lead.textContent = HEADINGS[next] ? HEADINGS[next][1] : '';
		el.lead.hidden = !el.lead.textContent;

		if (next === 2) { el.title.textContent = picked.programme.title; }

		el.back.hidden = next === 1 || next === 'done';
		showError('');
		updateFoot();

		/* Each step starts at the top of the dialog, never of the page. */
		el.scroll.scrollTop = 0;
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
		lastFocused = document.activeElement;
		modal.hidden = false;
		lockPage();
		renderOptions();
		goTo(at || (picked.time ? 3 : 1));

		/* Force layout with the dialog still at opacity 0, then reveal in the same
		   task. requestAnimationFrame would read better, but a browser stops
		   servicing it in a background tab, and a dialog that opens invisible is
		   worse than one that opens without its transition. */
		void modal.offsetHeight;
		modal.classList.add('is-open');

		var close = modal.querySelector('.iwf-modal__close');
		if (close) { close.focus(); }
	}

	function closeModal() {
		modal.classList.remove('is-open');
		unlockPage();

		window.setTimeout(function () {
			modal.hidden = true;
			if (lastFocused) { lastFocused.focus(); }
		}, 320);
	}

	root.querySelector('[data-iwf-open]').addEventListener('click', function () { openModal(1); });

	modal.querySelectorAll('[data-iwf-close]').forEach(function (node) {
		node.addEventListener('click', closeModal);
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

	/* ---- Step 3 ---- */

	root.querySelectorAll('[data-iwf-people]').forEach(function (control) {
		control.addEventListener('click', function () {
			var change = Number(control.getAttribute('data-iwf-people'));
			var most = picked.time ? picked.time.left : 1;

			picked.people = Math.min(most, Math.max(1, picked.people + change));
			el.people.textContent = String(picked.people);
			showError(picked.people === most && change > 0 ? 'Αυτές είναι οι διαθέσιμες θέσεις.' : '');
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

		var submit = el.form.querySelector('.iwf__submit');
		submit.disabled = true;
		submit.textContent = 'Στέλνουμε...';

		fetch(root.dataset.ajax, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (response) { return response.json(); })
			.then(function (result) {
				submit.disabled = false;
				submit.textContent = 'Ολοκλήρωση κράτησης';

				if (!result || !result.success) {
					showError((result && result.data && result.data.message) || 'Κάτι πήγε στραβά.');
					return;
				}

				el.summary.textContent = result.data.programme + '  ·  ' + result.data.when;
				goTo('done');
				el.footNote.textContent = '';
			})
			.catch(function () {
				submit.disabled = false;
				submit.textContent = 'Ολοκλήρωση κράτησης';
				showError('Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.');
			});
	});

	renderChips();
	renderSchedule();
}());
</script>
		<?php
	}
}
