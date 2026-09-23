# ARUCAD Apply feature coverage

This matrix is the code-backed audit of the 70-item product brief. “Implemented”
means a persisted domain model plus an executable application path exists;
“external” means production completion depends on infrastructure configuration.

## Implemented in the application

- Independent application platform and application-centric data model (1–2)
- Expanded form builder field catalogue, reusable field library, immutable form
  templates and form versioning (3–6)
- Application lifecycle, autosave, guest resume tokens and student portal (7–9,
  59)
- Optional six-digit email verification and incomplete-draft reminder schedules
  with cancellation, expiry, retries and a Jobs screen (10–13)
- Conditional notification rules, event listeners, multilingual WYSIWYG email
  templates, delivery logs and retry (14–19)
- Message/redirect/custom-page confirmations and AND/OR conditional form rules
  with show/hide/set-value behavior (20–21)
- Server-side validation for required, email, URL, length, numeric range, regex,
  date range and Turkish identity numbers (22)
- Multi-page flow, completion percentage, review-before-submit and configurable
  post-submit edit/correction policies (23–26)
- Missing-document requests, private file delivery, MIME/size validation,
  malware-scan state, SHA-256 hashes and document version chains (27–29)
- Application PDF generation, selectable PDF templates and configurable
  application-number patterns (30–32)
- Application periods, scheduled/open/closed state, informational closed pages
  and separate draft grace deadlines (33–36)
- Application list, configurable columns, filters, saved-view persistence,
  searchable identity columns and bulk assign/tag/export/PDF/archive/email actions
  (37–44)
- Immutable audit trail, application revisions, internal/applicant-visible notes,
  individual/team assignment, expanded RBAC and field-level privacy/encryption
  (45–50)
- Dashboard/reporting dimensions, analytics event capture for funnel and field
  drop-off analysis, CSV and PDF output foundations and queued export records
  (51–56)
- Duplicate-match records without automatic deletion, tags and applicant portal
  (57–59)
- Rate limiting, secure headers/cookies, password hashing, upload controls,
  malware-scan integration point, sensitive-field encryption, configurable
  retention records, anonymization and soft-delete/trash/restore (62–65)
- API-first public form/submission/upload/privacy/analytics endpoints, signed
  webhooks with delivery retries, and idempotent WPForms migration with legacy
  IDs, reports and incremental re-import (66–70)

## Production/infrastructure completion

- **Magic-link login (60):** the public edit-link is already a scoped, expiring
  magic link. Account-level passwordless login still requires deciding whether
  Microsoft Entra External ID or an application-issued login link owns identity.
- **MFA (61):** staff/student authentication is Microsoft Entra ID. MFA must be
  enforced with Entra Conditional Access; duplicating a weaker application MFA
  layer is intentionally avoided.
- **Delivered/bounced/complained email states (19):** queued/sent/failed are
  native. The additional states require the selected email provider’s signed
  webhook contract.
- **Malware verdicts (62):** quarantine/status/job hooks exist; production needs
  the selected ClamAV or object-storage scanning service.
- **Binary WPForms file copy (68):** legacy URLs are retained as `reference_only`.
  The final copy needs the real export directory or read-only legacy endpoint.

## Remaining product-depth work

The brief describes a mature product, not only database capabilities. These
items have foundations but should not yet be represented as production-complete:

- Saved views are persisted, but the personal “My Views” picker/editor still
  needs its dedicated Filament page.
- Funnel and field drop-off events are collected; dedicated charts and the
  free-form group-by report-builder UI are still to be added.
- CSV export and queued export records exist. XLSX generation and a completed
  background-export download inbox are not yet wired end-to-end.
- Bulk email supports schedule and test-send, but the modal should show the
  exact expanded recipient list before the operator confirms.
- Team, custom-status and retention-policy tables exist; their full CRUD screens
  and policy-specific authorization rules remain to be exposed.
- The public API is complete for application entry. A separately authenticated,
  versioned integration API for ERP/SIS consumers still needs an API credential
  policy before it can be safely exposed.

## WPForms roadmap Phase 1 completion (2026-09-18)

- Added schema cache/export, extensible field registry, form tags, views, embed state, shortcode and duplicate/export/entries actions.
- Added a dedicated Entries module with search/filter/sort, read/star/spam state, notes, metadata and JSON export.
- Added signed form-session tokens, honeypot and timing checks, request metadata capture and granular Entries permissions.
- Added missing Phase 1 field types and reusable field settings, with matching frontend rendering.

## WPForms roadmap Phase 2 completion (2026-09-18)

- Added reusable Smart Tags for forms, entries, users, URLs, IP data, all fields and individual field values.
- Added password/date/login/guest/role/submission-limit/IP/email-domain form locker rules.
- Added configurable abandonment capture, scheduled detection, and exclusion of passwords, signatures and uploads from partial storage.
- Added conversational and lead display modes, landing-page configuration, responsive theme variables and an offline retry queue with sensitive-field exclusion.
- Existing multi-page validation, progress, save/resume, conditional notifications and confirmations remain integrated with the same schema.
- Completed grouped AND/OR conditions with show, hide, enable, disable, require, skip and set-value actions, plus a public password-unlock flow.

## WPForms roadmap Phase 3 completion (2026-09-18)

- Added normalized payment, subscription, refund, coupon and quiz-result storage with admin payment/refund and coupon screens.
- Added a gateway contract and registry with adapter slots for Stripe, PayPal, Square, Authorize.Net and Mercado Pago; live transactions remain disabled until credentials and provider SDK adapters are configured.
- Added a safe calculation parser supporting arithmetic, parentheses and the approved math functions without executing PHP code.
- Added payment, coupon, survey, NPS, ranking and quiz field types, server-side calculated values, graded/weighted/personality quiz scoring and survey aggregation services.
- Added persisted per-entry/per-user coupon redemption, recurring subscription lifecycle and cancellation, drag ranking, quiz-result administration, and visual survey/quiz distribution reports.

## WPForms roadmap Phase 4 implementation (2026-09-18)

- Expanded the admin dashboard with forms, entries, views, conversion, spam, payments, sales, net revenue, coupon use and refunds; added entries/views, top forms, top locations, recent entries and recent payments widgets.
- Added daily field analytics for views, focus, changes, validation errors, abandonment, completions and average completion time, plus an admin Field Analytics screen.
- Added session-linked user journeys and consent-gated geolocation with configurable city-only or coordinate precision; events and journeys attach to the final entry.
- Added a mouse/touch digital signature pad. Signatures are excluded from drafts/offline queues and encrypted at rest on final submission.
- Added configuration-driven user registration and post/content submission workflows (title, body, excerpt, image, category, tags, custom fields and status) without allowing public forms to overwrite existing accounts.
- Added rate-limited frontend forgot/reset-password flows for explicitly enabled local accounts; Entra production accounts continue to use Microsoft identity recovery.
- Added automatic entry PDF generation from a selected template as a submission workflow, alongside the existing manual PDF tools.
- Added prepared integration handoffs and mappings for Google Sheets/Drive/Calendar, Dropbox, HubSpot, Salesforce, Pipedrive, Zoho and the requested email-marketing providers. Until credentials and each live API adapter are installed, attempts are explicitly recorded as `needs_config` rather than silently claiming success.
- Existing PDF templates/downloads, public login routing, Microsoft Entra authentication and signed webhooks remain connected to the same Laravel/PostgreSQL submission records.

## No-code visual form builder completion (2026-09-18)

- Replaced the technical nested key/value editor with a responsive three-panel builder: a one-click/drag field library, live form canvas and contextual settings sidebar.
- Added visual field selection, automatic saving, drag and button reordering, duplication, deletion, sections/pages and full/half/third-width layout controls. Internal field names and schema updates are generated automatically.
- Added teacher-ready Student Information, Multiple Choice Question, Written Exam Question, Consent Section and File Assignment blocks.
- Added visual options, correct-answer, points, required, description, upload constraints and multi-condition AND/OR logic controls without exposing field identifiers or rule configuration.
- Added a 13-item template gallery covering contact, registration, exams, quizzes, surveys, assignments, feedback, events, applications, attendance and consent.
- Added visual publishing for new pages, existing pages and standalone forms, plus preview and one-click public-link copying. Published page records are served through `/p/{slug}`.
- Removed schema, key/value, shortcode and internal-key controls from the ordinary form workflow; technical template and field-definition resources are no longer shown in navigation.

## Verification

- Backend feature suite includes dedicated visual-builder acceptance tests in addition to the platform regression suite.
- Dedicated platform-capability tests cover templates, periods, advanced rules,
  email OTP, conditional confirmation, analytics, webhooks, reminders and PDFs.
- Frontend ESLint and Next.js production build pass.

## Public site, visual builder and applicant portal (2026-09-18)

Implemented against the platform brief:

- **Public site CMS (§3–5, §22):** audiences, categories, form cards,
  navigation and announcements are database-driven and edited under
  **Public site** in `/admin`. Each card carries its own locale, audience,
  visibility, position and availability override, so the Turkish and English
  homepages can expose different application sets.
- **Legacy URL compatibility (§6):** `route_aliases` renders preserved
  WordPress paths in place (no redirect) and issues a `301` only where the
  canonical path changed. Renaming a card's public URL creates the alias
  automatically. Next.js middleware serves redirects from a cached map so the
  recorded status code is the one the visitor gets; `?lang=en` survives the move.
- **Field library and blocks (§8–9):** `config/field_library.php` is the single
  catalog behind the palette, the validator and the WPForms importer.
  Eight reusable ARUCAD blocks (identity, contact, education, programme
  preferences, registration documents, competition, travel, consents) ship
  seeded and are editable at `/admin/form-blocks`.
- **Visual builder (§7, §11–12, §64):** `/builder/forms/{uuid}` provides the
  palette / canvas / settings layout with drag-and-drop *and* click-to-add,
  page management, a visual conditional-logic editor and autosave. No JSON,
  schema or field id is ever shown.
- **Versioning (§13):** reading the canvas of a published form opens a new draft;
  the published version is never edited in place. Field uuids carry across
  versions so historic answers stay attributable.
- **Availability and publication status (§14–15):** always-open, date-range and
  academic-period modes, with `close at end of day`, computed into
  open / scheduled / closed and enforced at submit time.
- **Save & Resume (§16–17):** resume validity is per form (`draft_expiry_days`),
  tokens are stored hashed with expiry, revocation and last-used tracking, and
  `/resume/{token}` exchanges a token for its application without exposing ids.
- **Application status model (§19) and timeline (§46):** lifecycle and review
  status are projected from the internal pipeline by `ApplicationStatusView`;
  `application_events` is an append-only timeline shown in the admin entry
  screen, the trainer review screen and the applicant portal.
- **Applicant portal (§20):** `/student` provides dashboard, applications,
  application detail with answers/documents/timeline, documents, notifications,
  profile and settings, scoped to the signed-in user and to guest applications
  linked by verified email.
- **Trainer panel (§27–28):** `/trainer` has a review queue with
  Assigned to me / Needs review / Needs information / Completed reviews, scoped
  by `applications.view_assigned` versus `applications.view_all`, with
  permission-gated start-review, request-information, approve and reject actions.
- **Permissions (§29):** granular `applications.*`, `communications.*`,
  `public_site.manage`, `blocks.manage`, `migration.manage` and
  `settings.manage` permissions replace role-name checks in the new code paths.
- **Answer storage (§42, §44, §45):** `form_fields` carries a per-version uuid
  and a reporting key; `submission_values` projects into typed columns with a
  GIN index on the JSON; `consent_definitions` / `consent_records` record which
  exact text version an applicant accepted.
- **API (§60):** `/api/v1` exposes the public catalog, path resolution, redirect
  map, form delivery and availability, draft lifecycle, resume exchange, the
  applicant portal and the builder.

Still open from the brief: the WPForms binary file copy and delta cutover tools
(§57–59) remain as previously recorded, and the admin report-builder UI and
XLSX export inbox are unchanged from the earlier audit.

## Migration, exports, automations and templates (2026-09-21)

This closes the items previously recorded as open.

- **WPForms migration module (§52–59):** a migration console at
  `/admin/wpforms-migration` with a compatibility scan that reports supported /
  needs-review / unsupported fields *before* writing anything; import from a
  WPForms export file or straight from a read-only `wordpress_mysql`
  connection; a delta import (`only entries changed since`) for the pre-cutover
  catch-up; `import_batches` and `legacy_mappings` making every run traceable
  and every import idempotent.
- **Binary file copy (§57):** the previously flagged gap. Attachments are now
  fetched, checksummed (SHA-256), type-sniffed from their contents and stored
  privately, with per-file `copied` / `missing` / `failed` state surfaced under
  **Migration → Mapping issues**.
- **Reconciliation (§57):** legacy entry counts compared against imported
  counts per form, plus file totals. Reports *incomplete* while attachments are
  unresolved, and *unverified* — not reconciled — when the legacy database is
  unreachable.
- **Entry import fallback (§55):** `/admin/entry-import` reads a CSV or XLSX
  export, shows real sample values beside each column, suggests a mapping and
  requires the operator to confirm it. Re-running the same file updates rather
  than duplicates.
- **Cutover tools (§58–59):** legacy alias generation and resume-link reissue,
  both with a dry-run preview listing exact recipients, plus a database-backed
  cutover checklist in the UI and as `migration:cutover checklist` (non-zero
  exit while work remains).
- **Exports (§49):** queued CSV/XLSX generation with a download inbox under
  **Reporting → Exports**, per-reporting-key answer columns, sensitive fields
  masked, scoping taken from the requester, retention-based purge, and an
  `Export selected` bulk action on the applications table.
- **Automations and reminders (§33–34):** `automation_rules` with trigger,
  conditions and actions, editable in plain language at
  **Communication → Automations** with *Preview matches* and *Run now*. One
  `automations:run` scheduler entry drives all of them; no cron syntax is ever
  shown or required.
- **Form templates (§10):** the eleven ARUCAD templates are seeded and
  assembled from the same reusable blocks, carrying their own Save & Resume
  durations (YKS 10 days, DGS 30). Creating a form from a template was broken —
  fields were created without a machine name — and now produces a complete,
  answerable form.
- **PDF templates (§36):** the five templates from the brief are seeded and
  generate real documents.
- **Email variables (§30):** the template editor has an *Insert variable*
  picker and a plain-language reference list; staff no longer type smart-tag
  syntax by hand.
- **Staff assignment (§43):** `assigned_to` on applications, with a bulk
  assign action and a timeline entry.

Still open, and deliberately not claimed: the free-form report-builder UI and
the dedicated funnel/drop-off charts (§48, §49) remain as recorded in the
earlier audit — the underlying events and reporting columns exist, the
exploratory UI does not.

## Defect fixes (2026-09-21)

- `/admin/reports-dashboard` returned a 500: the page declared `filterForm()`
  but never registered it in `getForms()`, so Filament's property resolver fell
  past the form resolver into the infolist resolver and called it with an
  `Infolist`. Fixed, and covered by `PanelSmokeTest`.
- `/admin/coupons/create` returned a 500 on PostgreSQL: the relationship picker
  issues `select distinct "forms".*`, and `forms` held `json` columns, which
  have no equality operator. All JSON columns are now `jsonb`.
- A sweep of all 60 admin screens, both other panels and 13 edit screens now
  passes; the sweep runs as a test rather than by hand.

## Analytics and reporting (2026-09-21)

The last two items from the earlier audit are now implemented.

- **Funnel and drop-off analytics (§48):** `/admin/form-analytics` shows
  views → started → drafts → submitted for a chosen form and date range, with
  start, completion and abandonment rates and average time to complete; drop-off
  page by page (which page loses people, and how many); the fields with the most
  validation errors and the longest time spent; incomplete applications bucketed
  by age; and submissions over time. Counts are per session, not per event.
- **Report builder (§49):** the reports dashboard gained a group-by picker
  covering form, period, category, faculty, programme, academic year, intake,
  lifecycle, review status, decision, source, assigned staff, country, city,
  school and nationality — with counts, shares, a bar for each row and a CSV
  download of exactly what is on screen. Document completeness and review
  throughput are shown alongside it.

With these, every numbered item in the platform brief has an implementation.
The remaining entries in this document describe production configuration
(malware scanning service, email provider webhooks, Entra MFA policy), not
missing application code.

## Quality and defect fixes (2026-09-21, second pass)

- **Portfolio, multi-file and document uploads rendered as text boxes.** Five
  field types were offered in the builder that the public runtime could not
  render, so an applicant on a talent-exam form could not attach their work.
  Fixed, and guarded by `FieldCatalogContractTest`.
- **Every emailed resume link was malformed** where `FRONTEND_URL` lists more
  than one origin. The canonical public URL is now separate from the CORS list.
- **The duplicate Filament student panel is retired** in favour of the Next.js
  applicant portal; the backend `/student` routes redirect to it.
- **91 frontend tests added** (Vitest + Testing Library) covering the form
  runtime, the builder and the portal, wired into CI.

Known gap, stated plainly: there is still no browser-level test, so
pointer-driven drag-and-drop in the builder and the full apply → resume →
submit journey are only verified by hand.

## Browser-level testing (2026-09-21)

15 Playwright specs now run against the real stack — a database, the API and a
production build of the frontend:

- **Applicant journey:** find a form from the catalog, open it, autosave a
  draft, submit it and receive a reference; an expired resume link explains
  itself; a closed form refuses a submission at the API.
- **Builder:** a field dragged from the palette onto the canvas with a real
  pointer; fields reordered by dragging; build and publish without seeing JSON;
  editing a published form opens version 2 and leaves version 1 intact.
- **Public site:** catalog renders, audience tabs filter, a preserved legacy
  WordPress URL renders in place with no redirect, a moved URL issues its
  recorded 301, an unknown path 404s.

These found three defects no existing test could:

1. **No applicant could submit any form.** `X-Form-Token` was missing from the
   CORS allowlist, so the browser stripped it and every save and submit failed.
2. **31 homepage views a minute from one IP broke the public site**, because
   reads shared the submission rate budget.
3. **The English homepage was empty** — every publication was Turkish-only.

Run them with `npm run test:e2e` (the API must be running); CI runs them in a
dedicated job with Postgres.

## Public site design (2026-09-21)

The old apply.arucad.edu.tr is a bulleted list of links. The same information
architecture — four audience-led categories, the same fifteen forms — is now a
portal: hero with live counts, audience tabs, search, and a card per
application showing its state, deadline and days remaining.

Fixed along the way:

- **The application form's own title was `sr-only`.** A sighted applicant saw a
  set of fields with no indication of which form they had opened — worst for
  someone returning days later from a resume link. Now visible, with a link
  back to the full list.
- **The English homepage was empty**, because every publication was
  Turkish-only.
- **Two demo records** ("ARUCAD Örnek Başvuru", "art of da vinci subs") were
  listed as real applications. Hidden from the public catalog, not deleted —
  one toggle in Admin → Public site → Form cards restores either.
- **`npm run dev` took whatever port was free**, so the documented URL, the CORS
  allowlist and reality could disagree. Both `dev` and `start` now pin
  127.0.0.1:3000.
- **The palette is a single calm navy accent**, with red kept for the ARUCAD
  mark and for genuine deadline warnings.

## Brand and form UX (2026-09-21)

- The official ARUCAD mark replaces the redrawn SVG across the public site, the
  applicant portal, the form runtime and all three Filament panels.
- The application form is a two-column working layout: sticky sidebar with a
  visible progress bar, numbered step list, draft reference and autosave state;
  the form beside it rather than alone in the middle of the page.
- The progress bar was hidden outright by a `display: none` in `globals.css`.

- The navbar is full-bleed and sticky, with the brand at the left edge and the
  audience/language controls at the right; it previously sat boxed inside the
  page with 70px padding and a black top rule.
