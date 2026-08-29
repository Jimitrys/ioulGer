<?php
/**
 * Bookings dashboard — Ioulia's own page, built for a phone.
 *
 * Lives at /kratiseis/ on the front end. She never opens wp-admin: she signs in
 * on this page and sees her bookings as a list of days, taps a phone number to
 * call, and cancels with a message that reaches the visitor by email.
 *
 * Greek only, on purpose. This is an internal tool, so it is left out of the
 * English site and out of search engines entirely.
 *
 * The page is created on first run if it is missing, so nothing has to be set up
 * by hand in WordPress. All the reading and writing goes through the workshops
 * bookings snippet.
 *
 * Actions go over AJAX rather than a form post. Site Studio's snippet validator
 * blocks exit, so a post-redirect-get round trip is not available to us, and a
 * phone wants an app-like response anyway.
 *
 * Requires the "workshops data" and "workshops bookings" snippets.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! defined( 'IOULIA_DASHBOARD_SLUG' ) ) {
	define( 'IOULIA_DASHBOARD_SLUG', 'kratiseis' );
}

if ( ! function_exists( 'ioulia_dashboard_capability' ) ) {
	/**
	 * Who may see bookings. Editors and administrators by default, so Ioulia does
	 * not need a full administrator account to run the studio.
	 */
	function ioulia_dashboard_capability() {
		return apply_filters( 'ioulia_dashboard_capability', 'edit_others_posts' );
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
	 * An internal tool has no business in search results, in the English site, or
	 * behind the site's own chrome.
	 */
	function ioulia_dashboard_keep_private() {
		if ( ! ioulia_is_dashboard() ) {
			return;
		}

		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter( 'show_admin_bar', '__return_false' );
	}
	add_action( 'template_redirect', 'ioulia_dashboard_keep_private' );
}

if ( ! function_exists( 'ioulia_dashboard_not_translatable' ) ) {
	/**
	 * Keep /en/kratiseis/ from existing at all.
	 */
	function ioulia_dashboard_not_translatable( $translatable, $path ) {
		return 0 === strpos( ltrim( (string) $path, '/' ), IOULIA_DASHBOARD_SLUG ) ? false : $translatable;
	}
	add_filter( 'ioulia_path_is_translatable', 'ioulia_dashboard_not_translatable', 10, 2 );
}

/* -------------------------------------------------------------------------
 * Actions
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_dashboard_cancel_ajax' ) ) {
	function ioulia_dashboard_cancel_ajax() {
		check_ajax_referer( 'ioulia_dashboard', 'nonce' );

		if ( ! current_user_can( ioulia_dashboard_capability() ) ) {
			wp_send_json_error( array( 'message' => 'Δεν έχεις δικαίωμα.' ), 403 );
		}

		$booking_id = isset( $_POST['booking'] ) ? absint( $_POST['booking'] ) : 0;
		$reason     = isset( $_POST['reason'] ) ? (string) wp_unslash( $_POST['reason'] ) : '';
		$cancelled  = ioulia_cancel_booking( $booking_id, $reason );

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
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

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
		$phone     = preg_replace( '/[^0-9+]/', '', $booking['phone'] );
		?>
		<article class="iwd-card<?php echo $cancelled ? ' is-cancelled' : ''; ?>" data-booking="<?php echo esc_attr( $booking['id'] ); ?>">
			<header class="iwd-card__top">
				<span class="iwd-card__time"><?php echo esc_html( substr( $booking['starts'], 11, 5 ) ); ?></span>
				<span class="iwd-card__programme"><?php echo esc_html( $booking['programme_title'] ); ?></span>
				<?php if ( $capacity ) : ?>
					<span class="iwd-card__seats"><?php echo esc_html( $taken . '/' . $capacity ); ?></span>
				<?php endif; ?>
			</header>

			<p class="iwd-card__who">
				<strong><?php echo esc_html( $booking['name'] ); ?></strong>
				<span><?php echo esc_html( 1 === $booking['participants'] ? '1 άτομο' : $booking['participants'] . ' άτομα' ); ?></span>
			</p>

			<?php if ( '' !== $booking['note'] ) : ?>
				<p class="iwd-card__note"><?php echo esc_html( $booking['note'] ); ?></p>
			<?php endif; ?>

			<div class="iwd-card__actions">
				<?php if ( '' !== $phone ) : ?>
					<a class="iwd-action" href="tel:<?php echo esc_attr( $phone ); ?>">Κλήση</a>
				<?php endif; ?>
				<a class="iwd-action" href="mailto:<?php echo esc_attr( $booking['email'] ); ?>">Email</a>
				<?php if ( ! $cancelled ) : ?>
					<button type="button" class="iwd-action iwd-action--danger" data-iwd-cancel>Ακύρωση</button>
				<?php else : ?>
					<span class="iwd-action iwd-action--muted" data-iwd-status>Ακυρώθηκε</span>
				<?php endif; ?>
			</div>

			<div class="iwd-cancel" hidden>
				<label for="iwd-reason-<?php echo esc_attr( $booking['id'] ); ?>">Μήνυμα στον πελάτη (προαιρετικό)</label>
				<textarea id="iwd-reason-<?php echo esc_attr( $booking['id'] ); ?>" rows="2" data-iwd-reason placeholder="π.χ. το εργαστήριο είναι κλειστό εκείνη τη μέρα"></textarea>
				<div class="iwd-cancel__buttons">
					<button type="button" class="iwd-action" data-iwd-abort>Πίσω</button>
					<button type="button" class="iwd-action iwd-action--danger" data-iwd-confirm>Ακύρωση και email</button>
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
			<section class="iwd-day">
				<h2 class="iwd-day__label"><?php echo esc_html( ioulia_dashboard_day_label( $date ) ); ?></h2>
				<?php foreach ( $day as $booking ) : ?>
					<?php ioulia_dashboard_card( $booking ); ?>
				<?php endforeach; ?>
			</section>
			<?php
		}
	}
}

if ( ! function_exists( 'ioulia_dashboard_shortcode' ) ) {
	function ioulia_dashboard_shortcode() {
		ob_start();

		if ( ! is_user_logged_in() || ! current_user_can( ioulia_dashboard_capability() ) ) {
			ioulia_dashboard_gate();

			return ob_get_clean();
		}

		$now      = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		$upcoming = ioulia_get_bookings( array( 'from' => $now, 'limit' => 300 ) );
		$past     = array_reverse( ioulia_get_bookings( array( 'until' => $now, 'limit' => 100 ) ) );
		$off      = ioulia_get_bookings( array( 'status' => 'cancelled', 'limit' => 100 ) );
		$people   = 0;

		foreach ( $upcoming as $booking ) {
			$people += $booking['participants'];
		}
		?>
		<div class="iwd" data-iwd
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'ioulia_dashboard' ) ); ?>">

			<header class="iwd-head">
				<div>
					<h1>Κρατήσεις</h1>
					<p><?php echo esc_html( sprintf( '%d κρατήσεις, %d άτομα', count( $upcoming ), $people ) ); ?></p>
				</div>
				<a class="iwd-signout" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Έξοδος</a>
			</header>

			<nav class="iwd-tabs" role="tablist">
				<button type="button" class="iwd-tab is-current" data-iwd-tab="upcoming" role="tab" aria-selected="true">Επόμενες</button>
				<button type="button" class="iwd-tab" data-iwd-tab="past" role="tab" aria-selected="false">Περασμένες</button>
				<button type="button" class="iwd-tab" data-iwd-tab="cancelled" role="tab" aria-selected="false">Ακυρωμένες</button>
			</nav>

			<p class="iwd-flash" data-iwd-flash hidden></p>

			<div class="iwd-panel" data-iwd-panel="upcoming">
				<?php ioulia_dashboard_list( $upcoming, 'Καμία επόμενη κράτηση.' ); ?>
			</div>
			<div class="iwd-panel" data-iwd-panel="past" hidden>
				<?php ioulia_dashboard_list( $past, 'Καμία περασμένη κράτηση.' ); ?>
			</div>
			<div class="iwd-panel" data-iwd-panel="cancelled" hidden>
				<?php ioulia_dashboard_list( $off, 'Καμία ακυρωμένη κράτηση.' ); ?>
			</div>
		</div>
		<?php
		ioulia_dashboard_assets();

		return ob_get_clean();
	}
	add_shortcode( 'ioulia_bookings_dashboard', 'ioulia_dashboard_shortcode' );
}

if ( ! function_exists( 'ioulia_dashboard_gate' ) ) {
	/**
	 * Signing in happens here rather than on wp-login.php, but the form still
	 * posts to WordPress' own login, so none of its hardening is bypassed.
	 */
	function ioulia_dashboard_gate() {
		$logged_in = is_user_logged_in();
		?>
		<div class="iwd iwd--gate">
			<header class="iwd-head">
				<div>
					<h1>Κρατήσεις</h1>
					<p><?php echo $logged_in ? 'Ο λογαριασμός σου δεν έχει πρόσβαση εδώ.' : 'Σύνδεση για να δεις τις κρατήσεις.'; ?></p>
				</div>
			</header>

			<?php
			if ( ! $logged_in ) {
				wp_login_form(
					array(
						'redirect'       => get_permalink(),
						'label_username' => 'Όνομα χρήστη ή email',
						'label_password' => 'Κωδικός',
						'label_log_in'   => 'Σύνδεση',
						'label_remember' => 'Να με θυμάσαι',
						'remember'       => true,
					)
				);
			}
			?>
		</div>
		<?php
		ioulia_dashboard_assets();
	}
}

/* -------------------------------------------------------------------------
 * Styles and behaviour
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
	/* The theme's own header and footer are noise around a tool. */
	body.page-kratiseis header.wp-block-template-part,
	body.page-kratiseis footer.wp-block-template-part,
	body.page-kratiseis .wp-block-post-title,
	body.page-kratiseis #ioulia-header,
	body.page-kratiseis .ioulia-footer { display: none !important; }

	body.page-kratiseis { background: var(--ioulia-paper, #FFFEF7); }

	body.page-kratiseis main,
	body.page-kratiseis .wp-block-post-content,
	body.page-kratiseis .entry-content { max-width: none; margin: 0; padding: 0; }

	.iwd {
		--iwd-ink: var(--ioulia-ink, #2B2B2B);
		--iwd-paper: var(--ioulia-paper, #FFFEF7);
		--iwd-line: var(--ioulia-ink-12, rgba(43, 43, 43, .12));
		--iwd-muted: var(--ioulia-ink-80, rgba(43, 43, 43, .8));
		--iwd-danger: var(--ioulia-accent, #7C3737);

		box-sizing: border-box;
		width: 100%;
		min-height: 100svh;
		margin: 0 auto;
		padding: 0 16px calc(32px + env(safe-area-inset-bottom));
		background: var(--iwd-paper);
		color: var(--iwd-ink);
		font-family: var(--ioulia-font, system-ui, sans-serif);
		font-size: 16px;
		line-height: 1.45;
		-webkit-text-size-adjust: 100%;
	}

	.iwd *, .iwd *::before, .iwd *::after { box-sizing: inherit; }

	.iwd-head {
		position: sticky;
		top: 0;
		z-index: 5;
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		gap: 12px;
		margin: 0 -16px;
		padding: calc(20px + env(safe-area-inset-top)) 16px 12px;
		background: var(--iwd-paper);
		border-bottom: 1px solid var(--iwd-line);
	}
	.iwd-head h1 { margin: 0; font-size: 1.5rem; font-weight: 500; letter-spacing: -.02em; }
	.iwd-head p { margin: 2px 0 0; color: var(--iwd-muted); font-size: var(--ioulia-small); }
	.iwd-signout { color: var(--iwd-muted); font-size: var(--ioulia-small); text-decoration: none; padding: 8px 0; }

	.iwd-tabs { display: flex; gap: 6px; margin: 14px 0 4px; }
	.iwd-tab {
		flex: 1 1 0;
		min-height: 44px;
		padding: 10px 8px;
		border: 1px solid var(--iwd-line);
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: var(--iwd-muted);
		font: inherit;
		font-size: var(--ioulia-small);
		cursor: pointer;
	}
	.iwd-tab.is-current { border-color: var(--iwd-ink); background: var(--iwd-ink); color: var(--iwd-paper); }

	.iwd-flash {
		margin: 12px 0 0;
		padding: 12px 14px;
		border-radius: var(--ioulia-radius, 5px);
		background: rgba(124, 55, 55, .08);
		color: var(--iwd-danger);
		font-size: var(--ioulia-small);
	}

	.iwd-day { margin-top: 22px; }
	.iwd-day__label {
		position: sticky;
		top: 76px;
		z-index: 4;
		margin: 0 -16px 10px;
		padding: 6px 16px;
		background: var(--iwd-paper);
		color: var(--iwd-muted);
		font-size: var(--ioulia-micro);
		font-weight: 500;
		letter-spacing: .1em;
		text-transform: uppercase;
	}

	.iwd-card {
		margin-bottom: 10px;
		padding: 14px;
		border: 1px solid var(--iwd-line);
		border-radius: var(--ioulia-radius, 5px);
		background: #fff;
	}
	.iwd-card.is-cancelled { opacity: .55; }

	.iwd-card__top { display: flex; align-items: baseline; gap: 10px; }
	.iwd-card__time { font-size: var(--ioulia-body); font-weight: 500; font-variant-numeric: tabular-nums; }
	.iwd-card__programme { flex: 1 1 auto; min-width: 0; color: var(--iwd-muted); font-size: var(--ioulia-small); }
	.iwd-card__seats {
		flex: 0 0 auto;
		padding: 2px 8px;
		border: 1px solid var(--iwd-line);
		border-radius: 999px;
		font-size: var(--ioulia-micro);
		font-variant-numeric: tabular-nums;
	}

	.iwd-card__who { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin: 10px 0 0; }
	.iwd-card__who strong { font-weight: 500; }
	.iwd-card__who span { color: var(--iwd-muted); font-size: var(--ioulia-small); white-space: nowrap; }
	.iwd-card__note { margin: 8px 0 0; color: var(--iwd-muted); font-size: var(--ioulia-small); }

	.iwd-card__actions { display: flex; gap: 8px; margin-top: 12px; }
	.iwd-action {
		display: inline-flex;
		flex: 1 1 0;
		align-items: center;
		justify-content: center;
		min-height: 44px;
		padding: 8px 12px;
		border: 1px solid var(--iwd-line);
		border-radius: var(--ioulia-radius, 5px);
		background: transparent;
		color: var(--iwd-ink);
		font: inherit;
		font-size: var(--ioulia-small);
        text-decoration: none;
		cursor: pointer;
	}
	.iwd-action--danger { border-color: var(--iwd-danger); color: var(--iwd-danger); }
	.iwd-action--muted { color: var(--iwd-muted); cursor: default; }

	.iwd-cancel { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--iwd-line); }
	.iwd-cancel[hidden] { display: none; }
	.iwd-cancel label { display: block; margin-bottom: 6px; color: var(--iwd-muted); font-size: var(--ioulia-micro); }
	.iwd-cancel textarea {
		width: 100%;
		padding: 10px;
		border: 1px solid var(--iwd-line);
		border-radius: var(--ioulia-radius, 5px);
		font: inherit;
		font-size: 16px;
		resize: vertical;
	}
	.iwd-cancel__buttons { display: flex; gap: 8px; margin-top: 10px; }

	.iwd-empty { margin: 28px 0; color: var(--iwd-muted); font-size: var(--ioulia-small); text-align: center; }

	.iwd--gate form { margin-top: 24px; }
	.iwd--gate label { display: block; margin-bottom: 14px; color: var(--iwd-muted); font-size: var(--ioulia-small); }
	.iwd--gate input[type="text"],
	.iwd--gate input[type="password"] {
		display: block;
		width: 100%;
		margin-top: 6px;
		padding: 12px;
		border: 1px solid var(--iwd-line);
		border-radius: var(--ioulia-radius, 5px);
		font: inherit;
		font-size: 16px;
		color: var(--iwd-ink);
	}
	.iwd--gate .login-remember label { display: flex; align-items: center; gap: 8px; }
	.iwd--gate input[type="submit"] {
		width: 100%;
		min-height: 48px;
		border: 1px solid var(--iwd-ink);
		border-radius: var(--ioulia-radius, 5px);
		background: var(--iwd-ink);
		color: var(--iwd-paper);
		font: inherit;
		font-size: var(--ioulia-small);
		cursor: pointer;
	}

	/* On a wide screen it stays a single readable column rather than stretching. */
	@media (min-width: 720px) {
		.iwd { max-width: 640px; padding-inline: 24px; }
		.iwd-head { margin-inline: -24px; padding-inline: 24px; }
		.iwd-day__label { margin-inline: -24px; padding-inline: 24px; }
	}

	/* Muted tone always comes with more weight. Lower contrast must not also
	   mean lighter strokes, or the text pays for the quietness twice. */
	.iwd-head p, .iwd-signout, .iwd-tab, .iwd-card__programme,
	.iwd-card__who span, .iwd-card__note, .iwd-empty,
	.iwd-cancel label, .iwd-card__seats, .iwd-action { font-weight: 500; }
</style>

<script id="ioulia-dashboard-js">
(function () {
	var root = document.querySelector('[data-iwd]');
	if (!root || root.dataset.iwdReady) { return; }
	root.dataset.iwdReady = '1';

	var flash = root.querySelector('[data-iwd-flash]');

	function say(message) {
		if (!flash) { return; }
		flash.textContent = message;
		flash.hidden = false;
	}

	/* Tabs */
	root.querySelectorAll('[data-iwd-tab]').forEach(function (tab) {
		tab.addEventListener('click', function () {
			var name = tab.getAttribute('data-iwd-tab');

			root.querySelectorAll('[data-iwd-tab]').forEach(function (other) {
				var current = other === tab;
				other.classList.toggle('is-current', current);
				other.setAttribute('aria-selected', current ? 'true' : 'false');
			});

			root.querySelectorAll('[data-iwd-panel]').forEach(function (panel) {
				panel.hidden = panel.getAttribute('data-iwd-panel') !== name;
			});

			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	});

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
					say((result && result.data && result.data.message) || 'Κάτι πήγε στραβά.');
					return;
				}

				panel.hidden = true;
				card.classList.add('is-cancelled');
				card.querySelector('.iwd-card__actions').innerHTML =
					'<span class="iwd-action iwd-action--muted">Ακυρώθηκε</span>';
				say(result.data.message);
			})
			.catch(function () {
				button.disabled = false;
				button.textContent = 'Ακύρωση και email';
				say('Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.');
			});
	});
}());
</script>
		<?php
	}
}
