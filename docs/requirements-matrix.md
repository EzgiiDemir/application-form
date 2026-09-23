# ARUCAD Apply — requirements matrix (§1–112)

The single source of truth for the platform backlog. Supersedes the status
tables in `architecture-assessment.md`, which remains as the narrative rationale.

Date: 2026-09-23.

**Status vocabulary**

| Status | Meaning |
| --- | --- |
| `COMPLETE` | The intended workflow is connected end to end and authorization was checked. |
| `PARTIAL` | Some of it works; the missing piece is named explicitly. |
| `MISSING` | Not implemented, or implemented but unreachable. |
| `CONFLICT` | Conflicts with the existing architecture; needs a decision. |
| `BLOCKED` | Requires a product decision before anything is built. |
| `N/A` | Only meaningful inside WordPress; the equivalent is named. |

**A class, route, table or migration existing does not make a section
`COMPLETE`.** Several rows below are `MISSING` precisely because a well-named
service exists but nothing reaches it.

**Phases**: `P0` blocking, `P1` core architecture, `P2` operational,
`P3` later, `VERIFY` needs testing rather than building.

---

## §1–31 — platform foundations

| § | Requirement | Status | Existing | Missing | Key files | Security | Data | Phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Reusable multi-form platform | `COMPLETE` | forms/versions/categories/audiences | — | `Form`, `FormVersion` | — | — | — |
| 2 | Remove applicant portal & accounts | `MISSING` | portal still live | whole removal | `src/app/student/`, `StudentIdentityService` | removes an auth surface | re-key applications to email | **P0** |
| 3 | Status tracking without an account | `COMPLETE` | token page, hashed/expiring/revocable | — | `ApplicationStatusController` | token is the credential | tracking tokens issued on submit | — |
| 4 | Configurable status engine | `COMPLETE` | registry + admin + seeded graph | — | `ApplicationStatusRegistry` | internal stages never shown | seeder reproduces shipped graph | — |
| 5 | Workflow engine (WHEN/IF/THEN) | `COMPLETE` | triggers, conditions, actions | — | `AutomationEngine` | — | — | — |
| 6 | Form metadata & publishing | `COMPLETE` | — | — | `FormPublication` | — | — | — |
| 7 | Continuous vs periodic forms | `COMPLETE` | 3 availability modes | — | `FormAvailabilityService` | — | — | — |
| 8 | Multi-page forms | `COMPLETE` | — | — | `FormPage` | — | — | — |
| 9 | 12-column responsive grid | `MISSING` | fields stack vertically | width per breakpoint, row/column model, builder UI, renderer | `form_fields`, `FormRenderer.tsx` | — | new columns; version-safe | **P1** |
| 10 | Field types | `PARTIAL` | 62 registered | decimal, toggle, gender, birth date, age, department, rich-text input | `config/form_fields.php` | — | — | P2 |
| 11 | Field settings incl. PII flags | `COMPLETE` | — | — | `form_fields` | encrypt_value, is_sensitive | — | — |
| 12 | Conditional logic rule engine | `COMPLETE` | — | — | `FormRulesEngine` | — | — | — |
| 13 | Save & resume as a form setting | `COMPLETE` | — | — | `DraftResumeService` | resume token | — | — |
| 14 | Draft/abandoned + reminders | `COMPLETE` | — | — | `ApplicationReminder` | — | — | — |
| 15 | Confirmations | `PARTIAL` | inline/page/redirect | `{application_reference}`, `{tracking_url}` unsupported (see §92) | `ConfirmationService` | `{entry_id}` exposes a DB id | — | **P1** |
| 16 | Per-form notifications | `COMPLETE` | — | — | `NotificationTrigger` | — | — | — |
| 17 | Central email templates | `COMPLETE` | — | — | `NotificationTemplate` | — | — | — |
| 18 | Email queue | `PARTIAL` | status, attempts, error, dedupe | `scheduled_at`, `cancelled` state, scheduled send, manual cancel | `notification_logs` | — | new columns | **P1** |
| 19 | Email log with retry | `COMPLETE` | — | — | `NotificationLogResource` | content visibility gated | — | — |
| 20 | Bulk email | `COMPLETE` | queued, batched | — | `BulkCommunicationService` | permission-gated | — | — |
| 21 | Scheduler screen | `COMPLETE` | — | — | `ScheduledOperation` | — | — | — |
| 22 | PDF system | `COMPLETE` | — | — | `ApplicationPdfService` | signed access | — | — |
| 23 | Configurable action buttons | `PARTIAL` | Filament actions exist | registry with permission/confirm/status/workflow per action | `FormSubmissionResource` | each action needs its own check | — | **P1** |
| 24 | CRM-grade submissions grid | `COMPLETE` | filters, saved views, exports | — | `FormSubmissionResource` | scoped per user | — | — |
| 25 | Entry detail workspace | `PARTIAL` | edit page exists | tabbed operational layout (§25 list) | `FormSubmissionResource` | — | — | **P1** |
| 26 | Internal notes | `COMPLETE` | never applicant-visible | — | `FormEntryNote` | — | — | — |
| 27 | Staff assignment | `PARTIAL` | `assigned_to` manual | round robin, by faculty/department/country | `form_submissions` | — | — | **P1** |
| 28 | Granular capabilities | `COMPLETE` | 11 roles / 44 permissions | — | spatie/permission | server-side enforced | — | — |
| 29 | Per-form access control | `COMPLETE` | — | — | `FormAccessService` | UI+API+export | — | — |
| 30 | Content access policy | `PARTIAL` | form/category scoping | reusable policy for pages/posts/categories | `ResourceScopeService` | — | — | P2 |
| 31 | Application catalogue | `PARTIAL` | categories, audiences, cards | "Başvurularım" must go (§2) | `PublicCatalogService` | — | — | **P0** |

---

## §32–70 — platform behaviour

| § | Requirement | Status | Existing | Missing | Key files | Security | Data | Phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 32 | Remove "Events" placement concept | `N/A` | no such concept; forms attach to publications/categories/pages | — | `FormPublication` | — | — | — |
| 33 | Page / slug management | `MISSING` | `site_pages` has 5 columns | content, parent, template, order, featured image, slug-collision guard | `site_pages` | — | new columns | **P2** |
| 34 | SEO / meta | `MISSING` | none | meta title/description, canonical, robots, social image | `site_pages` | noindex control matters for token pages | new columns | **P2** |
| 35 | Admin navigation architecture | `PARTIAL` | Filament groups | align to §35 task grouping | panel providers | — | — | P3 |
| 36 | WordPress functional coverage | `N/A` | Posts/Media/Appearance/Plugins have no counterpart | — | — | — | — | — |
| 37 | No file manager / code editor | `COMPLETE` | none exists, none should | — | — | **no code-execution surface** | — | — |
| 38 | Content sanitisation | `PARTIAL` | `ContentSafetyService` | not verified end to end against stored XSS | `ContentSafetyService` | see §75 | — | **VERIFY** |
| 39 | File upload security | `COMPLETE` | extension **and** MIME allowlists, size, safe names, protected downloads, audit | antivirus hook (future) | `SecureFileService` | strong | — | — |
| 40 | Privacy / KVKK module | `COMPLETE` | versioned consent | — | `ConsentRecord` | consent not rewritten | — | — |
| 41 | Data subject requests | `COMPLETE` | — | — | `DataSubjectRequestService` | legal hold respected | — | — |
| 42 | Data retention | `COMPLETE` | — | — | `DataRetentionService` | — | purges auditable | — |
| 43 | Analytics focus / quiz result | `PARTIAL` | `QuizEngine` scores `quiz_type` forms | hide Quiz Result from generic analytics UI unless a form uses it | `QuizResultResource` | — | **must not lose scoring** | **P2** |
| 44 | Workflow visualisation | `MISSING` | engine only | "what happens after submit" view | `AutomationEngine` | — | — | **P2** |
| 45 | Form duplication | `COMPLETE` | submissions not copied | — | `FormSchemaService::duplicate` | — | — | — |
| 46 | Form versioning | `COMPLETE` | per-version field uuids | — | `FormVersion` | — | historic answers safe | — |
| 47 | Templates | `COMPLETE` | — | — | `FormTemplate` | — | — | — |
| 48 | Config import/export | `MISSING` | duplication only | portable JSON with version info | — | must never include applicant data | — | **P2** |
| 49 | Webhooks / integrations | `COMPLETE` | retries, logs | — | `WebhookService` | secrets server-side | — | — |
| 50 | Versioned REST API | `COMPLETE` | `/api/v1` | — | `routes/api_v1.php` | permission callbacks | — | — |
| 51 | Database architecture | `COMPLETE` | custom tables + typed projection | — | 104 tables | — | — | — |
| 52 | Service architecture | `COMPLETE` | ~70 services | — | `app/Services` | — | — | — |
| 53 | Domain events | `PARTIAL` | 2 events | 7 more (`DraftSaved`, `DocumentUploaded`, `StaffAssigned`, …) | `app/Events` | — | — | **P1** |
| 54 | Audit log | `PARTIAL` | submissions covered | form/workflow/template changes not logged (see §90) | `AuditTrailService` | no secrets logged | — | **P1** |
| 55 | Duplicate prevention | `PARTIAL` | `CandidateDuplicateService` scans | per-form rules: allow/warn/block/update/review, keyed on email/TC/passport/phone | `CandidateDuplicateService` | — | new config | **P1** |
| 56 | Deadlines & timezone | `COMPLETE` | one policy, server-authoritative | — | `FormAvailabilityService` | — | — | — |
| 57 | Localization | `PARTIAL` | translation columns exist | hardcoded Turkish in several services | `ApplicationStage`, services | — | — | P2 |
| 58 | Accessibility | `PARTIAL` | labels/responsive present | no automated checks; builder drag-drop keyboard path unconfirmed | `FormRenderer.tsx` | — | — | **VERIFY** |
| 59 | Validation both sides | `COMPLETE` | server authoritative | — | Laravel validation | — | — | — |
| 60 | Submission idempotency | `COMPLETE` | hashed key, replayed | — | `form_submissions` | — | — | — |
| 61 | Spam / rate limit | `COMPLETE` | honeypot, timing, split budgets | CAPTCHA adapter (future) | `config/forms.php` | shared-IP safe | — | — |
| 62 | Admin UX consistency | `COMPLETE` | Filament design system | — | panels | — | — | — |
| 63 | Operational dashboard | `PARTIAL` | `OperationsDashboard` | not audited against the §63 card list | `OperationsDashboard` | metrics must respect scope | — | P2 |
| 64 | Search | `PARTIAL` | reference/name/email/phone | ID/passport gated for authorized users only | `FormSubmissionResource` | sensitive fields in suggestions | — | P2 |
| 65 | Exam / post synchronisation | `BLOCKED` | no sync service; no exam↔post relationship found | product definition | — | — | — | **BLOCKED** |
| 66 | Shortcodes / blocks | `N/A` | equivalent is `/apply/{slug}` | — | — | — | — | — |
| 67 | Clean application URLs | `COMPLETE` | slug + redirect aliases | — | `RouteAlias` | no internal ids | — | — |
| 68 | Performance at volume | `PARTIAL` | aggregates, streamed exports, queued bulk | never load-tested | `FormFieldAnalytics` | — | — | **VERIFY** |
| 69 | Cache compatibility | `PARTIAL` | Pragma/X-Accel-Expires/X-Robots-Tag on personal routes | `Cache-Control` reserved by Next → edge config; **not deployed or verified** | `next.config.ts` | cross-applicant cache risk | — | **P0** |
| 70 | Mail provider compatibility | `COMPLETE` | queue/templates/logs owned; transport is an adapter | — | `config/mail.php` | — | — | — |

---

## §71–111 — the band audited in this pass

| § | Requirement | Status | Existing | Missing | Key files | Security | Data | Phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 71 | Consistent error handling | `PARTIAL` | API errors render as JSON | **404/500 leak `exception` + full filesystem path**; no single public message | `bootstrap/app.php` | **`.env.example` ships `APP_DEBUG=true`** | — | **P0** |
| 72 | Observability / health | `PARTIAL` | db, cache, queue, storage, failed jobs, scheduler | pending migrations, PHP extensions, PDF engine, mail transport, cache-header check (§69) | `SystemHealthService` | — | — | **P1** |
| 73 | Versioned migrations | `COMPLETE` | Laravel runner, no manual SQL | destructive-migration warning | `database/migrations` | — | — | — |
| 74 | Testing critical behaviour | `PARTIAL` | 241 backend / 96 unit / 27 e2e | §75 matrix not executed | `tests/` | — | — | **P0** |
| 75 | Security test cases | `PARTIAL` | some covered incidentally | 11-point matrix not executed and recorded | `tests/Feature` | the point of the section | — | **P0** |
| 76 | Responsive public UX | `PARTIAL` | status page verified at 390px | multi-step, uploads, date fields, dropdowns unverified on mobile | `site.css` | — | — | **VERIFY** |
| 77 | Minimal application experience | `COMPLETE` | logo/title/intro/progress/steps/save/submit | — | `FormRenderer.tsx` | no admin concepts exposed | — | — |
| 78 | Confirmation experience | `PARTIAL` | reference + message shown | **email not masked** (`e***@example.com`); no tracking link shown | `FormRenderer.tsx` | full address displayed | — | **P1** |
| 79 | Editing after submission | `PARTIAL` | 4 modes enforced at `update()` | **no admin UI for `edit_policy`**; no "X days" mode; reopen action lives in the **trainer panel** that §2 removes | `ApplicationEditPolicyService` | enforced server-side | — | **P0** (§2 dependency) |
| 80 | Document request flow | `MISSING` | table + service + admin request | **applicant path unreachable**: `submitStudentResponse` called only from the unregistered Student panel; `/me/documents` is read-only; no token upload page | `DocumentRequestService` | needs its own scoped token | — | **P0** (§2 dependency) |
| 81 | Form responsible staff | `PARTIAL` | `user_form_scopes`, `assigned_to` | not surfaced in form settings as responsible staff / default assignee / recipients | `FormAccessService` | — | — | P2 |
| 82 | Archiving closed forms | `MISSING` | soft delete only | archive state: admin-visible, keeps submissions, refuses new ones | `forms` | — | new state | **P2** |
| 83 | Soft delete | `PARTIAL` | forms, submissions, candidates | permanent delete not audited; users hard-delete | models | deletion needs permission | — | P2 |
| 84 | PII masking | `MISSING` | none | masked TC/passport/email in tables, reveal for privileged users | `FormSubmissionResource` | **sensitive data shown in full today** | — | **P1** |
| 85 | Session / token design | `COMPLETE` | 64-char random, hashed, expiring, revocable, purpose-scoped | — | `SubmissionEditTokenService` | not derived from email/id/time | — | — |
| 86 | CSRF / nonces | `PARTIAL` | admin CSRF on; 4 routes waive it deliberately | waivers rely on a JSON-content-type gate; only `/logout` has that test | `routes/web.php` | audit the other 3 waivers | — | **P1** |
| 87 | API response contract | `PARTIAL` | `{data}` and `{message, errors}` keyed by field | no `meta`; error body leaks `exception` and path in debug | controllers | see §71 | — | **P0** |
| 88 | Frontend state architecture | `COMPLETE` | schema / UI state / persistence separated | — | `builder-state.ts` | — | — | — |
| 89 | Builder auto-save | `COMPLETE` | saving/saved/failed states, drafts only | — | `BuilderWorkspace.tsx` | never edits a published version | — | — |
| 90 | Admin change history | `MISSING` | submissions audited | no activity log on forms, workflows or templates | `AuditTrailService` | "why did production change" | — | **P1** |
| 91 | Email preview / test send | `MISSING` | none | preview with a sample submission, marked test send, no workflow side effects | `NotificationTemplateRenderer` | a test must not fire workflows | — | **P2** |
| 92 | Central variable resolver | `PARTIAL` | genuinely centralised: 2 consumers, no rival implementations | namespaced syntax; `{application_reference}`, `{tracking_url}`, `{status}`, `{first_name}` unsupported | `SmartTagService` | **`{entry_id}` exposes a database id**; `{user_*}` leaks staff identity | — | **P1** |
| 93 | Report builder | `COMPLETE` | 16 dimensions, filters honoured | — | `ReportBuilderService` | permission-scoped | — | — |
| 94 | Saved views | `COMPLETE` | per-user, shareable | — | `CrmSavedView` | — | — | — |
| 95 | Notes vs blog comments | `COMPLETE` | no blog comment system exists to confuse | — | `FormEntryNote` | — | — | — |
| 96 | Admin notification centre | `MISSING` | email only | in-app centre; the events to build on exist | — | scope-aware | — | **P2** |
| 97 | Feature flags | `MISSING` | none | a flag mechanism for half-built modules | `config/` | keeps unfinished work out of production | — | **P2** |
| 98 | Configuration hierarchy | `PARTIAL` | form settings override config defaults | not a documented or enforced chain (system → form → workflow → submission) | `config/forms.php` | — | — | P3 |
| 99 | Do not rebuild WordPress | `N/A` | the platform replaces WordPress by decision | — | — | — | — | — |
| 100 | No WPForms dependency | `COMPLETE` | no package, no runtime coupling, guarded reader | — | `Migration/*` | — | importer kept per §101 | — |
| 101 | WPForms data migration | `COMPLETE` | importer, reconciliation, reports | — | `MigrationOrchestrator` | — | source kept until verified | — |
| 102 | UI design principle | `PARTIAL` | consistent, dense, tabular | status still leans on colour; needs label and icon everywhere | Filament | — | — | P3 |
| 103 | Admin information architecture | `PARTIAL` | the screens exist | not organised around the six §103 questions | panels | — | — | P3 |
| 104 | Remove / clean features | `PARTIAL` | — | the §2 removal list; duplicate save/confirmation/notification config outside the form workflow not yet traced | — | stale permissions to remove | — | **P0** |
| 105 | Deliverable order | `COMPLETE` | phased, adjusted to the real architecture | — | this matrix | — | — | — |
| 106 | Pre-change checklist | `COMPLETE` | followed for §3, §4 and §69 | — | — | — | — | — |
| 107 | Post-implementation checks | `PARTIAL` | tests and browser sweeps per change | not a formal repeatable checklist | — | — | — | P3 |
| 108 | Coding quality | `PARTIAL` | conventions followed | some very long single-line Filament resources | `FormResource.php` | no secrets committed | — | P3 |
| 109 | Documentation | `PARTIAL` | README, assessment, this matrix, 7 docs | no schema reference or extension-point guide | `docs/` | — | — | P2 |
| 110 | Final target experience | `PARTIAL` | the public flow is nearly there | blocked on §2, §80 and §78 | — | — | — | **P0** |
| 111 | Application Operations Platform | `COMPLETE` | responsibilities separated | — | — | — | — | — |
| 112 | Initial analysis | `COMPLETE` | this matrix plus the assessment | — | `docs/` | — | — | — |

---

## Three findings that change the plan

1. **§80 is unreachable, not merely login-gated.** The applicant's response to a
   document request is invoked only from `app/Filament/Student/`, which is not a
   registered panel, and `/me/documents` is read-only. Nobody can respond to a
   document request today. So §2 does not break it — but §2 cannot be called
   complete without the token-based replacement, which its own acceptance
   criteria require.

2. **§79's "request correction" action lives in the trainer panel**, which §2
   removes. It must move to the admin panel before that happens, or
   `correction_only` editing becomes unreachable as well.

3. **§71 and §87: error responses leak `exception` and the full filesystem
   path**, and `.env.example` ships `APP_DEBUG=true`. Anyone copying it to
   production publishes stack traces. Small fix, real exposure.

## Unverified — not to be reported as production ready

Per §8 of the current instructions, these stay unverified until tested in the
deployment environment: accessibility and keyboard operation, performance at
realistic volume, queue worker behaviour, production cron, 30 MB uploads,
reverse-proxy cache behaviour, and production UAT.
