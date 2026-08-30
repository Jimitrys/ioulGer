# Workshop booking popup — design QA

- Source visual truth: `/Users/dimitrysantoniou/.codex/generated_images/01a04a95-d86a-74f0-b4df-d82175c8d8c6/exec-ef195d46-63ec-452b-8d62-f6103167af13.png`
- Implementation screenshot: `/tmp/iwf-harness/implementation-mobile-step1-final.png`
- Full-view comparison: `/tmp/iwf-harness/qa-step1-composite-final.png`
- Supporting states: `/tmp/iwf-harness/implementation-mobile-calendar.png`, `/tmp/iwf-harness/implementation-mobile-form.png`
- Viewport: 390 × 844 CSS px, device scale factor 1
- Source pixels: 853 × 1844, normalized to 390 × 844 for comparison
- Implementation pixels: 390 × 844
- State: Greek mobile booking sheet, step 1 selected; calendar and form checked separately

## Full-view comparison evidence

The selected workshop, price-card hierarchy, Popular badge/check grouping, progress, close control, scrollable list, and persistent primary action match the approved direction. The implementation intentionally retains the live site's visible navigation inset above the bottom sheet rather than treating the popup as a standalone app screen.

## Focused region evidence

- Price-card header: the Popular badge and selected check are grouped with a fixed 0.45rem gap.
- Calendar: available dates remain neutral, selection becomes charcoal, navigation controls use the shared control sizing, and the time selector appears in a contained surface.
- Form: booking summary, participant stepper, fields, textarea, consent, focus treatment, and errors use the same radius, border, spacing, and neutral palette system.
- Popup body measured 582px client height against 808px scroll height in the long form state; internal scrolling reached 226px while the footer action remained fixed.

## Required fidelity surfaces

- Fonts and typography: existing Montserrat site font preserved; hierarchy, weights, line height, and Greek wrapping remain readable at mobile and desktop widths.
- Spacing and layout rhythm: 24px-style gutters, fixed-height responsive shell, stable header/footer, and internally scrolling body prevent step-to-step shell jumps.
- Colors and visual tokens: warm paper, charcoal ink, neutral borders, and neutral availability states; no red warning-like accents.
- Image quality and assets: no new raster imagery is required by this UI; existing source icons are preserved.
- Copy and content: workshop names, prices, descriptions, Greek labels, booking fields, and workflow semantics are unchanged.

## Comparison history

1. P2 — the first workshop title wrapped and the card stack was denser than the approved visual at 390px. Fixed by grouping badge/check, tightening option padding/gaps, and using a 16px option title. The final comparison shows the first title on one line and improved list density.
2. P2 — the original close state disappeared without a visible exit motion. Fixed with explicit closing state animations and a fail-safe hide timer; open, close, directional step transitions, month movement, time reveal, and drag release now use shared motion curves.

## Interaction and console checks

- Opened step 1, advanced to calendar, selected a date and time, advanced to form, returned to calendar, and confirmed selected ARIA states.
- Confirmed internal scroll with the CTA/footer fixed.
- Browser console warnings/errors: none.
- PHP execution was not available locally; JavaScript syntax, CSS/PHP brace balance, and Git whitespace checks passed.

## Findings

No actionable P0/P1/P2 findings remain. The navigation inset is an intentional live-site constraint, not design drift.

final result: passed
