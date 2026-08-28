# Site Studio workspace

This repository contains the code-driven presentation layer for **{{SITE_URL}}**.
It was exported by **Site Studio {{PLUGIN_VERSION}}** from WordPress.

This file is both project documentation and the operating contract for developers
and AI coding agents. Read it before changing anything.

## AI quick start

1. Read `site-studio.json`, this README, and the relevant `meta.json` files.
2. Run `git status` and inspect the latest commit before editing.
3. Treat `runtime/` as deployable site code.
4. Make the smallest focused change possible; preserve IDs, slugs, schemas, paths,
   shortcode names, WordPress hooks, and CSS class contracts unless the task
   explicitly requires changing them.
5. Validate every edited JSON, PHP, HTML, CSS, and JavaScript file.
6. Commit and push the change to the configured branch.
7. A WordPress administrator must run **Site Sync → Pull GitHub → Site** to deploy
   runtime changes. A Git push by itself does not change the live site.

Never add secrets, tokens, passwords, customer data, orders, bookings, media
binaries, database dumps, `.env` files, or WordPress security keys to this repo.

## Mental model and source of truth

Site Studio connects two representations of the same runtime code:

```text
WordPress database  ⇄  generated workspace files  ⇄  GitHub
       live                    editable                 history
```

- **Push Site → GitHub** exports the current WordPress Site Studio records,
  replaces the generated `runtime/` and `plugin/igc-builder/` snapshots, creates a
  timestamped commit, and pushes it.
- **Pull GitHub → Site** downloads the configured branch, validates the complete
  runtime, creates a local runtime backup, and imports the code into WordPress.
- Do not edit both WordPress and GitHub concurrently. If GitHub is newer, pull it
  before pushing WordPress. If WordPress is newer, push it before starting Git work.
- A later WordPress push can overwrite hand edits inside generated paths. Commit and
  deploy Git edits before someone changes the same item in the WordPress editor.

## What is and is not stored here

Tracked runtime code:

- global CSS, JavaScript, design tokens, and Site Studio runtime settings;
- Visual Canvas HTML/CSS/JS and page-routing metadata;
- reusable Code Blocks;
- Theme Templates and their display conditions;
- guarded PHP snippets and their activation scope;
- a review-only snapshot of the installed Site Studio plugin.

Not tracked:

- WordPress pages, post body content, users, settings outside Site Studio;
- WooCommerce products, variations, stock, customers, carts, orders, payments;
- workshop bookings or other operational records;
- Media Library files and uploads;
- database credentials, GitHub tokens, or server configuration.

Those remain in WordPress and must not be reconstructed from this repository.

## Repository structure

```text
.
├── README.md
├── site-studio.json
├── runtime/
│   ├── global/
│   │   ├── styles.css
│   │   ├── scripts.js
│   │   ├── design-tokens.json
│   │   └── settings.json
│   ├── canvases/<slug>--<wordpress-id>/
│   │   ├── meta.json
│   │   ├── index.html
│   │   ├── style.css
│   │   └── script.js
│   ├── blocks/<slug>--<wordpress-id>/
│   │   ├── meta.json
│   │   ├── index.html
│   │   ├── style.css
│   │   └── script.js
│   ├── templates/<slug>--<wordpress-id>/
│   │   ├── meta.json
│   │   ├── index.html
│   │   ├── style.css
│   │   └── script.js
│   └── snippets/<slug>--<wordpress-id>/
│       ├── meta.json
│       └── snippet.php
└── plugin/igc-builder/
    └── reviewable plugin source snapshot
```

Directories use `<slug>--<wordpress-id>` only for readability and stable export
paths. The importer uses `meta.json` as its authoritative identity data.

## Generated root metadata

`site-studio.json` is generated on every WordPress export:

```json
{
  "schema": 1,
  "plugin_version": "{{PLUGIN_VERSION}}",
  "site_url": "{{SITE_URL}}",
  "exported_at_gmt": "ISO-8601 timestamp"
}
```

Do not use this file for application logic and do not manually alter its site URL,
schema, or plugin version.

## Runtime item metadata

Every Canvas, Block, Template, and Snippet has a `meta.json` file with this base
shape:

```json
{
  "schema": 1,
  "id": 123,
  "post_type": "igc_canvas",
  "title": "Example",
  "slug": "example",
  "status": "publish",
  "menu_order": 0,
  "properties": {}
}
```

Allowed statuses are `publish`, `draft`, and `private`. On import, Site Studio first
looks up the numeric WordPress `id`; if it is missing or belongs to another type, it
falls back to the `slug` within that post type. Therefore:

- do not change `id`, `post_type`, or `slug` casually;
- keep directory names aligned with their metadata after intentional renames;
- changing identity fields can create a new record or update the wrong record;
- deleting a directory from Git does **not** delete its WordPress database record;
  current imports are upserts, not destructive synchronization.

### Canvases

Canvas `properties`:

```json
{
  "target_page_id": 42,
  "route_active": true
}
```

- `target_page_id` is the existing WordPress Page ID receiving the canvas.
- `route_active` makes that canvas render on the assigned public page when Site Mode
  and the surrounding routing conditions allow it.
- Only one active canvas should target a given page.
- `index.html` is an HTML fragment, not a complete document. Do not add `<html>`,
  `<head>`, or `<body>` unless the renderer explicitly expects them.
- Put page CSS in `style.css` without `<style>` tags.
- Put page JavaScript in `script.js` without `<script>` tags.

### Reusable blocks

Blocks use the base metadata with `post_type: "igc_code_block"`. Their `index.html`,
`style.css`, and `script.js` form one reusable unit. Preserve any shortcode or block
identifier referenced by canvases, templates, or PHP snippets.

### Theme templates

Template `properties`:

```json
{
  "location": "header",
  "include": "",
  "priority": 10
}
```

- `location` selects the Site Studio render location/template role.
- `include` is the stored display-condition string. Preserve its format unless the
  task explicitly changes template targeting.
- `priority` determines ordering when several templates match.
- Header, footer, WooCommerce archive, single-product, cart, and checkout behavior
  may depend on these templates. Test all matching contexts after edits.

### PHP snippets

Snippet `properties`:

```json
{
  "scope": "everywhere",
  "enabled": false
}
```

Allowed scopes are `everywhere`, `frontend`, and `admin`.

- `snippet.php` includes one leading `<?php` for tooling and syntax highlighting.
  Site Studio removes that first opening tag before storing the snippet.
- Internal PHP-to-HTML transitions are supported and must not be stripped when a
  snippet outputs markup.
- Setting `enabled` to `true` makes the snippet live after the next Pull & Import.
- Never enable a duplicate implementation of the same function, class, shortcode,
  action, or filter.
- Preserve `function_exists` and class guards where present.
- The validator rejects syntax errors, `eval`, includes/requires, process/shell
  execution, direct filesystem deletion/permission changes, meaningful `exit/die`,
  and destructive `DROP`/`TRUNCATE` SQL.
- A snippet that fails validation aborts the entire import before runtime records are
  changed. Runtime errors can cause Site Studio to disable the executing snippet.

## Global runtime files

### `runtime/global/styles.css`

Global CSS loaded across the Site Studio frontend. Keep design tokens centralized,
avoid unscoped emergency overrides, and check desktop/mobile behavior. Do not wrap it
in `<style>` tags.

### `runtime/global/scripts.js`

Global JavaScript loaded by Site Studio. Do not wrap it in `<script>` tags. Avoid
redeclarations, unguarded DOM assumptions, duplicate animation loops, console logging
in production, and code that breaks WordPress admin pages.

### `runtime/global/design-tokens.json`

Stores the global design system. Current groups are:

- `colors`: background, text, accent, muted, border;
- `typography`: body font, heading font, base size, line height;
- `layout`: content width, wide width, spacing unit, radius.

Preserve valid JSON and the existing group/key structure unless Site Studio itself is
updated to understand a new schema.

### `runtime/global/settings.json`

```json
{
  "external_stylesheet": "",
  "external_scripts": [],
  "bundled_lenis": false,
  "site_mode": false,
  "remove_emoji": false
}
```

- URLs must be valid public URLs.
- `bundled_lenis` controls Site Studio's bundled smooth-scroll assets.
- `site_mode` is a high-impact routing switch; do not change it casually.
- `remove_emoji` controls WordPress emoji cleanup.

## Plugin snapshot boundary

`plugin/igc-builder/` is exported so developers and AI agents can inspect the exact
installed implementation. It is **not deployed by Pull & Import**. Editing it in
GitHub alone cannot update the active WordPress plugin, and the next WordPress push
will replace it with another snapshot of the installed plugin.

To change Site Studio core:

1. edit and review the plugin source in a development checkout;
2. increment the plugin version;
3. run PHP syntax/static checks and package-integrity checks;
4. create a release ZIP containing the `igc-builder/` directory;
5. install the ZIP through WordPress and verify activation;
6. push the site again to refresh this snapshot.

Do not attempt to build a self-updater inside runtime code.

## Safe workflows

### Change code in GitHub or with an AI editor

1. In WordPress, click **Site Sync → Push Site → GitHub** so the repository starts
   from the latest database state.
2. Pull/fetch the configured Git branch locally.
3. Edit only the relevant runtime files.
4. Validate and review the diff; ensure no generated IDs or unrelated code changed.
5. Commit and push.
6. In WordPress, click **Site Sync → Pull GitHub → Site** and confirm.
7. Test the affected page plus shared header/footer and commerce flows.

### Change code in WordPress

1. Edit and save through Site Studio.
2. Test the site.
3. Click **Site Sync → Push Site → GitHub**. The plugin creates a timestamped commit
   message automatically.
4. Confirm that the GitHub commit contains only the intended changes.

### Resolve competing edits

- Stop and compare the WordPress version with the Git branch.
- Preserve both versions before choosing a winner.
- Pulling imports Git into WordPress; pushing exports WordPress into generated Git
  paths. Direction matters.
- Do not use force-push, rewrite shared history, or resolve conflicts by deleting
  entire runtime directories.

## Validation and deployment behavior

Before import, Site Studio:

1. downloads or fast-forwards the configured branch;
2. requires the `runtime/` structure and readable global files;
3. parses all `meta.json` and global JSON files;
4. syntax-checks and safety-scans every PHP snippet;
5. builds the full import plan before database writes;
6. exports a local runtime backup and keeps recent backups;
7. applies database changes in a transaction where supported;
8. clears Site Studio/WordPress caches after a successful import.

The import updates or creates listed records. It does not treat missing Git folders as
deletion instructions.

## Required testing checklist

For any meaningful runtime change, check what is relevant:

- homepage and assigned canvases at desktop, tablet, and mobile widths;
- header navigation, menus, links, focus states, and mobile navigation;
- footer and legal links;
- shop/product archives, category/collection filters, and pagination;
- single-product gallery, price, variation/stock states, and add-to-cart;
- mini cart quantity/remove behavior;
- cart totals and empty-cart state;
- checkout fields, shipping, payment selection, validation, and order submission;
- workshop shortcodes/booking flows;
- logged-in and logged-out behavior;
- browser console errors, PHP errors, accessibility basics, and reduced motion;
- cache-cleared output after deployment.

Never test real payments or destructive order actions without explicit authorization.

## Agent editing rules

An AI agent working in this repository must:

- inspect dependencies and call sites before changing a shortcode, hook, selector, or
  template contract;
- preserve the site's established visual language unless asked to redesign it;
- prefer existing design tokens and shared styles over new hard-coded values;
- keep CSS selectors scoped to the component/page where possible;
- keep JavaScript idempotent and guard repeated initialization;
- use WordPress escaping, sanitization, nonces, capabilities, and WooCommerce APIs in
  PHP changes;
- avoid external CDN dependencies unless explicitly approved;
- never change activation/routing flags as a side effect of an unrelated task;
- never assume that plugin snapshot edits deploy automatically;
- report exactly which files changed and which WordPress action is required to deploy.

If a request conflicts with this contract, stop and explain the risk before changing
the repository.

## Recovery

- Define `IGC_SAFE_MODE` as `true` in `wp-config.php` to bypass Site Studio PHP
  snippets and Site Mode during emergency recovery.
- Site Studio keeps local runtime backups before imports, but they are not a substitute
  for hosting/database backups.
- Reverting a Git commit is not live until **Pull GitHub → Site** is run again.
- If a snippet causes a failure, disable it from the PHP Snippets list or use Safe Mode.
- Plugin-core failures require reinstalling a known-good plugin ZIP; Pull & Import does
  not roll back plugin source.

## Definition of done

A change is complete only when:

- the diff is focused and contains no secrets or unrelated generated churn;
- edited files validate successfully;
- relevant frontend/admin/commerce flows were tested;
- the commit was pushed to the correct branch;
- WordPress Pull & Import completed successfully when deployment was requested;
- the live result and caches were verified.

