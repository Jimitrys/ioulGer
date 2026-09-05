<?php
/**
 * Editing the programmes, from /kratiseis/.
 *
 * Until now a programme lived in two places: a PHP array the booking picker
 * read, and a block of hand-written HTML on the workshops page repeating its
 * days, times and price. Changing a Wednesday meant changing both, and they had
 * already drifted apart on one programme.
 *
 * There is one record now, and three things read it: this editor, the picker in
 * the popup, and the accordion on /workshops/. Saving here changes all three,
 * because there is nothing left to keep in step.
 *
 * The whole set is submitted at once rather than one programme at a time. That
 * keeps their order - which is the order they appear in on the page and in the
 * picker - and means a half-finished edit can never leave a gap in the middle.
 *
 * Requires the "workshops data" snippet.
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_editor_weekday_options' ) ) {
	function ioulia_editor_weekday_options( $selected ) {
		$weekdays = ioulia_greek_weekdays();
		$out      = '';

		foreach ( $weekdays as $number => $name ) {
			$out .= sprintf(
				'<option value="%1$d"%2$s>%3$s</option>',
				$number,
				(int) $selected === (int) $number ? ' selected' : '',
				esc_html( $name )
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'ioulia_editor_session_row' ) ) {
	function ioulia_editor_session_row( $index, $row, $session ) {
		$day   = isset( $session['day'] ) ? (int) $session['day'] : 3;
		$start = isset( $session['start'] ) ? $session['start'] : '11:00';
		$end   = isset( $session['end'] ) ? $session['end'] : '13:30';

		$name = 'prog[' . $index . '][sessions][' . $row . ']';

		return '<div class="iwe-session" data-iwe-session>'
			. '<select name="' . esc_attr( $name ) . '[day]" aria-label="Ημέρα">' . ioulia_editor_weekday_options( $day ) . '</select>'
			. '<input type="time" name="' . esc_attr( $name ) . '[start]" value="' . esc_attr( $start ) . '" aria-label="Από">'
			. '<input type="time" name="' . esc_attr( $name ) . '[end]" value="' . esc_attr( $end ) . '" aria-label="Έως">'
			. '<button type="button" class="iwe-remove" data-iwe-remove aria-label="Αφαίρεση ώρας">×</button>'
			. '</div>';
	}
}

if ( ! function_exists( 'ioulia_editor_programme' ) ) {
	function ioulia_editor_programme( $index, $slug, $programme, $open = false ) {
		$field = 'prog[' . $index . ']';
		$id    = 'iwe-' . $index;

		$title = '' !== $programme['title'] ? $programme['title'] : 'Νέο πρόγραμμα';

		$sessions = '';
		$row      = 0;

		foreach ( $programme['sessions'] as $session ) {
			$sessions .= ioulia_editor_session_row( $index, $row, $session );
			$row++;
		}

		if ( '' === $sessions ) {
			$sessions = ioulia_editor_session_row( $index, 0, array() );
			$row      = 1;
		}

		ob_start();
		?>
		<details class="iwe-item" data-iwe-item data-index="<?php echo esc_attr( $index ); ?>"<?php echo $open ? ' open' : ''; ?>>
			<summary class="iwe-item__summary">
				<span class="iwe-item__num"><?php echo esc_html( $programme['number'] ); ?></span>
				<span class="iwe-item__title" data-iwe-label><?php echo esc_html( $title ); ?></span>
				<?php if ( empty( $programme['active'] ) ) : ?>
					<span class="iwd-card__flag">Ανενεργό</span>
				<?php endif; ?>
			</summary>

			<div class="iwe-item__body">
				<input type="hidden" name="<?php echo esc_attr( $field ); ?>[slug]" value="<?php echo esc_attr( $slug ); ?>">

				<label for="<?php echo esc_attr( $id ); ?>-title">Τίτλος</label>
				<input id="<?php echo esc_attr( $id ); ?>-title" type="text" name="<?php echo esc_attr( $field ); ?>[title]" value="<?php echo esc_attr( $programme['title'] ); ?>" data-iwe-title required>

				<label for="<?php echo esc_attr( $id ); ?>-summary">Μία γραμμή για το popup κρατήσεων</label>
				<textarea id="<?php echo esc_attr( $id ); ?>-summary" name="<?php echo esc_attr( $field ); ?>[summary]" rows="2"><?php echo esc_textarea( $programme['summary'] ); ?></textarea>

				<label for="<?php echo esc_attr( $id ); ?>-description">Περιγραφή για τη σελίδα workshops</label>
				<textarea id="<?php echo esc_attr( $id ); ?>-description" name="<?php echo esc_attr( $field ); ?>[description]" rows="5"><?php echo esc_textarea( $programme['description'] ); ?></textarea>

				<div class="iwe-pair">
					<div>
						<label for="<?php echo esc_attr( $id ); ?>-price">Τιμή ανά συνάντηση (€)</label>
						<input id="<?php echo esc_attr( $id ); ?>-price" type="number" min="0" step="0.5" inputmode="decimal" name="<?php echo esc_attr( $field ); ?>[price]" value="<?php echo esc_attr( $programme['price'] ); ?>">
					</div>
					<div>
						<label for="<?php echo esc_attr( $id ); ?>-capacity">Θέσεις ανά συνάντηση</label>
						<input id="<?php echo esc_attr( $id ); ?>-capacity" type="number" min="1" step="1" inputmode="numeric" name="<?php echo esc_attr( $field ); ?>[capacity]" value="<?php echo esc_attr( $programme['capacity'] ); ?>">
					</div>
				</div>

				<label for="<?php echo esc_attr( $id ); ?>-note">Σημείωση τιμής (προαιρετικό)</label>
				<input id="<?php echo esc_attr( $id ); ?>-note" type="text" name="<?php echo esc_attr( $field ); ?>[note]" value="<?php echo esc_attr( $programme['note'] ); ?>" placeholder="π.χ. περιλαμβάνονται υλικά και εργαλεία">

				<fieldset class="iwe-sessions">
					<legend>Ημέρες και ώρες</legend>
					<div data-iwe-sessions data-next="<?php echo esc_attr( $row ); ?>">
						<?php echo $sessions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above. ?>
					</div>
					<button type="button" class="ioulia-btn ioulia-btn--outline ioulia-btn--sm" data-iwe-add-session>Προσθήκη ώρας</button>
				</fieldset>

				<div class="iwe-flags">
					<label class="iwe-check">
						<input type="checkbox" name="<?php echo esc_attr( $field ); ?>[active]" value="1"<?php checked( ! empty( $programme['active'] ) ); ?>>
						Ενεργό — φαίνεται στη σελίδα και στις κρατήσεις
					</label>
					<label class="iwe-check">
						<input type="checkbox" name="<?php echo esc_attr( $field ); ?>[popular]" value="1"<?php checked( ! empty( $programme['popular'] ) ); ?>>
						Σήμανση «Δημοφιλές» στο popup
					</label>
				</div>
			</div>
		</details>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'ioulia_programmes_panel' ) ) {
	function ioulia_programmes_panel() {
		$programmes = ioulia_workshop_programmes();

		ob_start();
		?>
		<form class="iwe" method="post" data-iwe>
			<?php wp_nonce_field( 'ioulia_dashboard_programmes', 'iwd_nonce' ); ?>
			<input type="hidden" name="iwd_action" value="programmes">

			<div data-iwe-list data-next="<?php echo esc_attr( count( $programmes ) ); ?>">
				<?php
				$index = 0;

				foreach ( $programmes as $slug => $programme ) {
					echo ioulia_editor_programme( $index, $slug, $programme ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
					$index++;
				}
				?>
			</div>

			<div class="iwe-actions">
				<button type="button" class="ioulia-btn ioulia-btn--outline ioulia-btn--sm" data-iwe-add>Νέο πρόγραμμα</button>
				<button type="submit" class="ioulia-btn ioulia-btn--sm">Αποθήκευση</button>
			</div>

			<p class="iwe-hint">
				Ένα πρόγραμμα που δεν χρειάζεται πια το κάνεις ανενεργό αντί να το σβήσεις,
				ώστε οι παλιές κρατήσεις να συνεχίσουν να διαβάζονται σωστά.
			</p>

			<template data-iwe-template>
				<?php
				echo ioulia_editor_programme( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
					'__i__',
					'',
					ioulia_workshop_shape( array( 'active' => true, 'capacity' => 8, 'price' => 25 ) ),
					true
				);
				?>
			</template>
		</form>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'ioulia_programmes_handle_post' ) ) {
	/**
	 * Called by the dashboard's own template_redirect handler, so the outcome
	 * lands in the same notice the rest of the page uses.
	 */
	function ioulia_programmes_handle_post() {
		$raw = isset( $_POST['prog'] ) ? wp_unslash( $_POST['prog'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- shaped below.

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return new WP_Error( 'ioulia_no_programmes', 'Δεν ήρθε τίποτα να αποθηκευτεί.' );
		}

		$set  = array();
		$used = array();

		foreach ( $raw as $programme ) {
			$programme = (array) $programme;
			$title     = sanitize_text_field( (string) ( $programme['title'] ?? '' ) );

			if ( '' === $title ) {
				continue;
			}

			/* A new programme arrives with no slug, so one is made from its
			   title. An existing one keeps the slug it was booked under - a
			   renamed programme must not orphan the bookings that point at it. */
			$slug = sanitize_title( (string) ( $programme['slug'] ?? '' ) );

			if ( '' === $slug ) {
				$slug = sanitize_title( $title );
			}

			if ( '' === $slug ) {
				$slug = 'programme';
			}

			$base   = $slug;
			$suffix = 2;

			while ( isset( $used[ $slug ] ) ) {
				$slug = $base . '-' . $suffix;
				$suffix++;
			}

			$used[ $slug ] = true;

			$programme['sessions'] = array_values( (array) ( $programme['sessions'] ?? array() ) );
			$set[ $slug ]          = $programme;
		}

		return ioulia_workshop_save_programmes( $set );
	}
}
