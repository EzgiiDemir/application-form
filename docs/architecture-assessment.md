# Architecture assessment — ARUCAD Apply

Assessment of the existing codebase against the 31-section platform specification,
written before any implementation, as the specification requires.

Date: 2026-09-23.

---

## 1. The blocking contradiction

The specification is written for a WordPress codebase. It asks for custom post
types, WP options, WordPress capabilities, wp-cron, "internal WordPress users",
and names a Senior WordPress Engineer as one of the roles.

**This project is not a WordPress project.** It is:

| Layer | Technology |
| --- | --- |
| Backend | Laravel 12, PHP 8.5 |
| Admin | Filament 3.3 (three panels) |
| Database | PostgreSQL, 104 tables |
| Frontend | Next.js 16 (App Router), React 19, TypeScript |
| Tests | 216 backend, 96 frontend unit, 22 browser |

It was built over the preceding phases explicitly to **replace** the WordPress /
WPForms site at `apply.arucad.edu.tr`. The repository contains a complete
one-way migration module (`app/Services/Migration/`) that reads the legacy
WordPress database, copies its uploads, reconciles entry counts and runs a
cutover checklist. WordPress is already treated as the *source* system being
retired, not a runtime dependency.

Section 0 of the specification says: do not replace working functionality, prefer
refactoring over rewrites, preserve existing application data. Rebuilding this
platform inside WordPress would do the opposite of all three — it would discard
104 tables, ~70 service classes, 33 admin resources and 334 tests to re-implement
the same product on a weaker foundation.

The two readings, and what each costs:

- **A — the WordPress wording is inherited boilerplate.** The intent is the
  *product* requirements, to be built on the platform that already exists. Most
  of the specification is already implemented; the remaining work is roughly
  five focused changes (section 4 below).
- **B — WordPress is a genuine requirement.** Everything below is void. This
  becomes a ground-up rebuild in a different language runtime, re-earning
  capabilities that currently pass 334 tests.

This document assumes **A**. If B is intended, stop and say so before any of the
work below begins.

---

## 2. What the specification asks for that already exists

Verified against the live database and code, not assumed:

| § | Requirement | Status | Where |
| --- | --- | --- | --- |
| 1 | Reusable multi-form platform, not one form type | Built | `forms`, `form_versions`, `form_categories`, `application_audiences` |
| 5 | Workflow engine (WHEN / IF / THEN) | Built | `AutomationEngine`, `automation_rules` (trigger, conditions, actions, form scope, schedule) |
| 6 | Form metadata, versioning, publishing | Built | `Form`, `FormVersion`, `FormPublication`, `SitePage` |
| 7 | Continuous vs periodic forms | Built | `FormAvailabilityService` — always_open / date_range / academic_period |
| 8 | Multi-page forms with sections | Built | `FormPage` |
| 10 | Extensible field types | Built | `FieldTypeRegistry` + `config/form_fields.php` — **62 types registered** |
| 11 | Per-field settings incl. PII/encryption | Built | `form_fields`: validation, conditional_logic, privacy, is_sensitive, encrypt_value, reporting_key |
| 12 | Generic conditional-logic rule engine | Built | `FormRulesEngine` |
| 13 | Save & resume as a form setting | Built | `DraftResumeService`, `SubmissionEditToken` |
| 14 | Draft / abandoned applications + reminders | Built | `ApplicationReminder`, abandoned stage, scheduled jobs |
| 15 | Confirmations | Built | `FormConfirmation`, `ConfirmationService` |
| 16 | Per-form notifications | Built | `NotificationTrigger`, `NotificationTemplate` |
| 17 | Central email templates | Built | `NotificationTemplate`, `NotificationTemplateRenderer` |
| 19 | Email log with retry | Built | `NotificationLog` + admin resource (status, attempts, error, dedupe_key) |
| 20 | Bulk email from submissions | Built | `BulkCommunicationService` + bulk actions |
| 21 | Scheduler administration screen | Built | `ScheduledOperation` + admin resource |
| 22 | PDF generation and templates | Built | `PdfTemplate`, `GeneratedDocument`, `ApplicationPdfService` |
| 24 | CRM-grade submissions grid | Built | `FormSubmissionResource`, `CrmSavedView`, `ApplicationExportService` (CSV/XLSX) |
| 26 | Internal notes, never applicant-visible | Built | `FormEntryNote`, `CandidateNote` |
| 28 | Granular capabilities, not role checks | Built | spatie/laravel-permission; capabilities enforced server-side |
| 29 | Per-form staff access control | Built | `user_form_scopes`, `UserCategoryScope`, `FormAccessService`, `ResourceScopeService` |
| 31 | Public application catalogue by category | Built | `PublicCatalogService`, audiences, categories, form cards |

The specification is largely a description of what this codebase already does.
That is the single most important finding: this is **not** a greenfield build.

---

## 3. Genuine gaps

Five items are real work. Everything else is polish.

### 3.1 §2 — remove applicant accounts and the applicant dashboard (largest change)

This is a **removal**, and it deletes working, tested functionality:

- `frontend/src/app/student/` — a six-page applicant portal (overview,
  applications, documents, notifications, profile, settings)
- `StudentIdentityService`, `StudentIdentityLink`, the `student` role, the
  student sign-in path, and the one-click student demo login
- `heroMine` / "Başvurularım" on the public homepage (§31 requires its removal)
- `app/Filament/Student/` — already dead code, unregistered

Note the specification also says to remove the **Trainer** account type (§2),
while §28 lists **Reviewer** as a role. These reconcile: the trainer *portal*
concept goes away, but the reviewing function must stay — there is a whole
evaluation subsystem behind it (`EvaluationAssignment`, `EvaluationRubric`,
`JuryEvaluationService`, `JuryAggregationService`, the trainer panel). The safe
reading is: reviewers become ordinary internal staff users with
`view_assigned_submissions`, not a separate account type with its own portal.
**Deleting the evaluation subsystem is not implied and should not happen.**

### 3.2 §3 — public status tracking without an account

Exists: `ApplicationReferenceGenerator` (human-readable references),
`SubmissionEditToken` (secure, expiring tokens).

Missing: the status endpoint is `api/v1/applications/{uuid}/status` — keyed by
**database UUID**, which §3 forbids exposing. Needs a `/application-status/{token}`
route backed by a dedicated, revocable, optionally single-use tracking token,
plus the applicant-facing page.

### 3.3 §4 — statuses are hardcoded; the configurable table is dead code

A first pass at this table is misleading. `application_status_definitions`
exists with `key, labels, color, is_terminal, applicant_visible, order` — but:

- the table is **empty**, and
- `ApplicationStatusDefinition` is referenced by **no file except its own model**.

The statuses actually in use are hardcoded PHP constants in
`app/Support/ApplicationStage.php` (16 of them, with a hardcoded Turkish label
map), plus parallel `decision_status` and `enrollment_status` columns. This is
exactly what §4 rules out: *"Statuses must NOT be hardcoded. Admin must be able
to create statuses."*

So this is not a matter of adding columns. It needs:

- seeding the definition table from the current constants, so nothing breaks
- an admin resource to manage them
- `allowed_transitions`, `icon`, `description`, `email_applicant_on_entry`,
  `triggers_workflow`
- migrating every read of `ApplicationStage::` to the table, keeping the
  constants only as seed data and as keys

This is the second-largest item after the grid, not a small one.

### 3.4 §9 — the 12-column grid

`form_fields` has no width columns. Fields stack vertically. This needs
desktop/tablet/mobile width per field (a 1–12 value each), a row/column concept
in the builder canvas, and renderer support. This is the largest *new* build
and touches builder, schema, versioning and the public renderer together.

### 3.5 §18 — email queue is not a real queue

`notification_logs` has `status, attempts, error, dedupe_key` but **no
`scheduled_at`**, so "send at a datetime", scheduled bulk sends and controlled
backoff cannot be expressed. §18 also asks for a `cancelled` state and manual
resend of a scheduled message. Needs a scheduling column and a cancel path.

### 3.6 Smaller gaps

- §23 — action buttons exist as Filament actions, but not as a configurable
  registry with permission / confirmation / resulting status / workflow per action.
- §25 — the entry detail screen is not yet organised into the tabbed operational
  workspace described.
- §27 — `assigned_to` exists; automatic routing strategies (round robin, by
  faculty, by country) do not.
- §30 — content-level access policy is partial.
- §10 — missing from the 62 registered types: decimal, toggle, gender, birth
  date, age, department selector, rich-text *input* (rich-text display exists).

---

## 4. Proposed sequencing

Ordered so that destructive work happens only after the replacement exists.

1. **§3 status tracking** — build the token-based public status page first. It is
   the replacement for the applicant dashboard; removing the dashboard before it
   exists would leave applicants with no way to check anything.
2. **§4 status engine** — extend definitions, add allowed transitions.
3. **§2 removal** — retire the applicant portal, the student role and the
   "Başvurularım" entry points, once (1) covers the need. Reviewers become staff.
4. **§18 email queue** — add scheduling and cancellation.
5. **§9 grid system** — builder, schema, renderer.
6. **§23/25/27/30** — action registry, detail workspace, routing, content policy.

---

## 5. Risks

- **Data preservation.** Removing the student role must not orphan
  `student_identity_links` or applications already attached to accounts. This
  needs a migration that re-keys those applications to their email address,
  which §2 makes the primary channel.
- **No commit history.** The repository has never been committed. Every change
  so far has been made against an unversioned tree. Before destructive work in
  step 3, this should be committed so the removal is revertible.
- **Tests encode the current behaviour.** 334 tests pass today. Removing the
  applicant portal will legitimately break the student-portal tests; those should
  be deleted deliberately, not patched into passing.

---

## 6. Sections 32–70

Same assumption as section 1: WordPress-specific wording (custom post types, WP
capabilities, wp-cron, Theme/Plugin editors) describes the system being retired,
not a target to rebuild into. Sections that exist only inside WordPress are
marked N/A with the equivalent named.

### Already built

| § | Requirement | Where |
| --- | --- | --- |
| 39 | File upload security | `SecureFileService` — extension **and** MIME allowlists (per field, then global), size limits, generated names, `SubmissionFileAudit`, protected download controller |
| 40 | Privacy / KVKK as a real module | `ConsentDefinition`, `ConsentRecord`, `PrivacyPolicyVersion` — consent is versioned, so historic consent is not rewritten when text changes |
| 41 | Data subject requests | `DataSubjectRequest` + `DataSubjectRequestService` |
| 42 | Retention and legal hold | `RetentionPolicy`, `DataRetentionService`, scheduled purges |
| 45 | Form duplication | `FormSchemaService::duplicate()`, exposed as "Make a copy"; submissions are not copied |
| 46 | Form versioning | `FormVersion` + `form_version_id` on every submission; answers keep per-version field uuids |
| 47 | Templates | `FormTemplate`, `BuiltInFormTemplateService` |
| 49 | Webhooks | `WebhookEndpoint`, `WebhookDelivery`, `WebhookService` |
| 50 | Versioned REST API | `/api/v1`, permission-checked, validated, paginated |
| 51 | Custom tables, not meta abuse | 104 tables; answers additionally projected into typed columns for reporting |
| 52 | Service architecture | ~70 services; no business logic in admin templates |
| 54 | Audit log | `AuditLogService`, `AuditTrailService`, spatie/activitylog |
| 56 | Deadlines and timezone | `FormAvailabilityService`, one policy, server-authoritative |
| 59 | Server-authoritative validation | Laravel validation owns it; the client only improves UX |
| 60 | Submission idempotency | `idempotency_key` on `form_submissions`, hashed and replayed |
| 61 | Spam defence without WPForms | honeypot (`_website`), timing (`_started_at`), per-IP rate limits split browse vs apply |
| 64 | Search | submission search across reference, name, email, phone |
| 67 | Clean application URLs | `/apply/{slug}` plus `RouteAlias` redirects when a slug changes |

### Not applicable, with the equivalent named

- **§32 — the "Events" concept does not exist here.** Nothing to redesign:
  forms attach to publications, categories, audiences and pages already.
  (`application_events` is the audit trail, not form placement.)
- **§35/36 — WordPress admin screens.** Posts, Media, Appearance, Plugins,
  Theme/Plugin editors have no counterpart. The Filament panels already group
  navigation along the lines section 35 describes.
- **§37 — file manager / code editor.** None exists, and none should be added.
  This is the good outcome: there is no executable-code editing surface to
  restrict.
- **§66 — shortcodes and blocks.** The equivalent is the `/apply/{slug}` route;
  embedding is a link, not a shortcode.

### Gaps

1. **§33/§34 — page and SEO metadata (real gap).** `site_pages` has only
   `id, title, slug, status, created_by`. Missing: content, parent, template,
   order, meta title, meta description, canonical, robots, featured image. No
   slug-collision guard.
2. **§44 — workflow visualisation.** The engine exists; nothing shows an admin
   what happens after submission.
3. **§53 — domain events are thin.** Two exist (`ApplicationSubmitted`,
   `ApplicationStatusChanged`) against the nine section 53 lists.
4. **§55 — duplicate prevention is not configurable.** `CandidateDuplicateService`
   scans, but there are no per-form rules (allow / warn / block / update
   existing / manual review) keyed on email, national id, passport or phone.
5. **§48 — configuration import/export.** Form duplication exists; portable
   JSON export of schema + settings + notifications is not implemented.
6. **§57 — localisation is partial.** Translation columns exist
   (`labels`, `title_translations`, `description_translations`), but Turkish
   strings are still hardcoded in several services.
7. **§58 — accessibility is unverified.** No automated checks; the builder's
   drag-and-drop needs a confirmed keyboard path.
8. **§65 — exam/post synchronisation.** No dedicated sync service exists. The
   original requirement ("exam fields add posts senkronizasyon") needs
   clarifying against the legacy behaviour before anything is designed.

### Closed in this pass

- **§69 — cache safety for personalized pages.** `/application-status/*`,
  `/resume/*`, `/apply/*` and `/student*` now send `Pragma: no-cache`,
  `X-Accel-Expires: 0` and `X-Robots-Tag: noindex`, and the public catalogue
  sends none of them.

  `Cache-Control` could **not** be set from the application: Next reserves it for
  dynamic pages and silently replaced it (verified — the three headers above
  arrive, a Cache-Control entry did not). It must be set at the reverse proxy;
  the README carries the nginx snippet. `/student` is the sharpest case, being
  the same URL for every signed-in applicant.

### Deliberately not done

- **§43 — removing quiz analytics.** The section says remove it "if it has no
  purpose". It has one: `QuizEngine` scores forms that set `quiz_type`, and
  `QuizResult` holds those scores. Deleting it would break quiz-type forms, so
  this needs a decision about whether quiz forms are still wanted rather than a
  silent removal.

---

## 7. Section 100 — WPForms independence

**The platform has no runtime dependency on WPForms.** Verified rather than
assumed:

- **No package dependency.** Neither `composer.json` nor `package.json`
  references WPForms.
- **No runtime coupling.** Twenty files mention WPForms. All are one-way import
  tooling (`WpformsImportService`, `WpformsRemoteClient`, `Migration/*`, the
  import admin pages and commands). The two mentions inside runtime models —
  `FormField` and `SubmissionValue` — are **comments** explaining why field
  uuids exist, not code paths.
- **No legacy database access in the application.** The legacy connection is
  used only by `WordPressDatabaseReader`, which is guarded: `available()` returns
  false unless `wordpress_mysql` is configured, and calls throw a clear error
  rather than failing obscurely. Rendering a form, saving a draft and submitting
  an application never touch it.
- **The one admin link** to the import page is permission-gated
  (`forms.create`).

If WPForms were deactivated and deleted tomorrow, the platform keeps working;
only the import screens would have nothing to read. That is section 100's
requirement, and section 101's requirement to keep the importer, satisfied at
the same time.

The import tooling should therefore be **kept, not removed** — it is the
migration path, and it already produces the migrated/skipped/unsupported/failed
reporting section 101 asks for (`ImportBatch`, `LegacyMapping`,
`MigrationReconciliationService`).

---

## 8. Section 112 — roles and capabilities

11 roles over **44 granular permissions**, enforced server-side via
spatie/laravel-permission. Capabilities, not role-name checks, are what the
policies test — section 28's requirement.

| Role | Permissions |
| --- | --- |
| super_admin | 44 |
| application_admin | 30 |
| faculty_admin | 18 |
| admissions_officer | 12 |
| department_secretary | 7 |
| reporting_user | 6 |
| technical_administrator | 5 |
| trainer | 5 |
| faculty_reviewer | 4 |
| read_only | 3 |
| student | 1 |

Relevant to sections 2 and 104: `student` (1 permission) and `trainer` (5) are
the two roles scheduled for removal. Their small surface is a good sign — the
removal is contained, and `faculty_reviewer` already exists as the reviewer role
that section 28 names.

The remaining points of section 112 are answered above: architecture map and
modules in section 1, storage model in sections 2 and 6, form/submission flow in
section 3.2, reusable components throughout, removals in section 3.1, and
implementation order in section 4.
