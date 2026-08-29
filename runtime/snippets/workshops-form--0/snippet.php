<?php
/**
 * Workshop booking form — what the visitor fills in.
 *
 * Answers [ioulia_workshops], which the home canvas and the Book Workshop page
 * already use, so replacing the old plugin needs no change to either.
 *
 * One screen, not a wizard. A row of programmes, then the dates that programme
 * runs with their times beside them, then the details. Everything stays visible,
 * which is how a weekly schedule is actually read: "Friday at 15:00", not four
 * separate questions.
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
		$settings     = ioulia_workshop_settings();
		$privacy      = get_privacy_policy_url();
		$visuals      = ioulia_workshops_visuals();

		ob_start();
		?>
		<section class="iwf" data-iwf
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_book' ) ); ?>">

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

						<div class="iwf__chips iwf__chips--programmes" data-iwf-programmes data-iwf-reveal role="group" aria-label="Πρόγραμμα"></div>

						<div class="iwf__schedule" data-iwf-schedule data-iwf-reveal></div>

						<p class="iwf__intro" data-iwf-reveal>Μαθήματα πηλοπλαστικής και τροχού για όσους θέλουν να επιβραδύνουν και να δημιουργήσουν κάτι δικό τους. Όλα τα υλικά, τα εργαλεία και τα ψησίματα περιλαμβάνονται.</p>

						<button type="button" class="ioulia-btn ioulia-btn--filled iwf__open" data-iwf-open data-iwf-reveal>Κλείσε τη θέση σου</button>

					<?php endif; ?>
				</div>
			</div>

			<div class="iwf-modal" data-iwf-modal hidden>
				<div class="iwf-modal__backdrop" data-iwf-close></div>

				<div class="iwf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="iwf-modal-title">
					<header class="iwf-modal__head">
						<h3 id="iwf-modal-title" data-iwf-modal-title>Κλείσε τη θέση σου</h3>
						<button type="button" class="iwf-modal__close" data-iwf-close aria-label="Κλείσιμο">×</button>
					</header>

					<div class="iwf-modal__body">
						<div class="iwf__chips iwf__chips--programmes" data-iwf-modal-programmes role="group" aria-label="Πρόγραμμα"></div>

						<div class="iwf__schedule iwf__schedule--full" data-iwf-modal-schedule></div>

						<p class="iwf__error" data-iwf-error hidden role="alert"></p>

						<form class="iwf__form" data-iwf-form novalidate hidden>
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

						<div class="iwf__done" data-iwf-done hidden>
							<h4>Η θέση σου κρατήθηκε.</h4>
							<p data-iwf-summary></p>
							<p>Σου στείλαμε email με τα στοιχεία. Θα σε περιμένουμε στο εργαστήριο.</p>
						</div>
					</div>
				</div>
			</div>

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
		--iwf-accent: var(--ioulia-accent, #7C3737);
		--iwf-peach: var(--ioulia-peach, #FECAA7);
		--iwf-ease: cubic-bezier(.16, 1, .3, 1);

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

	/* Photographs: two panels, the second dropped and taller, as in the reference. */
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
	.iwf__hint { margin: 1.2rem 0 0; color: var(--iwf-muted); font-size: .76rem; }

	.iwf__chips { display: flex; flex-wrap: wrap; gap: .4rem; }
	.iwf__chips--programmes { margin-top: clamp(2rem, 4vw, 3rem); }

	.iwf__chip {
		padding: .5rem .85rem;
		border: 1px solid var(--iwf-line);
		border-radius: 2px;
		background: transparent;
		color: var(--iwf-muted);
		font: inherit;
		font-size: .78rem;
		letter-spacing: .01em;
		cursor: pointer;
		transition: color .25s ease, border-color .25s ease, background-color .25s ease, transform .25s var(--iwf-ease);
	}
	.iwf__chip:hover, .iwf__chip:focus-visible { border-color: var(--iwf-ink); color: var(--iwf-ink); outline: none; transform: translateY(-2px); }
	.iwf__chip.is-current { border-color: var(--iwf-ink); background: var(--iwf-ink); color: var(--iwf-paper); }

	/* Schedule: a day on the left, its times on the right, one rule between. */
	.iwf__schedule { margin-top: clamp(1.6rem, 3vw, 2.4rem); border-top: 1px solid var(--iwf-line); }
	.iwf__row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		padding: .7rem 0;
		border-bottom: 1px solid var(--iwf-line);
	}
	.iwf__row-day { color: var(--iwf-muted); font-size: .74rem; letter-spacing: .12em; text-transform: uppercase; white-space: nowrap; }
	.iwf__row-times { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }

	.iwf__slot {
		padding: .3rem .7rem;
		border: 1px solid transparent;
		border-radius: 2px;
		background: var(--iwf-peach);
		color: var(--iwf-ink);
		font: inherit;
		font-size: .76rem;
		font-variant-numeric: tabular-nums;
		cursor: pointer;
		opacity: 0;
		transform: translateY(6px);
		animation: iwf-slot-in .5s var(--iwf-ease) forwards;
		transition: background-color .25s ease, color .25s ease, transform .25s var(--iwf-ease);
	}
	.iwf__slot:hover, .iwf__slot:focus-visible { background: var(--iwf-ink); color: var(--iwf-paper); outline: none; transform: translateY(-2px); }
	.iwf__slot.is-current { background: var(--iwf-ink); color: var(--iwf-paper); }

	@keyframes iwf-slot-in { to { opacity: 1; transform: translateY(0); } }

	.iwf__open { margin-top: clamp(2rem, 4vw, 3rem); min-height: 52px; padding-inline: 2rem; }

	/* Reveal on approach, staggered by position rather than by a fixed list. */
	[data-iwf-reveal] { opacity: 0; transform: translateY(18px); transition: opacity .8s var(--iwf-ease), transform .8s var(--iwf-ease); }
	[data-iwf-reveal].is-in { opacity: 1; transform: none; }

	@media (prefers-reduced-motion: reduce) {
		[data-iwf-reveal], .iwf__visual img, .iwf__slot { transition: none; animation: none; opacity: 1; transform: none; }
	}

	@media (min-width: 900px) {
		.iwf__grid { grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: clamp(3rem, 6vw, 7rem); }
	}

	/* ---- Dialog ---- */

	.iwf-modal { position: fixed; inset: 0; z-index: 100000; display: flex; align-items: flex-end; justify-content: center; }
	.iwf-modal[hidden] { display: none; }
	.iwf-modal__backdrop { position: absolute; inset: 0; background: rgba(43, 43, 43, .38); opacity: 0; transition: opacity .4s ease; }
	.iwf-modal.is-open .iwf-modal__backdrop { opacity: 1; }

	.iwf-modal__dialog {
		position: relative;
		display: flex;
		flex-direction: column;
		width: min(640px, 100%);
		max-height: 92svh;
		background: var(--ioulia-paper, #FFFEF7);
		color: var(--ioulia-ink, #2B2B2B);
		font-family: var(--ioulia-font, sans-serif);
		transform: translateY(24px);
		opacity: 0;
		transition: transform .5s cubic-bezier(.16, 1, .3, 1), opacity .35s ease;
	}
	.iwf-modal.is-open .iwf-modal__dialog { transform: none; opacity: 1; }

	.iwf-modal__head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		padding: 1.2rem clamp(1.1rem, 3vw, 2rem);
		border-bottom: 1px solid var(--ioulia-ink-12, rgba(43, 43, 43, .12));
	}
	.iwf-modal__head h3 { margin: 0; font-size: 1.05rem; font-weight: 400; letter-spacing: -.01em; }
	.iwf-modal__close {
		width: 44px;
		height: 44px;
		margin-right: -.6rem;
		border: 0;
		background: none;
		color: inherit;
		font-size: 1.6rem;
		line-height: 1;
		cursor: pointer;
	}
	.iwf-modal__body { flex: 1 1 auto; overflow-y: auto; padding: clamp(1.1rem, 3vw, 2rem) clamp(1.1rem, 3vw, 2rem) calc(1.6rem + env(safe-area-inset-bottom)); -webkit-overflow-scrolling: touch; }
	.iwf-modal__body .iwf__chips--programmes { margin-top: 0; }
	.iwf-modal__body .iwf__schedule { margin-top: 1.4rem; }

	@media (min-width: 700px) {
		.iwf-modal { align-items: center; padding: 2rem; }
		.iwf-modal__dialog { max-height: 86vh; border-radius: var(--ioulia-radius, 5px); }
	}

	/* ---- Details ---- */

	.iwf__form[hidden], .iwf__done[hidden] { display: none; }
	.iwf__form { margin-top: 1.6rem; padding-top: 1.4rem; border-top: 1px solid var(--ioulia-ink-12, rgba(43, 43, 43, .12)); }
	.iwf__chosen { margin: 0 0 1.2rem; padding-left: .8rem; border-left: 2px solid var(--ioulia-accent, #7C3737); color: var(--ioulia-ink-65, rgba(43, 43, 43, .65)); font-size: .84rem; }
	.iwf__error { margin: 1.2rem 0 0; padding: .8rem 1rem; background: rgba(124, 55, 55, .08); color: var(--ioulia-accent, #7C3737); font-size: .84rem; }
	.iwf__error[hidden] { display: none; }

	.iwf__people { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1.2rem; }
	.iwf__stepper { display: flex; align-items: center; gap: .4rem; }
	.iwf__stepper button {
		width: 44px;
		height: 44px;
		border: 1px solid var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: inherit;
		font: inherit;
		font-size: 1.1rem;
		cursor: pointer;
		transition: border-color .2s ease;
	}
	.iwf__stepper button:hover { border-color: currentColor; }
	.iwf__stepper output { min-width: 2ch; text-align: center; font-variant-numeric: tabular-nums; }

	.iwf__fields { display: grid; gap: 1rem; }
	.iwf__field > span { display: block; margin-bottom: .35rem; color: var(--ioulia-ink-65, rgba(43, 43, 43, .65)); font-size: .76rem; }
	.iwf__field em { font-style: normal; opacity: .7; }
	.iwf__field input, .iwf__field textarea {
		display: block;
		width: 100%;
		padding: .75rem .85rem;
		border: 1px solid var(--ioulia-ink-12, rgba(43, 43, 43, .12));
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

	.iwf__consent { display: flex; gap: .7rem; margin-top: 1.3rem; color: var(--ioulia-ink-65, rgba(43, 43, 43, .65)); font-size: .8rem; line-height: 1.5; }
	.iwf__consent input { width: 22px; height: 22px; margin: 0; flex: 0 0 auto; accent-color: var(--ioulia-ink, #2B2B2B); }
	.iwf__consent a { color: inherit; }

	.iwf__trap { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
	.iwf__submit { width: 100%; margin-top: 1.6rem; min-height: 52px; }

	.iwf__done h4 { margin: 1.5rem 0 0; font-size: 1.2rem; font-weight: 400; }
	.iwf__done p { margin: .7rem 0 0; color: var(--ioulia-ink-65, rgba(43, 43, 43, .65)); font-size: .88rem; }

	@media (min-width: 560px) {
		.iwf__fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.iwf__field--wide { grid-column: 1 / -1; }
	}
</style>

<script id="ioulia-workshops-form-js">
(function () {
	var root = document.querySelector('[data-iwf]');
	if (!root || root.dataset.iwfReady) { return; }
	root.dataset.iwfReady = '1';

	var payload = root.querySelector('[data-iwf-availability]');
	var programmes = payload ? JSON.parse(payload.textContent || '[]') : [];

	/* Reveal on approach. Staggered by order of arrival so a column of elements
	   settles rather than appearing all at once. */
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
	var lastFocused = null;

	var el = {
		inlineChips: root.querySelector('[data-iwf-programmes]'),
		inlineDays: root.querySelector('[data-iwf-schedule]'),
		modalChips: root.querySelector('[data-iwf-modal-programmes]'),
		modalDays: root.querySelector('[data-iwf-modal-schedule]'),
		title: root.querySelector('[data-iwf-modal-title]'),
		form: root.querySelector('[data-iwf-form]'),
		chosen: root.querySelector('[data-iwf-chosen]'),
		error: root.querySelector('[data-iwf-error]'),
		done: root.querySelector('[data-iwf-done]'),
		summary: root.querySelector('[data-iwf-summary]'),
		people: root.querySelector('[data-iwf-people-value]')
	};

	function showError(message) {
		el.error.textContent = message || '';
		el.error.hidden = !message;
	}

	function chip(label, current) {
		var node = document.createElement('button');
		node.type = 'button';
		node.className = 'iwf__chip' + (current ? ' is-current' : '');
		node.textContent = label;
		return node;
	}

	function renderProgrammes(target, onPick) {
		target.textContent = '';

		programmes.forEach(function (programme) {
			var node = chip(programme.title, programme === picked.programme);
			node.addEventListener('click', function () { onPick(programme); });
			target.appendChild(node);
		});
	}

	/* The schedule is the reference's shape: a day, then its times beside it. */
	function renderSchedule(target, limit, onPick) {
		target.textContent = '';

		var dates = picked.programme.dates.slice(0, limit || picked.programme.dates.length);

		dates.forEach(function (date, index) {
			var row = document.createElement('div');
			row.className = 'iwf__row';

			var day = document.createElement('span');
			day.className = 'iwf__row-day';
			day.textContent = date.day + ' ' + date.num + ' ' + date.month;

			var times = document.createElement('span');
			times.className = 'iwf__row-times';

			date.times.forEach(function (time, position) {
				var node = document.createElement('button');
				node.type = 'button';
				node.className = 'iwf__slot' + (picked.time && picked.time.starts === time.starts ? ' is-current' : '');
				node.textContent = time.label.split(' ')[0];
				node.title = time.left === 1 ? '1 θέση' : time.left + ' θέσεις';
				node.style.animationDelay = ((index * 3) + position) * 35 + 'ms';
				node.addEventListener('click', function () { onPick(date, time); });
				times.appendChild(node);
			});

			row.appendChild(day);
			row.appendChild(times);
			target.appendChild(row);
		});
	}

	function describe() {
		el.chosen.textContent = [
			picked.programme.title,
			picked.date ? picked.date.full : '',
			picked.time ? picked.time.label : ''
		].filter(Boolean).join('  ·  ');
	}

	function chooseTime(date, time) {
		picked.date = date;
		picked.time = time;
		picked.people = 1;
		el.people.textContent = '1';
		renderSchedule(el.modalDays, 0, chooseTime);
		describe();
		el.form.hidden = false;
		el.done.hidden = true;
		showError('');
	}

	function chooseProgramme(programme, inModal) {
		picked.programme = programme;
		picked.date = null;
		picked.time = null;

		renderProgrammes(el.inlineChips, function (next) { chooseProgramme(next, false); });
		renderSchedule(el.inlineDays, 3, function (date, time) {
			chooseTime(date, time);
			openModal();
		});

		if (inModal || modal.classList.contains('is-open')) {
			renderProgrammes(el.modalChips, function (next) { chooseProgramme(next, true); });
			renderSchedule(el.modalDays, 0, chooseTime);
			el.title.textContent = programme.title;
			el.form.hidden = true;
		}
	}

	/* ---- Dialog ---- */

	function openModal() {
		lastFocused = document.activeElement;
		modal.hidden = false;
		document.body.style.overflow = 'hidden';

		renderProgrammes(el.modalChips, function (next) { chooseProgramme(next, true); });
		renderSchedule(el.modalDays, 0, chooseTime);
		el.title.textContent = picked.programme.title;
		el.form.hidden = !picked.time;
		el.done.hidden = true;

		window.requestAnimationFrame(function () {
			modal.classList.add('is-open');
			modal.querySelector('.iwf-modal__close').focus();
		});
	}

	function closeModal() {
		modal.classList.remove('is-open');
		document.body.style.overflow = '';

		window.setTimeout(function () {
			modal.hidden = true;
			if (lastFocused) { lastFocused.focus(); }
		}, 320);
	}

	root.querySelector('[data-iwf-open]').addEventListener('click', openModal);

	modal.querySelectorAll('[data-iwf-close]').forEach(function (node) {
		node.addEventListener('click', closeModal);
	});

	document.addEventListener('keydown', function (event) {
		if ('Escape' === event.key && modal.classList.contains('is-open')) { closeModal(); }
	});

	/* Keep tabbing inside the dialog while it is open. */
	modal.addEventListener('keydown', function (event) {
		if ('Tab' !== event.key) { return; }

		var focusable = modal.querySelectorAll('button, input, textarea, a[href], select');
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

	/* ---- Details ---- */

	root.querySelectorAll('[data-iwf-people]').forEach(function (control) {
		control.addEventListener('click', function () {
			var step = Number(control.getAttribute('data-iwf-people'));
			var most = picked.time ? picked.time.left : 1;

			picked.people = Math.min(most, Math.max(1, picked.people + step));
			el.people.textContent = String(picked.people);
			showError(picked.people === most && step > 0 ? 'Αυτές είναι οι διαθέσιμες θέσεις.' : '');
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
				el.form.hidden = true;
				el.done.hidden = false;
				showError('');
			})
			.catch(function () {
				submit.disabled = false;
				submit.textContent = 'Ολοκλήρωση κράτησης';
				showError('Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.');
			});
	});

	chooseProgramme(programmes[0], false);
}());
</script>
		<?php
	}
}
