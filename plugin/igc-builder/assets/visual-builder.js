(function () {
	'use strict';
	const boot = () => {

	const root = document.querySelector('.studio-builder');
	if (!root || !window.SiteStudioBuilder) return;

	const config = window.SiteStudioBuilder;
	const preview = document.querySelector('#studio-preview');
	const canvasWrap = document.querySelector('.studio-canvas-wrap');
	const dirtyState = document.querySelector('#studio-dirty-state');
	const form = document.querySelector('#studio-builder-form');
	const editors = {};
	let previewTimer;
	let previewController;
	let previewSequence = 0;

	['html', 'css', 'js'].forEach((type) => {
		const textarea = document.querySelector('#studio-' + type);
		if (window.wp && wp.codeEditor && config.editors[type]) {
			editors[type] = wp.codeEditor.initialize(textarea, config.editors[type]).codemirror;
			editors[type].on('change', changed);
		} else {
			editors[type] = {
				getValue: () => textarea.value,
				setValue: (value) => { textarea.value = value; },
				save: () => {},
				replaceSelection: (value) => { textarea.value += value; },
				refresh: () => {}
			};
			textarea.addEventListener('input', changed);
		}
	});

	function changed() {
		dirtyState.textContent = 'Unsaved changes';
		dirtyState.classList.add('is-dirty');
		clearTimeout(previewTimer);
		previewTimer = setTimeout(renderPreview, 280);
	}

	function tokenCss() {
		const t = config.tokens || {};
		const colors = t.colors || {};
		const type = t.typography || {};
		const layout = t.layout || {};
		return `:root {
--studio-color-background:${colors.background || '#fff'};
--studio-color-text:${colors.text || '#171716'};
--studio-color-accent:${colors.accent || '#7257e8'};
--studio-color-muted:${colors.muted || '#777'};
--studio-color-border:${colors.border || '#ddd'};
--studio-font-body:${type.body_font || 'Arial, sans-serif'};
--studio-font-heading:${type.heading_font || 'Arial, sans-serif'};
--studio-font-size:${type.base_size || '16px'};
--studio-line-height:${type.line_height || '1.5'};
--studio-content-width:${layout.content_width || '900px'};
--studio-wide-width:${layout.wide_width || '1440px'};
--studio-space:${layout.space || '8px'};
--studio-radius:${layout.radius || '0px'};
}`;
	}

	function previewHtml(html) {
		return html.replace(/\[[a-zA-Z_][^\]]*\]/g, (shortcode) => {
			const label = shortcode.length > 54 ? shortcode.slice(0, 51) + '…]' : shortcode;
			return `<div class="studio-shortcode-preview" title="${escapeAttribute(config.previewTitle)}"><span>Shortcode</span><code>${escapeHtml(label)}</code></div>`;
		});
	}

	function localPreview(message = '') {
		const html = previewHtml(editors.html.getValue());
		const css = editors.css.getValue();
		const runJs = document.querySelector('#studio-run-js').checked;
		const js = runJs ? editors.js.getValue().replace(/<\/script/gi, '<\\/script') : '';
		const external = config.externalCss ? `<link rel="stylesheet" href="${escapeAttribute(config.externalCss)}">` : '';
		const notice = message ? `<div class="studio-preview-notice">${escapeHtml(message)}</div>` : '';
		preview.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${external}<style>${tokenCss()}\n${config.globalCss || ''}\n*{box-sizing:border-box}html,body{margin:0;min-height:100%;font:var(--studio-font-size)/var(--studio-line-height) var(--studio-font-body);background:var(--studio-color-background);color:var(--studio-color-text)}img{max-width:100%;height:auto}.studio-section{padding:calc(var(--studio-space)*8) 24px}.studio-container{width:min(100%,var(--studio-wide-width));margin-inline:auto}.studio-stack{display:flex;flex-direction:column;gap:calc(var(--studio-space)*2)}.studio-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:calc(var(--studio-space)*2)}.studio-shortcode-preview{margin:12px 0;padding:14px;border:1px dashed #aaa;border-radius:6px;background:#f7f7f4;color:#555}.studio-shortcode-preview span{display:block;margin-bottom:5px;font-size:10px;text-transform:uppercase;letter-spacing:.1em}.studio-shortcode-preview code{font-size:11px}.studio-preview-notice{position:sticky;top:0;z-index:99999;padding:9px 14px;background:#fff1cd;color:#654d0b;font:12px/1.4 system-ui,sans-serif;border-bottom:1px solid #ead28b}@media(max-width:700px){.studio-grid{grid-template-columns:1fr}}\n${css}</style></head><body>${notice}${html}${runJs && js ? `<script>${js}<\/script>` : ''}</body></html>`;
	}

	async function renderPreview() {
		if (!document.querySelector('#studio-render-wp').checked) {
			if (previewController) previewController.abort();
			localPreview();
			return;
		}

		const sequence = ++previewSequence;
		if (previewController) previewController.abort();
		previewController = new AbortController();
		preview.setAttribute('aria-busy', 'true');
		const payload = new URLSearchParams({
			action: 'igc_render_canvas_preview',
			nonce: config.previewNonce,
			html: editors.html.getValue(),
			css: editors.css.getValue(),
			js: editors.js.getValue(),
			run_js: document.querySelector('#studio-run-js').checked ? '1' : ''
		});

		try {
			const response = await fetch(config.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: payload.toString(),
				signal: previewController.signal,
				credentials: 'same-origin'
			});
			const result = await response.json();
			if (!response.ok || !result.success || !result.data || !result.data.document) {
				throw new Error(result.data && result.data.message ? result.data.message : config.previewError);
			}
			if (sequence === previewSequence) preview.srcdoc = result.data.document;
		} catch (error) {
			if (error.name !== 'AbortError' && sequence === previewSequence) localPreview(config.previewError);
		} finally {
			if (sequence === previewSequence) preview.removeAttribute('aria-busy');
		}
	}

	function escapeHtml(value) {
		return value.replace(/[&<>]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[character]));
	}

	function escapeAttribute(value) {
		return String(value).replace(/[&"<>]/g, (character) => ({ '&': '&amp;', '"': '&quot;', '<': '&lt;', '>': '&gt;' }[character]));
	}

	function buildLibrary() {
		const library = document.querySelector('#studio-widget-library');
		const groups = {};
		config.widgets.forEach((widget, index) => {
			(groups[widget.group] ||= []).push({ ...widget, index });
		});
		Object.entries(groups).forEach(([group, widgets]) => {
			const section = document.createElement('section');
			section.className = 'studio-widget-group';
			section.innerHTML = `<h3>${escapeHtml(group)}</h3><div class="studio-widget-grid"></div>`;
			const grid = section.querySelector('.studio-widget-grid');
			widgets.forEach((widget) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'studio-widget';
				button.draggable = true;
				button.dataset.widget = String(widget.index);
				button.dataset.label = (widget.group + ' ' + widget.label).toLowerCase();
				button.innerHTML = `<span class="studio-widget__icon">${escapeHtml(widget.icon)}</span><span class="studio-widget__label">${escapeHtml(widget.label)}</span>`;
				button.addEventListener('click', () => insertWidget(widget.index));
				button.addEventListener('dragstart', (event) => event.dataTransfer.setData('text/studio-widget', String(widget.index)));
				grid.appendChild(button);
			});
			library.appendChild(section);
		});
	}

	function insertWidget(index) {
		const widget = config.widgets[index];
		if (!widget) return;
		const code = `\n${widget.html}\n`;
		editors.html.replaceSelection(code);
		showEditor('html');
		changed();
	}

	function showEditor(type) {
		document.querySelectorAll('.studio-code-tabs button').forEach((button) => button.classList.toggle('is-active', button.dataset.editor === type));
		document.querySelectorAll('.studio-editor').forEach((panel) => panel.classList.toggle('is-active', panel.dataset.panel === type));
		setTimeout(() => editors[type].refresh(), 0);
	}

	document.querySelectorAll('.studio-code-tabs button').forEach((button) => button.addEventListener('click', () => showEditor(button.dataset.editor)));
	document.querySelectorAll('.studio-devices button').forEach((button) => button.addEventListener('click', () => {
		document.querySelectorAll('.studio-devices button').forEach((item) => item.classList.remove('is-active'));
		button.classList.add('is-active');
		preview.style.width = button.dataset.width;
	}));
	document.querySelector('#studio-run-js').addEventListener('change', renderPreview);
	document.querySelector('#studio-render-wp').addEventListener('change', renderPreview);

	document.querySelector('#studio-widget-search').addEventListener('input', (event) => {
		const query = event.target.value.trim().toLowerCase();
		document.querySelectorAll('.studio-widget').forEach((widget) => { widget.hidden = query && !widget.dataset.label.includes(query); });
		document.querySelectorAll('.studio-widget-group').forEach((group) => { group.hidden = !group.querySelector('.studio-widget:not([hidden])'); });
	});

	canvasWrap.addEventListener('dragover', (event) => { event.preventDefault(); canvasWrap.classList.add('is-drop-target'); });
	canvasWrap.addEventListener('dragleave', () => canvasWrap.classList.remove('is-drop-target'));
	canvasWrap.addEventListener('drop', (event) => {
		event.preventDefault();
		canvasWrap.classList.remove('is-drop-target');
		insertWidget(Number(event.dataTransfer.getData('text/studio-widget')));
	});

	const dialog = document.querySelector('#studio-import-dialog');
	document.querySelector('[data-action="import-html"]').addEventListener('click', () => dialog.showModal());
	document.querySelector('#studio-import-confirm').addEventListener('click', (event) => {
		event.preventDefault();
		const source = document.querySelector('#studio-full-html').value.trim();
		if (!source) return;
		const doc = new DOMParser().parseFromString(source, 'text/html');
		const styles = Array.from(doc.querySelectorAll('style')).map((node) => node.textContent).join('\n\n');
		const scripts = Array.from(doc.querySelectorAll('script:not([src])')).map((node) => node.textContent).join('\n\n');
		doc.querySelectorAll('style, script').forEach((node) => node.remove());
		editors.html.setValue(doc.body.innerHTML.trim());
		if (styles.trim()) editors.css.setValue(styles.trim());
		if (scripts.trim()) editors.js.setValue(scripts.trim());
		dialog.close();
		changed();
	});

	document.querySelectorAll('[data-save]').forEach((button) => button.addEventListener('click', () => {
		document.querySelector('#studio-save-status').value = button.dataset.save;
	}));
	form.addEventListener('submit', () => Object.values(editors).forEach((editor) => editor.save()));
	document.querySelector('.studio-title').addEventListener('input', changed);
	document.addEventListener('keydown', (event) => {
		if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
			event.preventDefault();
			document.querySelector('[data-save="draft"]').click();
		}
	});

	window.addEventListener('resize', () => {
		const active = document.querySelector('.studio-code-tabs button.is-active');
		if (active) editors[active.dataset.editor].refresh();
	});

	buildLibrary();
	renderPreview();

	// CodeMirror instances created while their tab is hidden can measure a 0 height;
	// refresh once more after the initial layout settles to fix that.
	setTimeout(() => Object.values(editors).forEach((editor) => editor.refresh()), 50);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot, { once: true });
	} else {
		boot();
	}
}());
