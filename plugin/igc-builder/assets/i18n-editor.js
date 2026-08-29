/**
 * Site Studio — translation editor.
 *
 * Finds every readable string on the page without touching the DOM, so what you
 * are editing stays byte for byte what a visitor sees. Strings are discovered in
 * the browser and sent to the server to be looked up, which means nothing has to
 * be registered in advance: whatever renders is translatable.
 */
(function () {
	var config = window.igcI18nEditor;

	if (!config || document.getElementById('igc-i18n-panel')) {
		return;
	}

	var text = config.strings;
	var entries = [];
	var bySource = {};
	var translations = {};
	var active = null;
	var highlight = null;
	var panel;
	var listEl;
	var editEl;
	var sourceEl;
	var inputEl;
	var statusEl;
	var searchEl;

	function normalize(value) {
		return String(value).replace(/\s+/g, ' ').trim();
	}

	function skipped(node) {
		var el = node.parentElement;

		while (el) {
			if (el === panel || el.id === 'wpadminbar' || el.isContentEditable) {
				return true;
			}

			if (config.skipTags.indexOf(el.tagName) !== -1) {
				return true;
			}

			el = el.parentElement;
		}

		return false;
	}

	function register(source, target) {
		if (!source || source.length > 20000) {
			return;
		}

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

			if (source && !skipped(node)) {
				register(source, { node: node });
			}
		}

		config.attributes.forEach(function (attribute) {
			Array.prototype.forEach.call(document.querySelectorAll('[' + attribute + ']'), function (el) {
				if (panel.contains(el) || el.closest('#wpadminbar')) {
					return;
				}

				register(normalize(el.getAttribute(attribute)), { element: el, attribute: attribute });
			});
		});
	}

	function post(action, data) {
		var body = new FormData();

		body.append('action', action);
		body.append('nonce', config.nonce);
		body.append('language', config.language);

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
			highlight.className = 'igc-i18n-highlight';
			document.body.appendChild(highlight);
		}

		var rect = rectOf(entry.targets[0]);

		if (!rect || (!rect.width && !rect.height)) {
			highlight.style.display = 'none';
			return;
		}

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
			row.className = 'igc-i18n-row' + (active === entry ? ' is-active' : '');

			var src = document.createElement('span');
			src.className = 'src';
			src.textContent = entry.source.length > 90 ? entry.source.slice(0, 90) + '...' : entry.source;

			var dst = document.createElement('span');
			dst.className = 'dst' + (translated ? '' : ' is-missing');
			dst.textContent = translated || text.untranslated;

			row.appendChild(src);
			row.appendChild(dst);
			row.addEventListener('click', function () { select(entry, true); });
			row.addEventListener('mouseenter', function () { showHighlight(entry, false); });

			listEl.appendChild(row);
		});

		if (!listEl.children.length) {
			var empty = document.createElement('div');
			empty.className = 'igc-i18n-row';
			empty.textContent = text.empty;
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

			if (el && el.scrollIntoView) {
				el.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}
		}

		window.setTimeout(function () { showHighlight(entry, true); }, scrollIntoView ? 400 : 0);
		inputEl.focus();
	}

	function entryFromEvent(event) {
		var node = event.target;

		if (!node || panel.contains(node) || (node.closest && node.closest('#wpadminbar'))) {
			return null;
		}

		var texts = Array.prototype.filter.call(node.childNodes || [], function (child) {
			return child.nodeType === 3 && normalize(child.nodeValue);
		});

		if (texts.length) {
			return bySource[normalize(texts[0].nodeValue)] || null;
		}

		for (var i = 0; i < config.attributes.length; i++) {
			var value = node.getAttribute && node.getAttribute(config.attributes[i]);

			if (value && bySource[normalize(value)]) {
				return bySource[normalize(value)];
			}
		}

		return null;
	}

	function save() {
		if (!active) {
			return;
		}

		var entry = active;
		statusEl.textContent = text.saving;

		post('igc_i18n_save', { source: entry.source, translation: inputEl.value })
			.then(function (result) {
				if (!result || !result.success) {
					statusEl.textContent = (result && result.data && result.data.message) || text.failed;
					return;
				}

				translations[entry.source] = result.data.text;
				statusEl.textContent = text.saved;
				renderList();
			})
			.catch(function () { statusEl.textContent = text.offline; });
	}

	function build() {
		panel = document.createElement('aside');
		panel.id = 'igc-i18n-panel';
		panel.innerHTML =
			'<div class="igc-i18n-head"><div><strong></strong><small></small></div>' +
			'<button type="button" class="igc-i18n-btn" data-exit></button></div>' +
			'<div class="igc-i18n-search"><input type="search" data-search></div>' +
			'<div class="igc-i18n-list" data-list></div>' +
			'<div class="igc-i18n-edit" hidden data-edit>' +
			'<label data-source-label></label><div class="igc-i18n-source" data-source></div>' +
			'<label data-target-label></label><textarea data-input spellcheck="true"></textarea>' +
			'<div class="igc-i18n-actions">' +
			'<button type="button" class="igc-i18n-btn primary" data-save></button>' +
			'<button type="button" class="igc-i18n-btn" data-clear></button>' +
			'<span class="igc-i18n-status" data-status></span></div></div>' +
			'<div class="igc-i18n-foot"><a class="igc-i18n-btn" data-preview target="_blank" rel="noopener"></a></div>';

		document.body.appendChild(panel);

		panel.querySelector('strong').textContent = text.panel;
		panel.querySelector('small').textContent = config.name;
		panel.querySelector('[data-exit]').textContent = text.exit;
		panel.querySelector('[data-search]').placeholder = text.search;
		panel.querySelector('[data-source-label]').textContent = text.source;
		panel.querySelector('[data-target-label]').textContent = config.name;
		panel.querySelector('[data-save]').textContent = text.save;
		panel.querySelector('[data-clear]').textContent = text.clear;

		var preview = panel.querySelector('[data-preview]');
		preview.textContent = text.preview;
		preview.href = config.previewUrl;

		listEl = panel.querySelector('[data-list]');
		editEl = panel.querySelector('[data-edit]');
		sourceEl = panel.querySelector('[data-source]');
		inputEl = panel.querySelector('[data-input]');
		statusEl = panel.querySelector('[data-status]');
		searchEl = panel.querySelector('[data-search]');

		panel.querySelector('[data-save]').addEventListener('click', save);
		panel.querySelector('[data-clear]').addEventListener('click', function () {
			inputEl.value = '';
			save();
		});
		panel.querySelector('[data-exit]').addEventListener('click', function () {
			window.location.href = config.exitUrl;
		});

		searchEl.addEventListener('input', renderList);

		inputEl.addEventListener('keydown', function (event) {
			if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
				event.preventDefault();
				save();
			}
		});
	}

	function start() {
		build();
		document.body.classList.add('igc-i18n-editing');
		collect();

		document.addEventListener('mouseover', function (event) {
			var entry = entryFromEvent(event);

			if (entry && entry !== active) {
				showHighlight(entry, false);
			}
		});

		document.addEventListener('click', function (event) {
			var entry = entryFromEvent(event);

			if (!entry) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			select(entry, false);
		}, true);

		window.addEventListener('resize', function () {
			if (active) {
				showHighlight(active, true);
			}
		});

		post('igc_i18n_lookup', { sources: entries.map(function (entry) { return entry.source; }) })
			.then(function (result) {
				if (result && result.success) {
					translations = result.data.translations || {};
				}

				renderList();
			})
			.catch(renderList);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
}());
