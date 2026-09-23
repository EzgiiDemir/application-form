# ARUCAD Apply

WordPress-independent application and CRM monorepo.

## Local setup

1. Start PostgreSQL 16+ (the Windows development profile uses the local
   `postgresql-x64-18` service). The application database is `arucad_apply`.
   Docker users can instead run `docker compose up -d postgres`.
2. In `backend/`, review `.env` (created from `.env.example`), then run
   `php artisan migrate --seed`.
   - Native Windows without Redis: set `QUEUE_CONNECTION=database` and run
     `php artisan queue:work`.
   - Linux/WSL/Docker with Redis: set `QUEUE_CONNECTION=redis`,
     `REDIS_CLIENT=phpredis`, `OPERATIONS_HORIZON_ENABLED=true`, then run
     `php artisan horizon`.
3. In a second backend terminal run
   `php artisan serve --host=127.0.0.1 --port=8010`.
   Port 8010 is intentionally fixed so this project cannot accidentally use
   another Laravel service already bound to port 8000.
4. In `frontend/`, copy `.env.example` to `.env.local`, set the API URL if
   needed, then run `npm run dev`. It serves on port 3000.

The local connection chain is:

`Next.js :3000 → Laravel API :8010 → PostgreSQL :5432 / arucad_apply`

The Phase 1 platform includes schema-driven forms, an extensible field registry,
signed public submission sessions, form metadata/actions, and a dedicated admin
Entries module.

Verify it with `http://127.0.0.1:8010/health/detailed`; the database, queue,
storage and overall checks should all report `ok`.

### Signing in locally

Microsoft sign-in needs a configured Entra tenant. Without one it can only fail
with `AADSTS900144`, so `/auth/microsoft/redirect` does not call Microsoft at
all: it returns to `/login`, which hides the button and offers the seeded demo
accounts instead — one click each, no password typed:

| Account | Email | Lands on |
| --- | --- | --- |
| Admin | `admin@arucad.edu.tr` | `/admin` |

The same accounts work with the email/password form; the password is
`password`. Both routes are gated on `APP_ENV=local` **and**
`LOCAL_PASSWORD_LOGIN` / `LOCAL_DEMO_MODE`, which `.env.example` ships as
`false`. They return 404 anywhere else — never enable them in production.

Reach the site on whichever loopback name you prefer. `localhost` and
`127.0.0.1` are different hosts to a browser, so the API is called on the same
name the page was opened with; mixing them would scope the session cookie to one
host and silently sign the visitor out on the other.

## Routes

Public (Next.js):

| Route | Purpose |
| --- | --- |
| `/` | Application catalog: audience tabs, categories and form cards, all database-driven |
| `/apply/{form-slug}` | The form runtime |
| `/{legacy-path}` | Catch-all resolver for historic WordPress URLs — renders in place, or 301s |
| `/resume/{token}` | Exchanges a secure resume token for the draft it unlocks |
| `/preview/{form-slug}` | Time-limited administrator preview |

Applicant portal (Next.js, signed in; this is not a Student account type):

`/student`, `/student/applications`, `/student/applications/{uuid}`,
`/student/documents`, `/student/notifications`, `/student/profile`,
`/student/settings`.

Visual form builder (Next.js, staff):

`/builder` lists the forms a member of staff may edit; `/builder/forms/{uuid}`
is the palette / canvas / settings builder. It autosaves into a draft version
and never edits a published version in place.

Staff panel (Filament): `/admin`. Every sign-in redirects to Microsoft Entra
login and is gated on granular permissions, not Student/Trainer account types.
Roles and form visibility are assigned from **Admin ve Yetkilendirme → Kullanıcılar ve roller**.

KVKK requests are created by verified staff and processed through the audited
admin workflow; see [docs/KVKK_WORKFLOW.md](docs/KVKK_WORKFLOW.md).

The admin panel's **Public site** group controls the homepage: audiences,
categories, form cards, announcements, navigation and legacy redirects. Adding
an application section is an admin task, not a frontend deploy.

## API

Laravel owns `/api/v1`:

* `GET /api/v1/public/catalog` — the whole public homepage
* `GET /api/v1/public/resolve?path=…` — what a public URL should render, or where it redirects
* `GET /api/v1/public/redirects` — the redirect map Next.js middleware caches
* `GET /api/v1/public/forms/{slug}` and `/availability`
* `POST /api/v1/public/forms/{slug}/applications` and `/applications/submit`
* `GET|PATCH /api/v1/applications/{uuid}`, `/save`, `/submit`, `/resume-link`, `/files`
* `GET /api/v1/resume/{token}`
* `GET /api/v1/me/…` — the applicant's own dashboard, applications, documents, notifications
* `GET|POST|PATCH /api/v1/forms…`, `/schema`, `/publish`, `/versions`, `/blocks` — the builder

The unversioned `/api/...` routes remain for clients shipped before `/api/v1`
existed.

## Tests

```
cd backend  && php artisan test    # 232 tests
cd frontend && npm test            # 96 tests  (Vitest + Testing Library)
cd frontend && npm run test:e2e    # 23 specs  (Playwright, needs the API running)
```

All three run in CI on every push. The browser suite is the only one that can
catch a CORS failure, a broken drag-and-drop or a session cookie scoped to the
wrong hostname, because all three are enforced by the browser and nowhere else.
It needs the API on `http://127.0.0.1:8010` and starts the frontend itself on
port 3101.

## Checking an application's status

Applicants have no account. On submission the platform issues a tracking token
and the applicant reaches `/application-status/{token}`, which shows the
reference, the current status and a timeline.

The token is the whole credential, so: it is the only thing in the URL (never a
database id, email or national id), it is stored hashed, it expires and can be
revoked, it cannot be used to edit the application, and the page is `noindex`.
Unknown, revoked and expired tokens all answer identically, so nobody can probe
which tokens exist.

## Reporting and analytics

| Screen | Purpose |
| --- | --- |
| Reporting → Raporlar | Filters, summary cards, and a group-by report builder across 16 dimensions with CSV download |
| Analytics → Form funnel | Views → started → drafts → submitted, drop-off by page, field friction, incomplete ageing |
| Reporting → Exports | Queued CSV/XLSX application exports |

## Migration from WordPress

The migration module lives under **Migration** in `/admin`:

| Screen | Purpose |
| --- | --- |
| WPForms import | Compatibility scan, import from an export file or the WordPress database, file copy, reconciliation and the cutover checklist |
| Entry import | CSV/XLSX fallback with a confirmed column mapping |
| Import history | Every run, with its counts and errors |
| Mapping issues | Anything that could not be resolved, by name |

The same steps are available on the command line:

```
php artisan migration:cutover checklist        # go/no-go, non-zero while work remains
php artisan migration:cutover reconcile        # expected vs imported, per form
php artisan migration:cutover copy-files       # copy outstanding attachments
php artisan migration:cutover aliases --apply  # legacy URLs (omit --apply to preview)
php artisan migration:cutover resume-links --apply
```

Reading from the legacy database needs the `WORDPRESS_DB_*` values in `.env`;
the connection is read-only and WordPress is never modified. Copying
attachments needs either `WORDPRESS_UPLOADS_PATH` (a mounted copy of
`wp-content/uploads`, preferred) or `WORDPRESS_UPLOADS_URL` while the old site
is still serving.

## Scheduled work

One cron entry runs Laravel's scheduler; everything else is defined in code:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

That covers reminders, automations (`automations:run`), abandonment detection,
retention enforcement and export purging. Staff never see or write cron syntax.

## Required production configuration

Set PostgreSQL, Redis, S3-compatible storage, SMTP and Entra credentials in
the production backend environment. Horizon requires Linux because its workers
depend on `pcntl` and `posix`; native Windows development uses the database
queue worker instead.
