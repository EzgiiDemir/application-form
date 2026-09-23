# Technical decisions

## 2026-09-02 — Framework upgrade deferral

A framework version audit (`docs/framework-version-audit.md`) evaluated
Laravel 13, Filament 5 and Next.js 16 under September 2026 production
conditions. The frontend already runs Next.js 16; Laravel 12 and Filament 3
remain the supported baseline until after enrollment go-live stabilizes.
Upgrades must follow the documented order (PHP 8.3 → Laravel 13 → Filament
4 → Filament 5) on dedicated branches with the full PHPUnit suite green.
No automatic framework bumps are applied from the audit command.


The public application experience is a standalone Next.js 16 App Router app in
`frontend/`; Laravel owns the API, persistence, SSO and all three Filament
panels. This follows the roadmap's monorepo and WordPress-independence
requirements while keeping the public form separately deployable.

## 2026-09-02 — Drafts and public edit links

Every native submission receives a high-entropy, hashed edit token. The raw
token is only returned at creation time and is accepted in the query string for
the edit API. Tokens expire after 30 days by default; this is configurable via
`SUBMISSION_EDIT_TOKEN_TTL_DAYS`.

## 2026-09-02 — Scope enforcement

Form access for non-super-admin staff is modelled through `user_form_scopes`.
Authorization policies remain the single enforcement point, so Filament and
future internal APIs cannot accidentally bypass form scopes.

## 2026-09-02 — Apply directory and preview boundary

The public landing page follows the live Apply directory's four expandable
groups (TC, KKTC, competition and housing), but links are generated only from
published forms in our database. Admin previews use a short-lived Laravel
signed URL and render through Next.js with submission controls disabled, so a
draft can be reviewed without becoming public or producing a candidate record.

## 2026-09-02 — One institutional sign-in

ARUCAD's public site presents separate academic, student and personnel entry
points, but the Apply product uses one neutral Microsoft Entra sign-in screen.
Laravel derives the destination exclusively from the authenticated account's
server-side role, so login never reveals internal panel names or enables a
visitor to select a privilege.

## 2026-09-18 — Public site is data, not frontend code

Audiences, categories, form cards, navigation and announcements live in
`application_audiences`, `form_categories`, `form_publications`,
`public_navigation_items` and `public_announcements`, and are edited from the
admin panel under **Public site**. `/api/v1/public/catalog` returns the whole
homepage; Next.js renders whatever it receives. Adding an application section is
an admin task, never a frontend deploy — and it is what allows the English
homepage to legitimately expose a different application set from the Turkish one
(each card carries its own `locale` and `audience`).

## 2026-09-18 — Legacy URLs render in place wherever possible

`route_aliases` maps every historic WordPress path. A `redirect_type` of `200`
means "render this card at this URL", so the old link keeps working with no
redirect hop at all; only a path that genuinely moved costs a `301`. Renaming a
publication's `public_path` creates that `301` automatically, so a live URL can
never be silently broken from the admin UI. Redirects are served by Next.js
middleware from a cached map (`/api/v1/public/redirects`) so the visitor gets the
exact recorded status code rather than a rendered page that redirects afterwards.

## 2026-09-18 — Field identity is the uuid, scoped per version

`form_fields.uuid` is unique per `form_version_id`, not globally, and
`FormVersionService::cloneVersion()` carries it into the next version. A field
renamed from "TC Kimlik No" to "Kimlik / Pasaport No" in version 4 is still the
same field as in version 3, so historic answers stay attributable and reports
stay comparable across versions. A duplicated field or a duplicated form gets a
new uuid, because a copy is a different thing.

## 2026-09-18 — Answers are stored once and projected for reporting

`submission_values.value_json` remains authoritative. `text_value`,
`numeric_value`, `date_value` and `boolean_value` are a projection maintained by
`AnswerProjectionService` through a model `saving` hook, so every write path —
public submit, autosave, staff edit, WPForms import — keeps them in step.
Encrypted answers are deliberately not projected: the ciphertext is not
searchable and the plaintext must not be duplicated into an unencrypted column.
`php artisan answers:reproject` rebuilds the projection after a backfill.

## 2026-09-18 — Availability is computed, never stored twice

`FormAvailabilityService` derives the public state (open / scheduled / closed /
unpublished) from the editorial status plus the availability window, for both
forms and publications. It is enforced in the public form endpoint *and* in
`FormSubmissionService`, so a closed form cannot be started or submitted by
posting straight to the API. A publication can narrow its form's window but
never widen it.

## 2026-09-18 — Two-axis application status for the outside world

The internal pipeline (`application_stage` + `decision_status`) stays richer than
the applicant-facing model, because document review and jury assignment are real
admissions steps. `ApplicationStatusView` projects it onto the lifecycle /
review pair the API and the portal speak, so an applicant is told "In review",
never "jury_review".

## 2026-09-18 — Session-authenticated JSON API for the Next.js panels

`/api/v1` routes under the `api.session` middleware group authenticate with the
ordinary Laravel session cookie, so the builder and the student portal need no
separate token store. CSRF tokens are replaced by two guarantees: the routes
accept `application/json` only (which forces a CORS preflight), and the allowed
origins are an explicit allowlist in `config/cors.php`.

## 2026-09-21 — Exports run on the queue and expire

`export_requests` existed as a table with no code behind it. Exports are now
generated by `GenerateExportJob` through `ApplicationExportService`, streamed to
a private disk in chunks so memory does not grow with the row count, and
collected from a download inbox under **Reporting → Exports**. Two rules hold:
an export is always scoped from the *requesting user*, never from the filters,
so it cannot widen what that member of staff may see; and the file expires
(default seven days, `exports:purge-expired`) because it is a copy of personal
data sitting on disk. Sensitive fields export as `[hidden]`.

## 2026-09-21 — One scheduler entry drives every automation

`automation_rules` stores trigger + conditions + actions, and a single
`automations:run` scheduler entry executes every time-based rule. Event-based
rules are dispatched from `ApplicationEventRecorder`, so "the application was
submitted" has exactly one definition shared by the timeline, the portal and the
automations. `automation_targets` records that a rule has acted on an
application, which is what stops a daily reminder emailing the same applicant
every day; a rule must opt into `repeatable` to act twice. Sending mail goes
through the existing notification engine rather than a parallel path, so
recipients, templates, conditional routing, delivery logs and retries keep
working.

## 2026-09-21 — WPForms field ids come from the field, not the array position

The importer read the WPForms field id from the array key. A list-shaped export
— or one that has been through a JSON round trip — loses those keys, and every
answer was then filed against the wrong field, silently. The field's own `id`
now wins, with the array key as fallback. Candidate contact details are likewise
resolved by field *type* rather than by machine name, because a legacy form
labels its email field whatever it likes; without that, an imported applicant
had no address and could not be sent a resume link or matched to their account.

## 2026-09-21 — A migration is not done until the files are copied and counted

Importing an attachment's URL is not a migration: when WordPress goes away, an
application that references a passport scan by URL has lost the passport scan.
`LegacyFileMigrationService` fetches each binary (from a mounted uploads tree,
or over HTTP while the old site is still live), checksums it, sniffs its type
from the bytes rather than trusting the filename, and stores it privately.
Failures are named rows, not a number in a summary.
`MigrationReconciliationService` compares the legacy entry counts against what
landed here and reports a migration as **incomplete** while any attachment is
unresolved — and reports "unverified" rather than "reconciled" when the legacy
database cannot be reached, because those are different answers.

## 2026-09-21 — Cutover never silently invalidates an applicant

`CutoverService` generates the legacy URL aliases and reissues resume links for
every imported draft that is still unfinished, because old WordPress resume
links cannot survive the move. Both actions preview before they act, and the
preview lists the exact recipients — including those with no email on file, who
need manual follow-up. `migration:cutover checklist` answers the go/no-go
question from the database and exits non-zero while anything is outstanding.

## 2026-09-21 — All JSON columns are `jsonb`

PostgreSQL's `json` type has no equality operator, so any query applying
DISTINCT to a table holding one fails outright — which is what broke the coupon
form's relationship picker (`select distinct "forms".*`). All 62 remaining
`json` columns were converted to `jsonb`, which also supports the GIN indexes
reporting relies on and is what the platform brief specifies. The migration is a
no-op on SQLite, where both map to TEXT.

## 2026-09-21 — Panel screens are smoke-tested, not just clicked

A Filament screen fails at render time, so a broken page is otherwise only found
when someone opens it. `PanelSmokeTest` walks every registered parameterless
route in all three panels and asserts it renders, and separately asserts that any
page naming a form method something other than `form` registers it in
`getForms()` — the omission that made `/admin/reports-dashboard` call
`filterForm()` with an `Infolist`. Both checks were confirmed against the real
bug before being kept.

## 2026-09-21 — Funnel analytics count sessions, not events

`FormFunnelService` counts distinct `session_id` values, so a visitor who
reloads a page five times is one person who reached it. Counting raw events
would inflate every rate on the screen. Completion and abandonment are measured
against people who *started*, not against everyone who glanced at the page, and
a rate with nothing to divide by is reported as "—" rather than as 0%.

## 2026-09-21 — "Abandoned" is a real stage, and lifecycle buckets are exhaustive

`forms:mark-abandoned` set `application_stage = 'abandoned'`, a value that did
not exist in `ApplicationStage`. The consequences were silent: the accessor
mapped it to *submitted* (wrong — it was never submitted) while every filter and
report excluded it entirely, so those applications vanished from the admin
table, the portal, exports and reports. `ABANDONED` is now a declared stage that
maps to the **draft** lifecycle, and lifecycle filtering goes through
`ApplicationStatusView::applyLifecycleFilter()`, which expresses "submitted" as
the complement of the non-submitted stages. An application carrying a stage this
code has not seen before now shows up as submitted instead of disappearing.

## 2026-09-21 — Reports are declared, not hand-written

`ReportBuilderService::dimensions()` declares every group-by dimension once, so
the picker, the query and the CSV export cannot disagree about what is
available. Dimensions backed by an answer read the typed projection and exclude
fields marked sensitive: a report is not a way around field-level privacy.

## 2026-09-21 — The applicant portal is Next.js only

There were two student portals: a Filament panel at `/student` on the backend
and the Next.js portal at `/student` on the public site. Both worked, and they
had already diverged — the Next.js one carries documents, notifications,
profile, settings and the application timeline; the panel did not. The brief
asks for Next.js to own the student-facing UX, and it is the one on the domain
applicants are already on.

`StudentPanelProvider` is no longer registered, `preferredPanelPath()` sends a
student to `config('app.student_portal_url')`, and the backend `/student` routes
redirect there so any bookmarked or emailed link keeps working. The classes
under `app/Filament/Student/` are now unreferenced and should be deleted once
the repository has its first commit to delete them from.

## 2026-09-21 — One env var cannot be both a CORS allowlist and a public URL

`FRONTEND_URL` holds a comma-separated list of allowed origins, and eight call
sites were building applicant-facing links from it — so every Save & Resume
link, every `{{edit_link}}` in an email and every "public link" button produced
a URL like `http://a,http://b,http://c/resume/TOKEN`. `config('app.frontend_url')`
now resolves to a single canonical origin (`PUBLIC_URL`, or the first entry of
`FRONTEND_URL`), while `config/cors.php` keeps reading the raw list from the
environment. `config/app.php` also had `frontend_url` declared twice.

## 2026-09-21 — The field catalog has three copies, and they are tested against each other

A field type is declared in `config/field_library.php`, rendered by a branch in
`FormRenderer.tsx`, and typed in `types.ts`. Nothing structural keeps those in
step, and they had already drifted: five types were offered in the builder that
the public form could not render — a Portfolio field gave the applicant a text
box instead of a file picker, so they could not submit their work at all.
`FieldCatalogContractTest` reads the actual frontend sources and fails when the
palette offers something the runtime cannot render, when an upload type is not
handled by the file-input branch, or when the TypeScript union is missing a
type.

## 2026-09-21 — The frontend has tests

Everything applicants and staff actually touch — the form runtime, the builder,
the portal — had no automated cover; all tests were backend. Vitest with
Testing Library now covers the field-by-field output of the public runtime, the
builder's canvas operations and click-to-add flow, the portal's signed-out and
error states, and the API client's handling of 401s and 404s. `npm test` runs in
CI alongside lint and build.

Pointer-driven drag-and-drop is still not covered: jsdom has no layout engine.
Click-to-add is exercised instead, and a browser-level test remains the gap.

## 2026-09-21 — Browser tests, because some failures only exist in a browser

Playwright covers what neither PHPUnit nor jsdom can: CORS, which only the
browser enforces, and pointer-driven drag-and-drop, which needs a layout
engine. The suite runs against a production build of the frontend and a live
API, and covers the applicant journey end to end, the builder including real
drags, and the legacy URL behaviour.

Writing it immediately found that **no applicant could submit any form**: the
public runtime sends its signed session token as an `X-Form-Token` header,
that header was not in `cors.allowed_headers`, and the browser therefore
stripped it — so every save and submit failed with "the form session has
expired". Server-side tests passed throughout, because CORS is not enforced
server-side. `PublicAccessConfigurationTest` now guards the header list, the
preflight response and the canonical URL.

## 2026-09-21 — Reading the public site is not rationed like submitting to it

The catalog, form definitions and path resolution shared the 30-per-minute
budget meant for submissions. Around thirty homepage views a minute from one IP
— one school computer lab, or any campus behind NAT — returned 429 to everyone
on that address. Reads now have their own `public-browse` limiter (240/min by
default) and writes keep the tighter `public-apply` budget. Every limit is
configurable, because a university knows when its peak day is and should be
able to raise them beforehand rather than discovering them in support tickets.

## 2026-09-21 — The English homepage was empty

Every seeded publication was `locale: 'tr'`, and the catalog filters by locale,
so the English homepage rendered its categories with no applications in any of
them. The brief expects the two homepages to diverge eventually, but an empty
one is worse than a shared list: publications are now seeded as `all` and staff
narrow a card to one locale, or add a locale-specific card, when the sets
genuinely differ.

## 2026-09-21 — The public site is a portal, not a list of links

The old apply.arucad.edu.tr is a bulleted list of form links. The same
information architecture is kept — the four audience-led categories and the
same fifteen forms — but presented so an applicant can scan it: a hero with
live counts, audience tabs, a search box, and a card per application showing
whether it is open, when it closes, and how many days are left.

A card only shows a metadata row when there is something to say. An earlier
version printed "Status: no deadline" on all seventeen cards, which is noise
dressed as information; the same reasoning removes the "next deadline" hero
stat when nothing has a deadline.

## 2026-09-21 — The form page showed everything except which form it was

Every element of the application form's own header — the eyebrow, the title,
the description and the step count — carried `sr-only`, so a sighted applicant
saw a bare set of fields with no indication of which form they had opened. That
matters most for someone returning days later from a resume link. The header is
now visible, with a link back to the full list.

## 2026-09-21 — The dev server port is pinned

`npm run dev` took whatever port was free, so the documented URL and the CORS
allowlist could disagree with reality; two stale dev servers were also still
serving pre-change code on 3000 and 3100. Both `dev` and `start` now pin
127.0.0.1:3000, which is what `PUBLIC_URL` and the CORS allowlist name.

## 2026-09-21 — One calm accent, red reserved for attention

The directory carries one button per application, and fifteen saturated brand-red
buttons competed with each other, tired the eye, and left nothing louder for a
real warning. The interactive accent — `--action`, used by every button, focus
ring and active step across the public site, the form runtime and the portal —
is now a deep navy (`#1f3a5f`). The category rules keep their colour coding but
in muted tones, and the header's TC/KKTC shortcuts are quiet outlined links with
a coloured hairline rather than solid red and yellow fills.

Red is kept for two things: the ARUCAD mark, and the "N days left" warning on a
closing application — which now reads as the only alert on the page, because it
is.

Note for future edits: `site.css` is `@import`ed at the top of `globals.css`, so
globals' own declarations come *later* in the cascade and win at equal
specificity. Overriding a rule defined in `globals.css` from `site.css` needs
higher specificity, not just a later line.

## 2026-09-21 — The real brand mark replaces the redrawn one

`ArucadLogo` was an inline SVG recreation of the mark. It is now the
university's own artwork (`public/images/arucad-mark.png`, present in both the
frontend and the backend public directories), with the ARUCAD wordmark set in
type beside it because the supplied file is the mark alone. One custom property
(`--mark`) sizes each context, so the mark and the wordmark stay in proportion
in the header, the footers, the login screen and the not-found page. The three
Filament panels point at the same file, and the old `arucad-logo.svg` is gone.

## 2026-09-21 — The application form has a working layout

Filling in a form is the single most important thing this platform does, and it
was the weakest screen: a stretched strip of step labels, a hidden progress bar
(`globals.css` carried a literal `.progress { display: none }`), and the form
itself stranded in the middle of an empty page.

It is now two columns. A sticky sidebar carries the progress bar with the step
and percentage, a numbered step list marking done and current, the draft
reference and the autosave state — all the things an applicant wants to glance
at while typing. The form takes the remaining width with a responsive field
grid. Under 900px the sidebar becomes a compact progress header above the form
and the step list collapses to numbered pips.

## 2026-09-21 — The navbar is full-bleed and pinned

The header read as a panel floating inside the page rather than as a navbar: it
carried 70px of side padding and a heavy black top rule, and on the form page
`.apply-shell > header` boxed it to 840px along with the page content. It is now
sticky at the top, full viewport width with a small gutter, translucent with a
backdrop blur so content passing beneath stays legible.

`.category` keeps `scroll-margin-top`, so the in-page category links still land
below the pinned bar rather than behind it.
