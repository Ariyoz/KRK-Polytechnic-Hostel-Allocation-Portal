# Component classes — app.css

Load order: `app.css` (shared, do not edit per-page) then your own
`assets/css/yourpage.css` via `$pageCss = ['yourpage.css'];` before
`include '../includes/header.php';`. Never inline `style=""` for things a
class already does; add a modifier class or a small rule in your own file.

| Need | Classes |
|---|---|
| Button | `.btn` + `.btn-primary` (amber) / `.btn-dark` (navy) / `.btn-outline` / `.btn-ghost`, `.btn-sm`, `.btn-block`, `[disabled]` |
| Form field | `.field > .label (.req) + .input/.select/.textarea + .hint/.error` |
| Field grid | `.field-row` (2 cols), `.field-row.cols-3` |
| Checkbox/radio | `.check` wrapping the input + label text |
| Password field | `.pwd-field > .input + .pwd-toggle button`, JS: `togglePwd('id', this)` |
| Fieldset card | `.field-group > <legend> + fields` (e.g. "Personal details") |
| Alert | `.alert` + `.alert-info/-success/-warning/-danger` |
| Badge | `.badge` + `.badge-neutral/-info/-success/-warning/-danger` (dot included) |
| Card | `.card`, `.card-head` (title + `.card-action` link) |
| Overview tile | `.step-card` (+ `.is-done`/`.is-current`/`.is-locked`), `.step-card-icon`, `.step-card-tag` |
| Progress bar | `.progress > .progress-bar(.is-warning/.is-danger)`, `.progress-marker` with inline `left:%` (data, not style) and `data-label` |
| Horizontal stepper | `.steps-row > .steps-row-item(.is-done/.is-current) > .dot` |
| Vertical timeline | `.timeline > .timeline-item(.is-done/.is-current) > .timeline-dot` |
| Table | `.table-wrap > table.table` |
| Label/value row | `.kv > .kv-row > .kv-label + .kv-value` |
| Slip-style pairs | `.kv-grid > .kv-grid-cell > .kv-label + .kv-value` |
| Bed squares | `.beds > .bed(.bed-free/.bed-taken/.bed-you)` |
| Empty state | `.empty > .empty-icon + .empty-title + p` |
| Page header | `.page-head > .eyebrow + h1 + p.sub`, `.page-head-row` to add actions |
| Public header/footer | `.site-header`, `.site-nav`, `.nav-list`; `.site-footer`, `.footer-row`, `.footer-links` |
| Auth split (login/register) | `.auth-split > .auth-split-panel` (navy) + `.auth-split-main > .auth-split-card` |
| Portal shell | `.portal > .sidebar + .portal-main > .portal-topbar + .portal-content` |
| Sidebar nav item | `.sidebar-nav a(.active) > svg.s-icon + text + .badge` |
| Topbar user | `.portal-topbar-right > .icon-btn (bell) + .portal-user > .avatar + .portal-user-text` |
| Sidebar mobile toggle | any element with `[data-sidebar-toggle]`; add a `.portal-overlay` div |

Icons: inline SVG only (`<svg class="icon"><use href="#icon-x"/></svg>`,
sprite is in header.php). Font Awesome is still loaded only because old
pages hardcode `fa-*` classes — don't add new FA icons, add a `<symbol>`
to the sprite instead.

## Anti-slop rules (non-negotiable)
No gradient blobs/orbs/glassmorphism/backdrop-blur. No floating cards or
scroll-cue arrows. No icon-in-tinted-square decorating every heading or
card. No uniform 3/4-up feature grids just to fill space. No hover
lift/scale/tilt animations — only 150ms color/border transitions. No
gradient text, no emoji, no marketing filler ("seamless", "everything in
one place", "fully digital", "no more queues"). No made-up/hardcoded
stats. No `═══` banner comments. No `style=""` for anything a class can
do (dynamic widths/positions driven by PHP data are the one exception).
Copy stays plain and specific to KRK Polytechnic — a Nigerian polytechnic
hostel office, not a SaaS startup.
