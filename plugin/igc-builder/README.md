# Site Studio

Version 1.1.1 adds an automatic private temporary-workspace fallback for managed hosts that cannot write above the web root. Version 1.1.0 added the GitHub API mode, while Version 1.0.0 introduced the guarded file/Git workspace.

## Workflow

1. Define colors, typography, layout tokens and optional external CSS in **Site Studio → Global Styles**.
2. Add shared external libraries and footer JavaScript in **Site Studio → Global Scripts**.
3. Use **Visual Builder** to insert widgets, Studio blocks and WordPress/WooCommerce shortcodes, or import a complete HTML document from an AI editor.
4. Preview the canvas with real WordPress shortcode rendering at desktop, tablet and mobile widths while editing HTML, CSS and JavaScript side by side.
5. Assign the canvas to an existing WordPress page and use the admin-only real-page preview. Publish and enable its route only after verification. Alternatively place it with `[studio_canvas id="page-slug"]` in a page or Theme Template.
6. Create reusable HTML/CSS/JavaScript components in **Reusable Blocks** and place them with `[igc_block id="slug-or-id"]`.
7. Build header, footer, page, post, archive, search, WooCommerce and 404 layouts in **Theme Templates**.
8. Add small WordPress customisations through guarded **PHP Snippets**.
9. After templates have been tested, optionally enable **Site Mode** so matching Site Studio templates replace the active theme's page shell.

For an Elementor transition, open **Site Studio → Migration Pack** while Elementor is still active. The importer skips pages that already have an assigned canvas, creates only drafts, and never enables their routes.

## Git Workspace

Open **Site Studio → Git Workspace** to export:

- Visual Page HTML, CSS, JavaScript and routing metadata
- reusable Code Blocks
- Theme Templates and their conditions
- guarded PHP Snippets and activation scope
- Global CSS, Global JavaScript, design tokens and runtime settings
- a reviewable snapshot of the installed Site Studio plugin source

Runtime files can be edited locally, committed and pushed. **Pull & Import** uses
`git pull --ff-only`, validates every workspace file and PHP snippet, creates a local
runtime backup, and only then updates WordPress. Products, orders, customers, bookings,
pages and normal WordPress content are never exported.

When server Git exists, configure an SSH deploy key or Git credential helper and connect
an empty private repository. On managed hosting without Git, connect a private GitHub
repository initialized with a README and use a fine-grained token restricted to that
repository with Contents read/write access. The token can be defined as
`IGC_GITHUB_TOKEN` in `wp-config.php`; when entered through the dashboard it is encrypted
with the WordPress security keys using Sodium. The default workspace is outside the
WordPress web root; define `IGC_GIT_WORKSPACE_DIR` if the hosting layout requires another
writable path. Plugin source is exported for review and release packaging but is
deliberately not self-deployed.

## Global CSS tokens

The visual settings are output as CSS custom properties:

- `--studio-color-background`, `--studio-color-text`, `--studio-color-accent`, `--studio-color-muted`, `--studio-color-border`
- `--studio-font-body`, `--studio-font-heading`, `--studio-font-size`, `--studio-line-height`
- `--studio-content-width`, `--studio-wide-width`, `--studio-space`, `--studio-radius`

The Global Styles screen also accepts a complete custom stylesheet and an optional public stylesheet URL.

## Safety

- Site Mode is disabled by default.
- Page routes are disabled by default, require a published canvas, and only one active canvas may target a page.
- The real-page preview is available only to an administrator and does not alter the public page.
- PHP snippets are disabled when created. Saving runs PHP's parser plus a conservative scan that blocks shell execution, direct filesystem deletion, includes, eval/exit and destructive DROP/TRUNCATE SQL.
- Invalid PHP is neither saved nor activated; the last known version remains available.
- Activated snippets automatically switch off after a caught runtime error or detected fatal shutdown.
- HTML preview JavaScript is off by default and, when enabled, runs inside a sandboxed iframe without same-origin access.
- WordPress previews require an authenticated administrator nonce, accept only in-memory draft code and never publish or activate it.
- Canvas and snippet code fields participate in WordPress revisions.
- `define( 'IGC_SAFE_MODE', true );` in `wp-config.php` bypasses both PHP snippets and Site Mode.
- During an Elementor migration, never enable a copied PHP snippet while the matching Code Snippets version remains active.

Do not cut over a production site until its templates and commerce flows have passed staging regression tests.
