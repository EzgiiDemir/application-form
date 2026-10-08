# Release readiness audit — 2026-10-06

This is a point-in-time audit of the current local checkout, not a production sign-off. Existing uncommitted work in the root, backend and frontend repositories was preserved.

## Verified in this pass

- Backend: `php artisan test` — 368 passed, 1,226 assertions. All migrations are applied locally.
- Frontend: `npm test` — 129 passed; `npm run build` succeeded; `npm run lint` — 0 errors, 3 imported/dynamic image optimization warnings.
- `composer audit --locked` — no advisories. `npm audit --omit=dev` — no advisories after pinning `source-map-js` 1.2.2. The full npm audit still reports 5 high-severity findings in the ESLint toolchain; its suggested automatic fix would downgrade `eslint-config-next` across a breaking major version, so it was not applied.
- Local API, public catalog and home page returned HTTP 200. A published public form was visually checked at a 390 px mobile viewport; the form is usable and does not overflow horizontally.
- Detailed health information now requires a bearer token in production and is not cacheable. Public form schema responses carrying submission tokens are also marked `Cache-Control: no-store, private`.
- Notification dispatch now reserves its deduplication key before queueing; a replay of a sent job is a no-op. Two older local failed jobs have the duplicate-key exception and were **not** blindly retried or deleted.

## Release blockers and decisions

1. **WordPress entry reconciliation:** 118 forms are imported, but the connected legacy database has no usable WPForms entry tables, so none of the 118 legacy counts can be verified. Do not retire WordPress or claim full entry parity until the correct read-only source or complete entry export is provided and the cutover checklist passes.
2. **Publication review:** 18 of 118 imported forms are published, while 17 public cards are present. Review the intentionally unpublished forms and the currently open `ARUCAD TEST` public card with the form owner; neither should be changed by an automated clean-up without that decision.
3. **Historical data quality:** the read-only scan found 1,146 open observations in 291 submissions (including phone prefixes, dates, duplicate preferences and three identity-number flags). These are historical records, so corrections require source-backed review rather than an automatic rewrite.
4. **Legacy content assets:** at least 11 form fields still refer to `apply.arucad.edu.tr/wp-content/uploads/` images. One sampled hero image was ~3 MB and became visible several seconds after the mobile form loaded. Copy and resize the original images into managed storage before retiring WordPress; keep the source layout and text unchanged.
5. **Environment verification:** the local health check is healthy except for a missing scheduler heartbeat, expected while the scheduler is not running locally. Production must verify its own scheduler, queue worker, mail, storage and backup/restore configuration before go-live.

The migration cutover checklist remains **blocked**; 744 attachments have been copied and 24 legacy paths resolve, but those facts do not replace entry-count reconciliation or publication review.
