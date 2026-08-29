<?php
/**
 * Ioulia i18n editor — click a piece of Greek text on the site and translate it.
 *
 * Open any Greek page as an administrator and use "Μετάφραση σελίδας" in the
 * admin bar (or add ?ioulia_translate=en to the URL). Every readable string on
 * the page becomes clickable; a panel on the right holds the Greek original and
 * the English translation.
 *
 * The editor never modifies the page markup. It finds text with a TreeWalker and
 * draws a highlight over it, so nothing about the layout, the CSS selectors or
 * the animations changes while you are editing.
 *
 * Translations are stored by the hash of the Greek text, so translating a string
 * once translates it on every page it appears on.
 *
 * Requires the "i18n core" and "i18n translate" snippets.
 * Everything here is gated on manage_options and does nothing for visitors.
 */

if ( ! defined( 'IOULIA_I18N_EDIT_PARAM' ) ) {
	define( 'IOULIA_I18N_EDIT_PARAM', 'ioulia_translate' );
}

if ( ! function_exists( 'ioulia_editor_can_edit' ) ) {
	function ioulia_editor_can_edit() {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}
}

if ( ! function_exists( 'ioulia_editor_target_lang' ) ) {
	/**
	 * Language being translated into, or '' when the editor is not active.
	 * The editor only ever runs on a default-language page: the Greek text on it
	 * is the source you are translating from.
	 */
	function ioulia_editor_target_lang() {
		static $lang = null;

		if ( null !== $lang ) {
			return $lang;
		}

		$lang = '';

		if ( ! ioulia_editor_can_edit() || is_admin() || ! ioulia_is_default_lang() ) {
			return $lang;
		}

		$requested = isset( $_GET[ IOULIA_I18N_EDIT_PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ IOULIA_I18N_EDIT_PARAM ] ) ) : '';
		$languages = ioulia_languages();

		if ( isset( $languages[ $requested ] ) && IOULIA_LANG_DEFAULT !== $requested ) {
			$lang = $requested;
		}

		return $lang;
	}
}

/* -------------------------------------------------------------------------
 * Admin bar entry
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_editor_admin_bar' ) ) {
	function ioulia_editor_admin_bar( $bar ) {
		if ( is_admin() || ! ioulia_editor_can_edit() || ! ioulia_is_default_lang() ) {
			return;
		}

		$languages = ioulia_languages();

		foreach ( $languages as $code => $language ) {
			if ( IOULIA_LANG_DEFAULT === $code ) {
				continue;
			}

			$bar->add_node(
				array(
					'id'    => 'ioulia-translate-' . $code,
					'title' => sprintf( 'Μετάφραση σελίδας → %s', $language['label'] ),
					'href'  => add_query_arg( IOULIA_I18N_EDIT_PARAM, $code ),
					'meta'  => array( 'title' => $language['name'] ),
				)
			);
		}
	}
	add_action( 'admin_bar_menu', 'ioulia_editor_admin_bar', 100 );
}

/* -------------------------------------------------------------------------
 * AJAX: batch lookup, save, export
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_editor_guard' ) ) {
	function ioulia_editor_guard() {
		check_ajax_referer( 'ioulia_i18n_editor', 'nonce' );

		if ( ! ioulia_editor_can_edit() ) {
			wp_send_json_error( array( 'message' => 'Δεν έχεις δικαίωμα επεξεργασίας μεταφράσεων.' ), 403 );
		}
	}
}

if ( ! function_exists( 'ioulia_editor_request_lang' ) ) {
	function ioulia_editor_request_lang() {
		$lang      = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		$languages = ioulia_languages();

		if ( ! isset( $languages[ $lang ] ) || IOULIA_LANG_DEFAULT === $lang ) {
			wp_send_json_error( array( 'message' => 'Άγνωστη γλώσσα.' ), 400 );
		}

		return $lang;
	}
}

if ( ! function_exists( 'ioulia_editor_lookup_ajax' ) ) {
	/**
	 * The browser reports every string it found on the page; we answer with the
	 * translation each one currently has.
	 */
	function ioulia_editor_lookup_ajax() {
		ioulia_editor_guard();

		$lang    = ioulia_editor_request_lang();
		$sources = isset( $_POST['sources'] ) ? (array) wp_unslash( $_POST['sources'] ) : array();
		$result  = array();

		foreach ( array_slice( $sources, 0, 2000 ) as $source ) {
			$source = ioulia_normalize_source( (string) $source );

			if ( '' === $source ) {
				continue;
			}

			$result[ $source ] = ioulia_lookup_translation( $lang, $source );
		}

		wp_send_json_success( array( 'translations' => $result ) );
	}
	add_action( 'wp_ajax_ioulia_i18n_lookup', 'ioulia_editor_lookup_ajax' );
}

if ( ! function_exists( 'ioulia_editor_save_ajax' ) ) {
	function ioulia_editor_save_ajax() {
		ioulia_editor_guard();

		$lang        = ioulia_editor_request_lang();
		$source      = isset( $_POST['source'] ) ? (string) wp_unslash( $_POST['source'] ) : '';
		$translation = isset( $_POST['translation'] ) ? (string) wp_unslash( $_POST['translation'] ) : '';
		$saved       = ioulia_save_translation( $lang, $source, $translation );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'source' => ioulia_normalize_source( $source ),
				'text'   => isset( $saved['text'] ) ? $saved['text'] : '',
			)
		);
	}
	add_action( 'wp_ajax_ioulia_i18n_save', 'ioulia_editor_save_ajax' );
}

if ( ! function_exists( 'ioulia_php_literal' ) ) {
	/**
	 * A single-quoted PHP string literal that survives a Site Studio import.
	 *
	 * Snippet code is stored through update_post_meta(), which unslashes it, so a
	 * backslash written into a snippet never reaches PHP. Escapes are therefore
	 * unusable and an apostrophe has to be concatenated in as chr( 39 ) instead.
	 * Backslashes in the text itself are dropped for the same reason.
	 */
	function ioulia_php_literal( $value ) {
		$value = str_replace( chr( 92 ), '', (string) $value );
		$parts = array();

		foreach ( explode( chr( 39 ), $value ) as $part ) {
			$parts[] = chr( 39 ) . $part . chr( 39 );
		}

		return implode( ' . chr( 39 ) . ', $parts );
	}
}

if ( ! function_exists( 'ioulia_editor_export_ajax' ) ) {
	/**
	 * Everything saved through the editor, rendered as the PHP array the
	 * "i18n seed" snippet expects, so a round of corrections can be committed.
	 */
	function ioulia_editor_export_ajax() {
		ioulia_editor_guard();

		$lang  = ioulia_editor_request_lang();
		$store = ioulia_translation_store( true );
		$rows  = isset( $store[ $lang ] ) && is_array( $store[ $lang ] ) ? $store[ $lang ] : array();
		$indent = str_repeat( chr( 9 ), 4 );
		$lines  = array();

		foreach ( $rows as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['source'], $entry['text'] ) ) {
				continue;
			}

			$lines[] = $indent . ioulia_php_literal( $entry['source'] ) . ' => ' . ioulia_php_literal( $entry['text'] ) . ',';
		}

		sort( $lines );

		wp_send_json_success(
			array(
				'count' => count( $lines ),
				'php'   => implode( chr( 10 ), $lines ),
			)
		);
	}
	add_action( 'wp_ajax_ioulia_i18n_export', 'ioulia_editor_export_ajax' );
}

/* -------------------------------------------------------------------------
 * The editor itself
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'ioulia_editor_render' ) ) {
	function ioulia_editor_render() {
		$lang = ioulia_editor_target_lang();

		if ( '' === $lang ) {
			return;
		}

		$languages = ioulia_languages();
		$config    = array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'ioulia_i18n_editor' ),
			'lang'       => $lang,
			'langName'   => isset( $languages[ $lang ]['name'] ) ? $languages[ $lang ]['name'] : $lang,
			'skipTags'   => array_map( 'strtoupper', ioulia_html_skip_tags() ),
			'whitespace' => chr( 32 ) . chr( 9 ) . chr( 10 ) . chr( 13 ) . chr( 12 ),
			'attributes' => ioulia_translatable_attributes(),
			'previewUrl' => ioulia_alternate_url( $lang ),
			'exitUrl'    => remove_query_arg( IOULIA_I18N_EDIT_PARAM ),
		);
		?>
<style id="ioulia-i18n-editor-css">
	.ioulia-i18n-highlight {
		position: absolute;
		z-index: 2147483000;
		pointer-events: none;
		border: 1px solid #7C3737;
		background: rgba(124, 55, 55, .12);
		border-radius: 2px;
		transition: opacity .12s ease;
	}
	.ioulia-i18n-highlight.is-active {
		background: rgba(124, 55, 55, .22);
		border-width: 2px;
	}
	#ioulia-i18n-panel {
		position: fixed;
		top: 0;
		right: 0;
		bottom: 0;
		width: 380px;
		max-width: 100vw;
		z-index: 2147483001;
		display: flex;
		flex-direction: column;
		background: #FFFEF7;
		border-left: 1px solid rgba(43, 43, 43, .2);
		box-shadow: 0 0 40px rgba(43, 43, 43, .12);
		font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
		font-size: 13px;
		line-height: 1.5;
		color: #2B2B2B;
	}
	#ioulia-i18n-panel[hidden] { display: none; }
	.ioulia-i18n-head {
		padding: 14px 16px;
		border-bottom: 1px solid rgba(43, 43, 43, .12);
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
	}
	.ioulia-i18n-head strong { font-size: 13px; font-weight: 600; }
	.ioulia-i18n-head small { display: block; color: rgba(43, 43, 43, .6); font-size: 11px; }
	.ioulia-i18n-search { padding: 10px 16px; border-bottom: 1px solid rgba(43, 43, 43, .12); }
	.ioulia-i18n-search input {
		width: 100%;
		padding: 7px 9px;
		border: 1px solid rgba(43, 43, 43, .25);
		border-radius: 4px;
		font: inherit;
		background: #fff;
	}
	.ioulia-i18n-list { flex: 1 1 auto; overflow-y: auto; }
	.ioulia-i18n-row {
		display: block;
		width: 100%;
		text-align: left;
		padding: 10px 16px;
		border: 0;
		border-bottom: 1px solid rgba(43, 43, 43, .08);
		background: transparent;
		font: inherit;
		color: inherit;
		cursor: pointer;
	}
	.ioulia-i18n-row:hover { background: rgba(124, 55, 55, .06); }
	.ioulia-i18n-row.is-active { background: rgba(124, 55, 55, .12); }
	.ioulia-i18n-row .src { display: block; }
	.ioulia-i18n-row .dst { display: block; color: rgba(43, 43, 43, .55); font-size: 11px; margin-top: 3px; }
	.ioulia-i18n-row .dst.is-missing { color: #A33; }
	.ioulia-i18n-edit { border-top: 1px solid rgba(43, 43, 43, .12); padding: 12px 16px; background: #fff; }
	.ioulia-i18n-edit label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: rgba(43, 43, 43, .55); margin-bottom: 4px; }
	.ioulia-i18n-source {
		background: rgba(43, 43, 43, .05);
		border-radius: 4px;
		padding: 8px 9px;
		margin-bottom: 10px;
		max-height: 110px;
		overflow-y: auto;
		white-space: pre-wrap;
	}
	.ioulia-i18n-edit textarea {
		width: 100%;
		min-height: 90px;
		padding: 8px 9px;
		border: 1px solid rgba(43, 43, 43, .25);
		border-radius: 4px;
		font: inherit;
		resize: vertical;
	}
	.ioulia-i18n-actions { display: flex; gap: 8px; align-items: center; margin-top: 10px; }
	.ioulia-i18n-btn {
		border: 1px solid rgba(43, 43, 43, .25);
		background: #fff;
		border-radius: 4px;
		padding: 6px 12px;
		font: inherit;
		cursor: pointer;
	}
	.ioulia-i18n-btn.primary { background: #7C3737; border-color: #7C3737; color: #fff; }
	.ioulia-i18n-btn[disabled] { opacity: .5; cursor: default; }
	.ioulia-i18n-status { font-size: 11px; color: rgba(43, 43, 43, .6); margin-left: auto; }
	.ioulia-i18n-foot { padding: 10px 16px; border-top: 1px solid rgba(43, 43, 43, .12); display: flex; gap: 8px; }
	.ioulia-i18n-foot a, .ioulia-i18n-foot button { font-size: 11px; }
	body.ioulia-i18n-editing { cursor: crosshair; }
</style>

<div id="ioulia-i18n-panel" role="complementary" aria-label="Επεξεργασία μεταφράσεων">
	<div class="ioulia-i18n-head">
		<div>
			<strong>Μετάφραση</strong>
			<small>Ελληνικά → <?php echo esc_html( $config['langName'] ); ?></small>
		</div>
		<button type="button" class="ioulia-i18n-btn" data-ioulia-exit>Έξοδος</button>
	</div>

	<div class="ioulia-i18n-search">
		<input type="search" placeholder="Αναζήτηση στη σελίδα…" data-ioulia-search>
	</div>

	<div class="ioulia-i18n-list" data-ioulia-list></div>

	<div class="ioulia-i18n-edit" hidden data-ioulia-edit>
		<label>Ελληνικό πρωτότυπο</label>
		<div class="ioulia-i18n-source" data-ioulia-source></div>
		<label for="ioulia-i18n-input"><?php echo esc_html( $config['langName'] ); ?></label>
		<textarea id="ioulia-i18n-input" data-ioulia-input spellcheck="true"></textarea>
		<div class="ioulia-i18n-actions">
			<button type="button" class="ioulia-i18n-btn primary" data-ioulia-save>Αποθήκευση</button>
			<button type="button" class="ioulia-i18n-btn" data-ioulia-reset>Καθαρισμός</button>
			<span class="ioulia-i18n-status" data-ioulia-status></span>
		</div>
	</div>

	<div class="ioulia-i18n-foot">
		<a class="ioulia-i18n-btn" href="<?php echo esc_url( $config['previewUrl'] ); ?>" target="_blank" rel="noopener">Προεπισκόπηση</a>
		<button type="button" class="ioulia-i18n-btn" data-ioulia-export>Εξαγωγή για Git</button>
	</div>
</div>

<script id="ioulia-i18n-editor-js">
(function () {
	if (window.iouliaI18nEditor) { return; }

	var config = <?php echo wp_json_encode( $config ); ?>;
	var panel = document.getElementById('ioulia-i18n-panel');
	if (!panel) { return; }

	window.iouliaI18nEditor = true;

	var listEl = panel.querySelector('[data-ioulia-list]');
	var editEl = panel.querySelector('[data-ioulia-edit]');
	var sourceEl = panel.querySelector('[data-ioulia-source]');
	var inputEl = panel.querySelector('[data-ioulia-input]');
	var statusEl = panel.querySelector('[data-ioulia-status]');
	var searchEl = panel.querySelector('[data-ioulia-search]');

	var entries = [];          /* { source, targets: [{node|element, attr}] } */
	var bySource = {};
	var translations = {};
	var active = null;
	var highlight = null;

	/* Built at runtime from real whitespace characters: an escape written in the
	   snippet source is stripped before PHP ever sees it. */
	var WHITESPACE = new RegExp('[' + config.whitespace + ']+', 'g');

	function normalize(text) {
		return String(text).replace(WHITESPACE, ' ').trim();
	}

	function isSkipped(node) {
		var el = node.parentElement;
		while (el) {
			if (el === panel || el.id === 'wpadminbar') { return true; }
			if (config.skipTags.indexOf(el.tagName) !== -1) { return true; }
			if (el.isContentEditable) { return true; }
			el = el.parentElement;
		}
		return false;
	}

	function register(source, target) {
		if (!source || source.length > 20000) { return; }
		if (!bySource[source]) {
			bySource[source] = { source: source, targets: [] };
			entries.push(bySource[source]);
		}
		bySource[source].targets.push(target);
	}

	function collect() {
		var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
		var node;
		while ((node = walker.nextNode())) {
			var source = normalize(node.nodeValue);
			if (!source || isSkipped(node)) { continue; }
			register(source, { node: node });
		}

		config.attributes.forEach(function (attr) {
			var nodes = document.querySelectorAll('[' + attr + ']');
			Array.prototype.forEach.call(nodes, function (el) {
				if (panel.contains(el) || el.closest('#wpadminbar')) { return; }
				register(normalize(el.getAttribute(attr)), { element: el, attr: attr });
			});
		});
	}

	function post(action, data) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', config.nonce);
		body.append('lang', config.lang);
		Object.keys(data).forEach(function (key) {
			if (Array.isArray(data[key])) {
				data[key].forEach(function (value) { body.append(key + '[]', value); });
			} else {
				body.append(key, data[key]);
			}
		});
		return fetch(config.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (response) { return response.json(); });
	}

	function rectOf(target) {
		if (target.node) {
			var range = document.createRange();
			range.selectNodeContents(target.node);
			return range.getBoundingClientRect();
		}
		return target.element.getBoundingClientRect();
	}

	function showHighlight(entry, isActive) {
		if (!highlight) {
			highlight = document.createElement('div');
			highlight.className = 'ioulia-i18n-highlight';
			document.body.appendChild(highlight);
		}
		var rect = rectOf(entry.targets[0]);
		if (!rect || (!rect.width && !rect.height)) { highlight.style.display = 'none'; return; }
		highlight.style.display = 'block';
		highlight.classList.toggle('is-active', !!isActive);
		highlight.style.top = (rect.top + window.scrollY - 2) + 'px';
		highlight.style.left = (rect.left + window.scrollX - 2) + 'px';
		highlight.style.width = (rect.width + 4) + 'px';
		highlight.style.height = (rect.height + 4) + 'px';
	}

	function renderList() {
		var query = normalize(searchEl.value).toLowerCase();
		listEl.textContent = '';

		entries.forEach(function (entry) {
			var translated = translations[entry.source] || '';
			if (query && entry.source.toLowerCase().indexOf(query) === -1 && translated.toLowerCase().indexOf(query) === -1) {
				return;
			}

			var row = document.createElement('button');
			row.type = 'button';
			row.className = 'ioulia-i18n-row' + (active === entry ? ' is-active' : '');

			var src = document.createElement('span');
			src.className = 'src';
			src.textContent = entry.source.length > 90 ? entry.source.slice(0, 90) + '…' : entry.source;

			var dst = document.createElement('span');
			dst.className = 'dst' + (translated ? '' : ' is-missing');
			dst.textContent = translated || 'χωρίς μετάφραση';

			row.appendChild(src);
			row.appendChild(dst);
			row.addEventListener('click', function () { select(entry, true); });
			row.addEventListener('mouseenter', function () { showHighlight(entry, false); });
			listEl.appendChild(row);
		});

		if (!listEl.children.length) {
			var empty = document.createElement('div');
			empty.className = 'ioulia-i18n-row';
			empty.textContent = 'Κανένα κείμενο.';
			listEl.appendChild(empty);
		}
	}

	function select(entry, scrollIntoView) {
		active = entry;
		editEl.hidden = false;
		sourceEl.textContent = entry.source;
		inputEl.value = translations[entry.source] || '';
		statusEl.textContent = '';
		renderList();

		if (scrollIntoView) {
			var target = entry.targets[0];
			var el = target.node ? target.node.parentElement : target.element;
			if (el && el.scrollIntoView) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
		}

		window.setTimeout(function () { showHighlight(entry, true); }, scrollIntoView ? 400 : 0);
		inputEl.focus();
	}

	function entryFromEvent(event) {
		var node = event.target;
		if (!node || panel.contains(node) || (node.closest && node.closest('#wpadminbar'))) { return null; }

		var texts = Array.prototype.filter.call(node.childNodes || [], function (child) {
			return child.nodeType === 3 && normalize(child.nodeValue);
		});

		if (texts.length) {
			return bySource[normalize(texts[0].nodeValue)] || null;
		}

		for (var i = 0; i < config.attributes.length; i++) {
			var value = node.getAttribute && node.getAttribute(config.attributes[i]);
			if (value && bySource[normalize(value)]) { return bySource[normalize(value)]; }
		}

		return null;
	}

	function save() {
		if (!active) { return; }
		var entry = active;
		statusEl.textContent = 'Αποθήκευση…';

		post('ioulia_i18n_save', { source: entry.source, translation: inputEl.value })
			.then(function (response) {
				if (!response || !response.success) {
					statusEl.textContent = (response && response.data && response.data.message) || 'Σφάλμα.';
					return;
				}
				translations[entry.source] = response.data.text;
				statusEl.textContent = 'Αποθηκεύτηκε.';
				renderList();
			})
			.catch(function () { statusEl.textContent = 'Σφάλμα δικτύου.'; });
	}

	panel.querySelector('[data-ioulia-save]').addEventListener('click', save);

	panel.querySelector('[data-ioulia-reset]').addEventListener('click', function () {
		inputEl.value = '';
		save();
	});

	panel.querySelector('[data-ioulia-exit]').addEventListener('click', function () {
		window.location.href = config.exitUrl;
	});

	panel.querySelector('[data-ioulia-export]').addEventListener('click', function () {
		post('ioulia_i18n_export', {}).then(function (response) {
			if (!response || !response.success) { return; }
			inputEl.value = response.data.php;
			editEl.hidden = false;
			sourceEl.textContent = response.data.count + ' μεταφράσεις — αντίγραψέ τις στο snippet «i18n seed».';
			active = null;
			inputEl.select();
		});
	});

	searchEl.addEventListener('input', renderList);

	inputEl.addEventListener('keydown', function (event) {
		if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
			event.preventDefault();
			save();
		}
	});

	document.addEventListener('mouseover', function (event) {
		var entry = entryFromEvent(event);
		if (entry && entry !== active) { showHighlight(entry, false); }
	});

	document.addEventListener('click', function (event) {
		var entry = entryFromEvent(event);
		if (!entry) { return; }
		event.preventDefault();
		event.stopPropagation();
		select(entry, false);
	}, true);

	window.addEventListener('resize', function () { if (active) { showHighlight(active, true); } });

	document.body.classList.add('ioulia-i18n-editing');
	collect();

	post('ioulia_i18n_lookup', { sources: entries.map(function (entry) { return entry.source; }) })
		.then(function (response) {
			if (response && response.success) { translations = response.data.translations || {}; }
			renderList();
		})
		.catch(renderList);
}());
</script>
		<?php
	}
	add_action( 'wp_footer', 'ioulia_editor_render', 999 );
}
