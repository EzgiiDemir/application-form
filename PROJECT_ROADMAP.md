# ARUCAD Apply — WordPress-Independent Application & CRM System

## Project Directive for AI Agent (End-to-End Implementation Guide)

> **Who this document is for:** This was written to be handed to an AI coding agent (Claude Code or similar) that will build this project from scratch. The agent will read this document, build the project from zero, implement the architecture accordingly, and progress through the phases in order. No ambiguity has been left unresolved; where the agent must make a technical decision, it should follow the choices in the sections labeled "Decision."

---

## 0\. Project Summary and Purpose

ARUCAD university currently runs 15 application forms (academic enrollment, competitions, dormitory/apartment, event forms) on `apply.arucad.edu.tr` using WordPress \+ WPForms. **Goal: build a complete application \+ CRM system, fully under our own control, with zero dependency on WordPress.**

WPForms' role is **limited to Phase 1, as a one-time data source.** After Phase 1 is complete, there is no dependency on WordPress anywhere in this document; the system has its own form design interface, its own database, its own authentication, and its own notification engine. The WordPress site can be shut down entirely once the migration is complete.

### The system's 4 panels

| Panel | User | Purpose |
| :---- | :---- | :---- |
| **Public Apply** | Applicant (no account required) | Next.js — fills out the form, updates it later via edit-link |
| **Admin/CRM Panel** | Super Admin, Faculty Admin, Department Secretary | Filament (Laravel) — manages all applications, designs forms, sets up notifications, pulls reports |
| **Trainer Panel** | Instructor/Jury member (e.g. talent exam jury) | Evaluates assigned candidates, enters notes/scores, makes decisions |
| **Student Panel** | Accepted/applying student | Views their own application status, missing documents, and results |

### Authentication

Both staff (admin/trainer) and students log in via **Microsoft Entra ID (Azure AD)** — institutional SSO. There is no Entra ID on the Public Apply side (before an application has been submitted), because the applicant may not yet have an institutional account; that side is edit-token based, unauthenticated access.

---

## 1\. Technology Stack (Decision — not to be changed)

- **Backend:** Laravel 11+ (PHP 8.3+)  
- **Admin/Trainer/Student panel:** Filament 3 (on top of Laravel) — **a single Filament installation, split by panel** (see Section 4\)  
- **Public Apply frontend:** Next.js 14+ (App Router), TypeScript  
- **Database:** PostgreSQL 16+  
- **Authentication:** Microsoft Entra ID — `laravel/socialite` \+ `socialiteproviders/microsoft` (Laravel side); not needed on the Next.js side (public apply has no login anyway)  
- **Authorization:** `spatie/laravel-permission`  
- **File storage:** S3-compatible object storage (AWS S3 or DigitalOcean Spaces) — local disk is **not used**  
- **Queue:** Laravel Horizon \+ Redis  
- **Notification delivery:** Laravel Notifications \+ Mailable, SMTP driver  
- **Repo structure:** **Monorepo** — a single repo containing `/backend` (Laravel) and `/frontend` (Next.js) folders

arucad-apply/

├── backend/          \# Laravel (API \+ Filament admin/trainer/student panels)

├── frontend/          \# Next.js (public apply interface only)

├── docs/              \# This document and other technical documentation

└── docker-compose.yml \# Local development environment (Postgres, Redis, Mailhog)

**Why monorepo:** Both sides depend on the same API contract; version sync and CI/CD are simpler in a single repo. The agent should implement this decision without questioning it.

---

## 2\. Phase Sequence (Strict Order — Agent Must Not Skip Phases)

Phase 0: Project scaffolding and environment setup

Phase 1: One-time data/form/file extraction from WPForms

Phase 2: Database schema and core architecture (migrations)

Phase 3: Authentication (Entra ID) and permission model

Phase 4: Laravel backend — form engine, own form builder, API

Phase 5: Filament panels — Admin/CRM, Trainer, Student

Phase 6: Notification center

Phase 7: Next.js Public Apply interface (pixel-identical to the WPForms design)

Phase 8: One-time WPForms JSON → system import

Phase 9: Testing, phased go-live, WordPress shutdown

Each phase depends on the output of the previous one. The agent must not move to the next phase before finishing one; each phase must end in a working state (migrations applied, tests passing).

---

## Phase 0 — Project Scaffolding and Environment (Duration: 2-3 days)

**Tasks:**

1. Create the monorepo, set up the folder structure above  
2. `backend/`: start a Laravel project with `laravel new`, install the required packages:  
     
   composer require filament/filament spatie/laravel-permission laravel/socialite socialiteproviders/microsoft laravel/horizon maatwebsite/excel  
     
3. `frontend/`: scaffold Next.js with `create-next-app` (TypeScript, App Router, Tailwind)  
4. `docker-compose.yml`: PostgreSQL, Redis, Mailhog (local test SMTP server) services  
5. Prepare `.env.example` files on both sides (with DB, Redis, S3, SMTP, Entra ID client id/secret placeholders)  
6. Git repo, `.gitignore`, basic CI scaffolding (GitHub Actions: lint \+ test placeholder)  
7. Place this document and any technical decisions produced in later phases into the `docs/` folder

**Output/Acceptance criteria:** Postgres and Redis come up with `docker-compose up`; `php artisan serve` and `npm run dev` run without errors.

---

## Phase 1 — One-Time Data Extraction from WPForms 

## This phase is the **last contact** with WordPress. It is done manually by a human (the project owner) from the WordPress admin panel; the agent must prepare a folder structure that expects the **output** of these steps (the exported files), and write a script to read and normalize them once the files arrive.

**What the agent needs to prepare:**

- A folder scaffold under `backend/storage/app/migration-source/` expecting the following structure:  
    
  migration-source/  
    
  ├── forms/              \# WPForms JSON export for each form (15 files)  
    
  ├── entries/             \# Existing submission data for each form (CSV/JSON export)  
    
  ├── files/               \# Uploaded documents (ID, diploma, portfolio, video)  
    
  └── design-reference/    \# HTML/CSS/screenshots of the Apply page  
    
- An "inventory check" Artisan command: `php artisan migration:verify-source` — verifies that all 15 forms are present in this folder, that each form has entries, and that file counts match the references in the JSON

**To be done by a human (the agent should document this, since it cannot access WordPress itself):**

- WPForms admin → JSON export for each form  
- Data dump from the `wp_wpforms_entries` \+ `wp_wpforms_entries_fields` tables  
- Downloading files from `/wp-content/uploads/wpforms/`  
- Extracting the design reference of the Apply page (screenshots \+ theme CSS files if available)

**Acceptance criteria:** The `migration-source/` folder is populated; the `migration:verify-source` command returns "OK" or a list of what's missing.

---

## Phase 2 — Database Schema

## The agent must write the following migrations for PostgreSQL. This schema is **permanent** — it is designed around our own needs, not WPForms' JSON structure.

### 2.1 Form engine

forms                (id, slug, title, category, status, version, created\_by, timestamps)

form\_pages           (id, form\_id, "order", title)                       \-- multi-step form support

form\_fields          (id, form\_id, page\_id, type, label, name,

                      options jsonb, validation jsonb,

                      conditional\_logic jsonb, "order", timestamps)

form\_submissions     (id, form\_id, uuid, status, source,                  \-- source: 'native' | 'migrated\_wpforms'

                      submitted\_at, edit\_token, edit\_token\_expires\_at, timestamps)

submission\_values    (id, submission\_id, field\_id, value jsonb)

submission\_files     (id, submission\_id, field\_id, original\_name,

                      stored\_path, mime\_type, size, disk)

### 2.2 CRM core

candidates           (id, submission\_id, full\_name, email, phone,

                      national\_id, nationality, source\_form\_id,

                      assigned\_to\_user\_id, pipeline\_stage\_id, timestamps)

pipeline\_stages      (id, name, "order", color, form\_category)

candidate\_notes      (id, candidate\_id, user\_id, note, timestamps)

candidate\_activities (id, candidate\_id, type, description, user\_id, timestamps)

                      \-- type: email\_sent | status\_changed | note\_added | assigned | manual\_log

communications       (id, candidate\_id, channel, direction, subject, body,

                      user\_id, sent\_at, status)

                      \-- channel: email|sms|call|manual ; direction: outbound|inbound

### 2.3 Trainer/evaluation module (tied to panel details in Section 4\)

evaluation\_assignments (id, candidate\_id, trainer\_user\_id, form\_id, status, assigned\_at)

evaluations             (id, assignment\_id, score numeric, comments text,

                         decision, submitted\_at)   \-- decision: pending|approved|rejected|waitlist

### 2.4 Permission model (alongside Spatie)

\-- spatie/laravel-permission sets up its own tables (roles, permissions, model\_has\_roles, etc.)

\-- Additional scope table:

user\_form\_scopes     (user\_id, form\_id)   \-- which forms' candidates a user can see

**Decision:** Roles must include at least: `super_admin`, `faculty_admin`, `department_secretary`, `trainer`, `student`. Permissions must be granular (e.g. `candidate.view.own`, `candidate.view.all`, `candidate.status.change`, `form.design`, `notification.manage`, `export.excel`) — the "fully-featured CRM" requirement mandates this.

### 2.5 Notification engine

notification\_templates  (id, name, channel, subject, body\_html, variables jsonb, language)

notification\_triggers   (id, form\_id, event, template\_id, recipient\_type,

                         recipient\_value, delay\_minutes, is\_active)

notification\_logs       (id, trigger\_id, candidate\_id, sent\_at, status, error)

### 2.6 Authentication

users                (id, name, email, entra\_id, role, is\_active, timestamps)

\-- entra\_id: the oid/sub claim returned from Microsoft Entra, unique

**Acceptance criteria:** All migrations run without error via `php artisan migrate`; basic factories/seeders (test data) are ready.

---

## Phase 3 — Authentication and Permission Model 

## **Entra ID integration (for both staff and student/trainer):**

- OAuth2 flow via `laravel/socialite` \+ `socialiteproviders/microsoft`  
- Azure App Registration details (tenant id, client id, client secret) go into `.env`  
- If the user doesn't exist on first login, they are automatically registered in the `users` table (matched by `entra_id`), and a default role is assigned (new student → `student`; new staff → awaits admin approval, no privileged role is granted automatically — **security decision: if role assignment doesn't come automatically from an Entra group, the user remains "roleless" on first login and a Super Admin assigns the role manually**)  
- **Decision:** If Azure AD already has security groups (e.g. "ARUCAD-Trainers", "ARUCAD-Students"), group membership can be pulled via the Microsoft Graph API for automatic role mapping — this is optional; the agent should start with manual role assignment first, and group sync can be left as a post-Phase-9 improvement.

**Filament panel guard configuration:** Each panel (Admin, Trainer, Student) is defined with its own Filament panel provider, uses the same `users` table, but access is restricted by role (see Phase 5).

**Acceptance criteria:** Login works via Entra ID; the user lands in the `users` table; a roleless user cannot enter any panel (they see a clear "you don't have access, contact admin" screen).

---

## Phase 4 — Laravel Backend: Form Engine and API

## **4.1 Own Form Builder (critical — the foundation of WordPress independence)** `FormBuilderResource` inside Filament:

- Add/remove/reorder fields (drag-drop)  
- Field type selection: text, textarea, email, phone, number, date, select, radio, checkbox, file-upload, richtext-display (informational text), section-break, page-break  
- Conditional logic editor ("If Field X \= Y, show Field Z")  
- Multi-page form management (via page breaks)  
- Form preview button (connects to the Next.js preview route in Phase 7\)

Once this builder is complete, **WPForms is no longer needed at all when a 16th form is required** — form design is done entirely from this panel.

**4.2 REST API (endpoints consumed by Next.js)**

GET   /api/forms/{slug}                      \-- return the form schema

POST  /api/forms/{slug}/submit                \-- new submission

GET   /api/submissions/{uuid}?token=...       \-- fetch existing data for editing

PATCH /api/submissions/{uuid}?token=...       \-- update

GET   /api/forms/{slug}/preview?token=...     \-- admin preview (does not create a record)

**4.3 File upload:** Chunked upload support (for large portfolio/video files), direct upload to S3 (presigned URL) — important for performance, to avoid routing files through the server.

**4.4 Queue/Horizon setup:** Notification delivery, file processing (e.g. generating image thumbnails) run in the background.

**4.5 Audit log:** All CRM actions (status changes, adding notes, form edits) are logged via `spatie/laravel-activitylog`.

**Acceptance criteria:** A test form works end-to-end (create → submit → update via edit-link) via Postman/Next.js.

---

## Phase 5 — Filament Panels

## A single Filament installation, with 3 separate panel providers:

### 5.1 Admin/CRM Panel (`/admin`)

- **Candidate management:** list, advanced filters (form/status/date/assignee), kanban/pipeline view, bulk actions, assignment  
- **Candidate detail page:** form data \+ inline file preview \+ notes \+ activity timeline all on one screen  
- **Communication history:** chronological timeline widget (emails, status changes, notes)  
- **Reporting:** per-form statistics, status distribution, funnel view, trend chart, Excel export  
- **Form Builder access:** Section 4.1  
- **Notification management access:** Section 6  
- **User/permission management:** role definition, form-scope assignment

### 5.2 Trainer Panel (`/trainer`)

- Sees only candidates assigned to them (filtered via `evaluation_assignments`)  
- Reviews candidate application details (form answers, uploaded files/portfolio/video)  
- Enters scores/notes, makes decisions (`approve / reject / waitlist`)  
- Views their own evaluation history  
- **Permission restriction:** A trainer cannot see another trainer's notes (configurable — Decision: hidden by default, Super Admin can enable)

### 5.3 Student Panel (`/student`)

- Sees the status of their own application (New → Under Review → Awaiting Exam → Accepted/Rejected)  
- Sees and can upload any missing documents  
- Sees the history of notifications sent to them  
- If they have multiple applications (e.g. both a university-entrance-exam form and a dormitory form), they see all of them from a single panel

**Acceptance criteria:** All three panels work at separate URLs; role-based access is correctly isolated (a trainer cannot enter the admin panel, a student cannot enter the trainer panel).

---

## Phase 6 — Notification Center (can run in parallel with Phase 5\)

- `NotificationTemplateResource`: WYSIWYG editor, variable support (`{{candidate_name}}`, `{{form_name}}`, `{{edit_link}}`, `{{status}}`), multi-language (TR/EN)  
- `NotificationTriggerResource`: **separately configurable** triggers per form \+ event (submission received / status change / reminder / manual send) — each independently active/inactive  
- SMTP settings: on a Filament settings page, with support for multiple sender addresses (different faculties can send from different addresses)  
- Delivery log (`notification_logs`), failed-delivery retry mechanism, test send before saving

**Acceptance criteria:** For one form, "submission received" and "status changed" triggers are configured and a real test email (via Mailhog locally) is delivered correctly with the right template.

---

## Phase 7 — Next.js Public Apply Interface (can start in parallel with Phase 4\)

- Next.js runs on **its own hosting** (makes no requests to WordPress whatsoever)  
- Using the design reference from Phase 1 (screenshots/CSS), the Apply page is rebuilt to be **pixel-identical** — component-based, but visually indistinguishable  
- Dynamic form rendering engine: converts field types into components based on the `GET /api/forms/{slug}` schema  
- Multi-step form flow, progress indicator (identical to the current design)  
- Preview mode: `/preview/[slug]?token=...` — triggered from the admin panel, does not create a record  
- Edit-link page: `/apply/[slug]/edit/[uuid]?token=...`  
- Domain: the `apply.arucad.edu.tr` DNS record is pointed from the WordPress server to this Next.js application at go-live

**Acceptance criteria:** One of the forms works end-to-end, indistinguishable in appearance from the WordPress version, connected to the real API.

---

## Phase 8 — One-Time WPForms JSON Import into the System This is **not** a permanent integration — it is a one-time data migration script.

php artisan migration:import-wpforms {json-path}

- Maps WPForms field types to our own `form_fields.type` enum (a one-time mapping table lives in code)  
- Converts WPForms' conditional logic format into our own `conditional_logic` jsonb format  
- Migrates entries data into `form_submissions` \+ `submission_values`, marking `source = 'migrated_wpforms'`  
- Uploads files to S3, updates `submission_files.stored_path`  
- Post-migration validation: cross-checks record counts and file counts

**Once this command has been run, WPForms' JSON format is no longer needed for anything** — from here on, forms are edited directly through the form builder in Phase 4.1.

**Acceptance criteria:** All 15 forms have been imported, are visible in the admin panel, and past submissions are listed as candidates in the CRM.

---

## Phase 9 — Testing, Phased Go-Live, WordPress Shutdown **Phased cutover order (low to high risk):**

1. Low risk: Workshop Week, Student Welcome, Dormitory/Apartment forms  
2. Medium risk: the 3 competition forms  
3. High risk (last): KKTC/DGS/YKS/Talent Exam enrollment forms

For each group:

- DNS/routing is pointed from WordPress to the new system (phased if per-form routing is possible; otherwise the entire domain cuts over at once and the WordPress side shows a "closed" message)  
- A 1-week parallel monitoring window (new system live, WordPress kept archived, quick rollback available)  
- Load testing (sudden traffic during enrollment periods — YKS/DGS results days are critical)

**Final step:** Once all groups have cut over and 2-3 weeks of stable operation have been observed, the WordPress server, database, and hosting are **shut down completely.**

---

## 3\. General Working Rules for the Agent

- Leave a working, tested state at the end of every phase; do not move to the next phase "halfway."  
- Do not change the table/field names specified in the database schema (Section 2\) — this document is the permanent reference.  
- Do not create any code dependency on WPForms outside of Phase 1 and Phase 8 (e.g. no code should make a runtime request to the WPForms API).  
- External service credentials (Entra ID, S3, SMTP, etc.) must always be read from `.env`, never hardcoded.  
- Write basic feature tests (Pest or PHPUnit) for every new Filament Resource and API endpoint.  
- If a technical decision is ambiguous, prioritize the items labeled "Decision" in this document; if the document is silent, prefer the option that requires the fewest assumptions and is most reversible, and note the reasoning in one sentence in `docs/decisions.md`.

