# Framework Version Audit — September 2026

> **Status:** Read-only assessment. No framework upgrades were applied as part of this audit.
> Run `php artisan framework:version-audit` for live installed versions.

## Executive summary

| Track | Current (repo) | Target evaluated | Recommendation |
|-------|----------------|------------------|----------------|
| PHP | ^8.2 (runtime may be 8.3+) | 8.3+ for Laravel 13 | Upgrade infra before Laravel 13 |
| Laravel | 12.x (`^12.0`) | 13.x | **Low–medium effort** — schedule after go-live stabilization |
| Filament | 3.3.x | 5.x (via 4.x) | **High effort** — defer until dedicated maintenance window |
| Livewire | 3.8.x (Filament dep) | 4.x (required by Filament 5) | Upgrade only with Filament |
| Next.js | **16.3.4** | 16.x | **Already on target** — no 14→16 migration needed |
| React | 19.2.x | 19.x | Aligned with Next 16 |

The original project brief referenced Next.js 14; the repository already runs **Next.js 16 App Router**. Treat frontend migration as **complete**; focus on patch-level updates and API contract stability.

---

## 1. Laravel 12 → Laravel 13

### Current state

- `composer.json`: `"laravel/framework": "^12.0"`
- Laravel 12 LTS security support until **February 2027**
- Application uses Laravel 12 conventions: `bootstrap/app.php` middleware registration, typed properties, queue/events, Filament panels

### Blockers

| Blocker | Severity | Mitigation |
|---------|----------|------------|
| PHP 8.3 minimum | **Hard** | Update Docker/CI/production PHP images before composer bump |
| `VerifyCsrfToken` rename → `PreventRequestForgery` | Low | 4 references in routes + panel providers; alias exists in L13 but should be updated |
| Cache `serializable_classes` default | Medium | Audit if any code stores PHP objects in cache; configure allow-list if needed |
| Cache/Redis key prefix format change | Medium | Pin `CACHE_PREFIX`, `REDIS_PREFIX`, `SESSION_COOKIE` in production `.env` before upgrade |
| PHPUnit ^11 → ^12 | Low | Update dev dependency with framework bump |
| Custom contract implementations | None | No custom cache/session drivers detected |

### Incompatible / verify plugins

| Package | Locked | L13 expectation |
|---------|--------|-----------------|
| `laravel/horizon` | ^5.29 | Verify ^5.x L13 compatibility before upgrade |
| `laravel/reverb` | ^1.11 | Verify latest 1.x supports L13 |
| `laravel/socialite` | ^5.16 | Generally compatible |
| `spatie/laravel-permission` | ^6.16 | Compatible |
| `spatie/laravel-activitylog` | ^4.10 | Compatible |
| `maatwebsite/excel` | ^3.1 | Compatible (dev/export feature) |
| `socialiteproviders/microsoft` | ^4.10 | Compatible |
| `filament/filament` | ^3.3 | **Supports Laravel 12 today**; Filament 3 is not the L13 target — plan Filament separately |

### Breaking changes (expected impact)

1. **PHP 8.3** — only hard blocker.
2. **CSRF middleware rename** — update imports in:
   - `routes/web.php`
   - `app/Providers/Filament/AdminPanelProvider.php`
   - `app/Providers/Filament/TrainerPanelProvider.php`
   - `app/Providers/Filament/StudentPanelProvider.php`
3. **Cache serialization hardening** — unlikely to affect this app (array/database cache in tests; Redis in prod).
4. **MySQL DELETE+JOIN+LIMIT** — not applicable (PostgreSQL target).

### Estimated code impact

- **Direct code changes:** ~5–15 files (middleware imports, config publishes, composer constraints)
- **Test requirement:** Full `php artisan test` (87 tests) + manual smoke: 3 Filament panels, public API submit, Entra callback (staging)
- **Effort:** 0.5–1.5 dev-days with CI green

### Migration order

1. PHP 8.3 on CI/staging/prod
2. Branch `upgrade/laravel-13`
3. `composer require laravel/framework:^13.0 --with-all-dependencies`
4. Apply official [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade)
5. Run tests + staging smoke
6. Merge after enrollment window if risk-averse

### Rollback strategy

- Keep `composer.lock` from pre-upgrade tagged commit
- Redeploy previous container/build artifact
- Pin env cache/session prefixes if already upgraded (document values pre-upgrade)

---

## 2. Filament 3 → Filament 5

### Current state

- `filament/filament` ^3.3, Livewire **3.8.x**
- **52 PHP files** under `app/Filament/` across 3 panels (admin, trainer, student)
- **9 custom Blade views** under `resources/views/filament/`
- Custom concerns: `InteractsWithAdaptivePolling`, `ListensForCandidateBroadcasts`
- No third-party Filament plugins installed

### Blockers

| Blocker | Severity | Notes |
|---------|----------|-------|
| Must pass through **Filament 4** | **Hard** | No direct v3→v5 path |
| **Livewire 4** required | **Hard** | Filament 5 bundles LW4; custom blades must be retested |
| **Tailwind CSS 4** required | Medium | Filament ships assets; review any custom CSS in blade widgets |
| Two-step automated scripts | Process | `filament/upgrade` for v4, then v5 |
| Panel providers (×3) | Medium | Upgrade script rewrites namespaces/APIs |

### Incompatible plugins

**None in use** — reduces risk significantly. All UI is first-party Filament resources/pages/widgets.

### Breaking changes (by layer)

**Filament 3 → 4** (largest change):

- Form/Table/Infolist namespace and API changes
- Resource base class changes
- Theme/asset pipeline updates
- Automated `vendor/bin/filament-v4` (or equivalent) required

**Filament 4 → 5**:

- Minimal Filament-specific breaks; primarily **Livewire 4** adoption
- `wire:model` no longer captures child events without `.deep`
- `wire:transition` modifiers removed (View Transitions API)
- Livewire component tags must be self-closed

**Project-specific retest areas:**

- `OperationsDashboard`, `ReportsDashboard` custom pages
- `CandidateTimelineWidget`, `CandidateAuditTrailWidget` custom blades
- Adaptive polling + broadcast Livewire hooks
- Student document upload relation manager (`TemporaryUploadedFile`)

### Estimated code impact

- **Automated script:** majority of resource/page rewrites
- **Manual:** ~9 blade views, 3 panel providers, 2 polling traits, theme publish
- **Effort:** 2–4 dev-days + 1 day QA across panels
- **Tests:** Full PHPUnit + manual jury/student/admin flows

### Migration order

1. Complete Laravel 13 upgrade first (Filament 5 requires Laravel 11.28+, L13 preferred)
2. Branch `upgrade/filament-4`
3. Run Filament v4 upgrade script; fix panel providers
4. Full regression
5. Branch `upgrade/filament-5` + Livewire 4 guide
6. Retest realtime polling and file uploads

### Rollback strategy

- Filament upgrade is **not** reversible via composer alone once v4 APIs are committed
- Maintain long-lived `production/filament-3` branch until v5 is validated in staging
- Snapshot staging DB for panel QA

---

## 3. Next.js 14 → Next.js 16

### Current state

```json
"next": "16.3.4",
"react": "19.2.8"
```

Frontend structure:

- App Router: `src/app/` (landing, apply, edit, preview, login)
- Core component: `FormRenderer.tsx` (multi-step, conditional logic, consent)
- API client: `src/lib/api.ts`
- Minimal `next.config.ts` (no custom webpack)

### Assessment

**Migration already complete.** The repository is ahead of the original brief (Next.js 14).

If upgrading from a hypothetical Next 14 codebase, typical steps would have been:

1. React 18 → 19 peer dependency alignment
2. App Router stable APIs (async params in Next 15+)
3. ESLint `eslint-config-next` version pin
4. Retest `FormRenderer` file upload + edit token flows

### Remaining maintenance (not migration)

- Keep `eslint-config-next` aligned with `next` pin
- Monitor React 19 + Next 16 security advisories
- E2E smoke: apply submit, edit link, preview mode, privacy consent checkbox

### Blockers for future Next 17+

None identified; decouple from backend API versioning.

### Rollback

Pin `package-lock.json` / `pnpm-lock.yaml`; frontend deploy is independent of Laravel upgrades.

---

## 4. Combined migration order (recommended)

```
Phase A — Infrastructure
  └─ PHP 8.3+ everywhere

Phase B — Laravel 13 (maintenance window #1)
  └─ composer bump, middleware/config, 87 tests green

Phase C — Filament 4 (maintenance window #2)
  └─ automated upgrade, panel QA

Phase D — Filament 5 + Livewire 4 (same window as C or immediately after)
  └─ custom blade/widget retest

Phase E — Next.js
  └─ patch updates only (already on 16)
```

**Do not combine B+C+D in a single release** unless enrollment period is closed.

---

## 5. Test requirements before any upgrade

| Gate | Command / action |
|------|------------------|
| Automated backend | `php artisan test` (87 tests) |
| Version audit | `php artisan framework:version-audit` |
| Critical flows | See `docs/test-strategy.md` (20 scenarios) |
| Manual smoke | Public submit, edit token, admin candidate edit, jury evaluation, student document upload |
| Staging | Entra SSO, S3 upload, Redis queue, `/health/detailed` |

---

## 6. Decision log entry

See `docs/decisions.md` — framework upgrades deferred until post-go-live stabilization; Next.js 16 already adopted.
