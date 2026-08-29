<?php
/**
 * Workshop booking form — what the visitor fills in.
 *
 * Answers [ioulia_workshops], which the home canvas and the Book Workshop page
 * already use, so replacing the old plugin needs no change to either.
 *
 * Four steps on purpose: programme, day, time, details. Each one is short enough
 * to read on a phone without scrolling, and the choice already made stays
 * visible above so nobody loses their place.
 *
 * Availability is rendered into the page as data rather than fetched per step,
 * so moving between steps is instant and no session is ever offered that would
 * then be refused. The server re-checks all of it on submit regardless.
 *
 * The form never scrolls the page on load. The plugin this replaces called its
 * step handler during setup, which scrolled visitors to the form and past the
 * whole home page above it.
 *
 * Requires the "workshops data" and "workshops bookings" snippets.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

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

		ob_start();
		?>
		<section class="iwf" data-iwf
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_book' ) ); ?>">

			<div class="iwf__inner">
				<header class="iwf__head">
					<p class="iwf__eyebrow">κρατήσεις</p>
					<h2 class="iwf__title">κλείσε τη θέση σου</h2>
					<p class="iwf__intro">Διάλεξε πρόγραμμα, ημέρα και ώρα. Θα λάβεις email μόλις ολοκληρωθεί η κράτησή σου.</p>
				</header>

				<?php if ( empty( $availability ) ) : ?>

					<p class="iwf__empty">Αυτή τη στιγμή δεν υπάρχουν διαθέσιμες ημερομηνίες. Γράψε μας και θα βρούμε μαζί μια μέρα.</p>

				<?php else : ?>

					<ol class="iwf__steps" aria-hidden="true">
						<li class="iwf__step is-current" data-iwf-marker="1"><span>01</span> πρόγραμμα</li>
						<li class="iwf__step" data-iwf-marker="2"><span>02</span> ημέρα</li>
						<li class="iwf__step" data-iwf-marker="3"><span>03</span> ώρα</li>
						<li class="iwf__step" data-iwf-marker="4"><span>04</span> στοιχεία</li>
					</ol>

					<p class="iwf__chosen" data-iwf-chosen hidden></p>
					<p class="iwf__error" data-iwf-error hidden role="alert"></p>

					<div class="iwf__panel" data-iwf-panel="1">
						<div class="iwf__programmes" data-iwf-programmes></div>
					</div>

					<div class="iwf__panel" data-iwf-panel="2" hidden>
						<p class="iwf__hint">Οι κρατήσεις γίνονται τουλάχιστον <?php echo (int) $settings['lead_days']; ?> ημέρες πριν τη συνάντηση.</p>
						<div class="iwf__dates" data-iwf-dates></div>
					</div>

					<div class="iwf__panel" data-iwf-panel="3" hidden>
						<div class="iwf__times" data-iwf-times></div>
					</div>

					<div class="iwf__panel" data-iwf-panel="4" hidden>
						<form class="iwf__form" data-iwf-form novalidate>
							<div class="iwf__people">
								<span id="iwf-people-label">Άτομα</span>
								<div class="iwf__stepper" role="group" aria-labelledby="iwf-people-label">
									<button type="button" data-iwf-people="-1" aria-label="Λιγότερα άτομα">−</button>
									<output data-iwf-people-value>1</output>
									<button type="button" data-iwf-people="1" aria-label="Περισσότερα άτομα">+</button>
								</div>
							</div>

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

							<label class="iwf__field">
								<span>Κάτι που πρέπει να ξέρουμε; <em>προαιρετικό</em></span>
								<textarea name="note" rows="2"></textarea>
							</label>

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

					<div class="iwf__panel iwf__done" data-iwf-panel="done" hidden>
						<h3>Η θέση σου κρατήθηκε.</h3>
						<p data-iwf-summary></p>
						<p>Σου στείλαμε email με τα στοιχεία. Θα σε περιμένουμε στο εργαστήριο.</p>
					</div>

					<button type="button" class="iwf__back" data-iwf-back hidden>Πίσω</button>

				<?php endif; ?>
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

		box-sizing: border-box;
		width: 100%;
		max-width: var(--ioulia-shell, 100%);
		margin-inline: auto;
		padding: clamp(3.5rem, 9vw, 7rem) var(--ioulia-page-x, 1.25rem);
		background: var(--iwf-paper);
		color: var(--iwf-ink);
		font-family: var(--ioulia-font, sans-serif);
	}
	.iwf *, .iwf *::before, .iwf *::after { box-sizing: inherit; }
	.iwf__inner { max-width: 620px; margin-inline: auto; }

	.iwf__eyebrow { margin: 0; color: var(--iwf-muted); font-size: .74rem; letter-spacing: .16em; text-transform: uppercase; }
	.iwf__title { margin: .4em 0 0; font-size: clamp(2.2rem, 7vw, 3.4rem); font-weight: 300; letter-spacing: -.035em; line-height: 1.05; }
	.iwf__intro { margin: 1em 0 0; max-width: 42ch; color: var(--iwf-muted); font-size: .92rem; line-height: 1.6; }
	.iwf__empty { margin: 2.5rem 0 0; color: var(--iwf-muted); }

	.iwf__steps { display: flex; flex-wrap: wrap; gap: .4rem 1.2rem; margin: 2.5rem 0 0; padding: 0; list-style: none; }
	.iwf__step { color: var(--iwf-muted); font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; opacity: .45; }
	.iwf__step span { font-variant-numeric: tabular-nums; }
	.iwf__step.is-current { color: var(--iwf-ink); opacity: 1; }
	.iwf__step.is-done { opacity: .8; }

	.iwf__chosen { margin: 1.4rem 0 0; padding: .8rem 1rem; border-left: 2px solid var(--iwf-accent); color: var(--iwf-muted); font-size: .84rem; }
	.iwf__error { margin: 1.4rem 0 0; padding: .8rem 1rem; background: rgba(124, 55, 55, .08); color: var(--iwf-accent); font-size: .86rem; }
	.iwf__hint { margin: 1.6rem 0 0; color: var(--iwf-muted); font-size: .78rem; }

	.iwf__panel { margin-top: 1.8rem; }
	.iwf__panel[hidden] { display: none; }

	/* Choices are one column on a phone: a row of small targets is a row of
	   mistakes when it is the only thing between a visitor and a booking. */
	.iwf__programmes { display: grid; gap: .6rem; }
	.iwf__choice {
		display: grid;
		grid-template-columns: auto 1fr auto;
		align-items: baseline;
		gap: .5rem .9rem;
		width: 100%;
		min-height: 56px;
		padding: 1rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: inherit;
		font: inherit;
		text-align: left;
		cursor: pointer;
		transition: border-color .2s ease, background-color .2s ease;
	}
	.iwf__choice:hover, .iwf__choice:focus-visible { border-color: var(--iwf-ink); outline: none; }
	.iwf__choice-num { color: var(--iwf-muted); font-size: .74rem; font-variant-numeric: tabular-nums; }
	.iwf__choice-title { font-size: 1rem; }
	.iwf__choice-price { color: var(--iwf-muted); font-size: .84rem; white-space: nowrap; }
	.iwf__choice-summary { grid-column: 2 / -1; margin: .25rem 0 0; color: var(--iwf-muted); font-size: .8rem; line-height: 1.5; }

	/* Dates scroll sideways so a long list does not push the form off screen. */
	.iwf__dates {
		display: flex;
		gap: .5rem;
		margin-top: 1rem;
		padding-bottom: .5rem;
		overflow-x: auto;
		scrollbar-width: thin;
		-webkit-overflow-scrolling: touch;
	}
	.iwf__date {
		flex: 0 0 auto;
		width: 76px;
		padding: .8rem .4rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: inherit;
		font: inherit;
		text-align: center;
		cursor: pointer;
	}
	.iwf__date:hover, .iwf__date:focus-visible { border-color: var(--iwf-ink); outline: none; }
	.iwf__date span { display: block; }
	.iwf__date-day { color: var(--iwf-muted); font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; }
	.iwf__date-num { margin: .15rem 0; font-size: 1.35rem; font-variant-numeric: tabular-nums; }
	.iwf__date-month { color: var(--iwf-muted); font-size: .68rem; }

	.iwf__times { display: grid; gap: .6rem; }
	.iwf__time {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		min-height: 56px;
		padding: 1rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: inherit;
		font: inherit;
		cursor: pointer;
	}
	.iwf__time:hover, .iwf__time:focus-visible { border-color: var(--iwf-ink); outline: none; }
	.iwf__time-left { color: var(--iwf-muted); font-size: .78rem; }

	.iwf__people { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--iwf-line); }
	.iwf__stepper { display: flex; align-items: center; gap: .4rem; }
	.iwf__stepper button {
		width: 44px;
		height: 44px;
		border: 1px solid var(--iwf-line);
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: inherit;
		font: inherit;
		font-size: 1.1rem;
		cursor: pointer;
	}
	.iwf__stepper output { min-width: 2ch; text-align: center; font-variant-numeric: tabular-nums; }

	.iwf__field { display: block; margin-top: 1.2rem; }
	.iwf__field > span { display: block; margin-bottom: .35rem; color: var(--iwf-muted); font-size: .78rem; }
	.iwf__field em { font-style: normal; opacity: .7; }
	.iwf__field input, .iwf__field textarea {
		display: block;
		width: 100%;
		padding: .8rem .9rem;
		border: 1px solid var(--iwf-line);
		border-radius: var(--ioulia-radius, 5px);
		background: #fff;
		color: inherit;
		font: inherit;
		/* 16px keeps iOS from zooming the page when a field takes focus. */
		font-size: 16px;
		resize: vertical;
	}
	.iwf__field input:focus, .iwf__field textarea:focus { border-color: var(--iwf-ink); outline: none; }

	.iwf__consent { display: flex; gap: .7rem; margin-top: 1.4rem; color: var(--iwf-muted); font-size: .82rem; line-height: 1.5; }
	.iwf__consent input { width: 22px; height: 22px; margin: 0; flex: 0 0 auto; accent-color: var(--iwf-ink); }
	.iwf__consent a { color: inherit; }

	.iwf__trap { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }

	.iwf__submit { width: 100%; margin-top: 1.8rem; min-height: 52px; }

	.iwf__back {
		margin-top: 1.4rem;
		padding: .6rem 0;
		border: 0;
		background: none;
		color: var(--iwf-muted);
		font: inherit;
		font-size: .82rem;
		cursor: pointer;
	}
	.iwf__back[hidden] { display: none; }

	.iwf__done h3 { margin: 0; font-size: 1.5rem; font-weight: 400; }
	.iwf__done p { margin: .8rem 0 0; color: var(--iwf-muted); font-size: .9rem; }

	@media (min-width: 700px) {
		.iwf__programmes { gap: .7rem; }
		.iwf__times { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	}
</style>

<script id="ioulia-workshops-form-js">
(function () {
	var root = document.querySelector('[data-iwf]');
	if (!root || root.dataset.iwfReady) { return; }
	root.dataset.iwfReady = '1';

	var payload = root.querySelector('[data-iwf-availability]');
	if (!payload) { return; }

	var programmes = JSON.parse(payload.textContent || '[]');
	if (!programmes.length) { return; }

	var picked = { programme: null, date: null, time: null, people: 1 };
	var interacted = false;

	var el = {
		programmes: root.querySelector('[data-iwf-programmes]'),
		dates: root.querySelector('[data-iwf-dates]'),
		times: root.querySelector('[data-iwf-times]'),
		chosen: root.querySelector('[data-iwf-chosen]'),
		error: root.querySelector('[data-iwf-error]'),
		back: root.querySelector('[data-iwf-back]'),
		form: root.querySelector('[data-iwf-form]'),
		people: root.querySelector('[data-iwf-people-value]'),
		summary: root.querySelector('[data-iwf-summary]')
	};

	function button(className, html) {
		var node = document.createElement('button');
		node.type = 'button';
		node.className = className;
		node.innerHTML = html;
		return node;
	}

	function showError(message) {
		el.error.textContent = message || '';
		el.error.hidden = !message;
	}

	function show(step) {
		root.querySelectorAll('[data-iwf-panel]').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-iwf-panel') !== String(step);
		});

		root.querySelectorAll('[data-iwf-marker]').forEach(function (marker) {
			var index = Number(marker.getAttribute('data-iwf-marker'));
			marker.classList.toggle('is-current', index === Number(step));
			marker.classList.toggle('is-done', index < Number(step));
		});

		el.back.hidden = step === 1 || step === 'done';
		root.dataset.step = step;
		showError('');

		/* Only bring the form into view once the visitor is actually using it, and
		   only when it has scrolled out of reach. Doing this unconditionally is
		   what made the old form yank the home page down to itself on load. */
		if (interacted && root.getBoundingClientRect().top < 0) {
			root.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}

	function describe() {
		if (!picked.programme) { el.chosen.hidden = true; return; }

		var parts = [picked.programme.title];
		if (picked.date) { parts.push(picked.date.full); }
		if (picked.time) { parts.push(picked.time.label); }

		el.chosen.textContent = parts.join('  ·  ');
		el.chosen.hidden = false;
	}

	/* Step 1 */
	programmes.forEach(function (programme) {
		var node = button('iwf__choice',
			'<span class="iwf__choice-num">' + programme.number + '</span>' +
			'<span class="iwf__choice-title"></span>' +
			'<span class="iwf__choice-price"></span>' +
			'<span class="iwf__choice-summary"></span>');

		node.querySelector('.iwf__choice-title').textContent = programme.title;
		node.querySelector('.iwf__choice-price').textContent = programme.price + ' €';
		node.querySelector('.iwf__choice-summary').textContent = programme.summary;

		node.addEventListener('click', function () {
			interacted = true;
			picked.programme = programme;
			picked.date = null;
			picked.time = null;
			renderDates();
			describe();
			show(2);
		});

		el.programmes.appendChild(node);
	});

	function renderDates() {
		el.dates.textContent = '';

		picked.programme.dates.forEach(function (date) {
			var node = button('iwf__date',
				'<span class="iwf__date-day"></span>' +
				'<span class="iwf__date-num"></span>' +
				'<span class="iwf__date-month"></span>');

			node.querySelector('.iwf__date-day').textContent = date.day;
			node.querySelector('.iwf__date-num').textContent = date.num;
			node.querySelector('.iwf__date-month').textContent = date.month;

			node.addEventListener('click', function () {
				interacted = true;
				picked.date = date;
				picked.time = null;
				renderTimes();
				describe();
				show(3);
			});

			el.dates.appendChild(node);
		});
	}

	function renderTimes() {
		el.times.textContent = '';

		picked.date.times.forEach(function (time) {
			var node = button('iwf__time',
				'<span></span><span class="iwf__time-left"></span>');

			node.querySelector('span').textContent = time.label;
			node.querySelector('.iwf__time-left').textContent =
				time.left === 1 ? '1 θέση' : time.left + ' θέσεις';

			node.addEventListener('click', function () {
				interacted = true;
				picked.time = time;
				picked.people = 1;
				el.people.textContent = '1';
				describe();
				show(4);
			});

			el.times.appendChild(node);
		});
	}

	/* Step 4 */
	root.querySelectorAll('[data-iwf-people]').forEach(function (control) {
		control.addEventListener('click', function () {
			var step = Number(control.getAttribute('data-iwf-people'));
			var most = picked.time ? picked.time.left : 1;

			picked.people = Math.min(most, Math.max(1, picked.people + step));
			el.people.textContent = String(picked.people);

			showError(picked.people === most && step > 0 ? 'Αυτές είναι οι διαθέσιμες θέσεις.' : '');
		});
	});

	el.back.addEventListener('click', function () {
		interacted = true;
		var step = Number(root.dataset.step || 1);
		show(Math.max(1, step - 1));
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
				el.chosen.hidden = true;
				show('done');
			})
			.catch(function () {
				submit.disabled = false;
				submit.textContent = 'Ολοκλήρωση κράτησης';
				showError('Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.');
			});
	});

	show(1);
}());
</script>
		<?php
	}
}
