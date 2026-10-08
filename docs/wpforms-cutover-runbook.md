# WPForms migration and cutover runbook

## Current verified baseline (2026-09-29)

- Form `1788` is published on native version 4. Version 5 remains a draft.
- 229 legacy applications and 744 legacy attachments were imported and reconciled.
- All 744 imported attachments passed ClamAV 1.5.4; the database contains 745 clean files including the native test upload.
- Legacy candidate identity numbers are encrypted at rest. The plaintext column contains zero values.
- Retention deadlines were assigned to all 229 imported candidates. No privacy consent was fabricated: the export contained no provable policy-version acceptance.
- The other 117 imported forms remain drafts and have central validation profiles applied to their latest draft versions.
- Every application now has a unique, public-facing 8-character ID. The longer legacy reference is retained internally for audit and reconciliation.
- The published form 1788 legacy path has an active 301 alias. The remaining draft forms intentionally have no public redirect target yet.

## Daily admin workflow

1. Open **Forms → Import from WPForms**.
2. For a local export, choose the WPForms JSON file and select **Import selected JSON template**. Remote WordPress imports remain available on the same page.
3. Open the imported draft in the builder. Use the top tabs for **Fields**, **Workflow**, **Notifications**, and **Form settings**.
4. Confirm imported section titles, HTML/content, page breaks, field options, validation and smart logic in Preview.
5. Configure form-specific WhatsApp support under **Form settings → General**. The public button remains hidden until a valid number is entered.
6. Publish only after the validation and notification checks are complete.
7. Open **Applications → Entries**, select a form, search the registrant, and use the column chooser to keep only the required columns. Operational exports are XLSX.
8. Manage old paths under **Forms & content → Redirects**. Generate publication-backed aliases with `php artisan migration:cutover aliases --apply`.

Imported HTML is sanitized before storage. Likert matrices, wet-signature canvases and HTML/content fields are available in the compact field palette. WPForms field options may arrive as either lists or keyed objects; both shapes are normalized by the builder.

## OP Center integration contract

`op_center` is reserved as a supported submission integration and can already retain per-form field mappings. Live delivery must remain disabled until the service owner provides the base URL, authentication method/credential, endpoint paths, request schema, response examples, timeout/retry rules and a test environment. Generic signed/retrying webhooks can be used when OP Center accepts event callbacks.

## Repeatable import order

1. Upload and scan the WPForms JSON export in **System → Migration console**.
2. Review unsupported/ambiguous field mappings before import.
3. Import forms as drafts. Do not publish them as a side effect of migration.
4. Export a form's entries from WPForms as CSV/XLSX and preview it in the migration console.
5. Confirm column mapping. File columns such as `Portfolio: File #1` are mapped to their base field automatically.
6. Choose an explicit blank policy:
   - `ignore`: a blank source cell keeps the existing answer;
   - `clear`: a blank source cell clears the stored answer;
   - `reject`: the row is rejected for manual review.
7. Import attachment binaries from the WPForms ZIP or configured source, then reconcile row IDs, field values, metadata, file counts and SHA-256 hashes.
8. Update ClamAV signatures and run the bulk scan before allowing downloads.
9. Run privacy retention backfill and the data-quality scan.
10. Preview, submit a test application, verify API/admin/DB visibility, then publish the selected version.

## Operator commands

Run from `backend/`:

```powershell
php artisan migrate --force
php artisan forms:validation-profiles
php artisan forms:validation-profiles --apply
php artisan privacy:backfill-legacy
php artisan privacy:backfill-legacy --apply
php artisan data-quality:scan
php artisan files:scan-bulk --apply --timeout=3600
php artisan operations:scheduler-heartbeat
php artisan operations:health --json
php artisan migration:cutover aliases
php artisan migration:cutover aliases --apply
php artisan test
```

`forms:validation-profiles`, `privacy:backfill-legacy`, and `files:scan-bulk` provide a dry run unless `--apply` is passed. Imported source values are never silently normalized; suspected phone/date/identity/preference/portfolio issues appear under **System → Data quality** for a human decision.

## File security

Production is fail-closed. Only files whose `scan_status` is `clean` can be downloaded. `pending`, `failed`, `skipped`, and `infected` files are blocked at both URL issuance and local streaming endpoints.

For a production host, install ClamAV independently, keep `freshclam` scheduled, and configure:

```dotenv
MALWARE_SCANNER_DRIVER=clamav
CLAMAV_BINARY=C:\Program Files\ClamAV\clamscan.exe
CLAMAV_DATABASE_DIRECTORY=C:\Program Files\ClamAV\database
MALWARE_SCAN_TIMEOUT_SECONDS=120
```

The portable local development engine is intentionally excluded from Git.

## Validation profiles

The central profile adds server-side and client-side limits for names, email, phone, identity/passport values, dates, graduation year, required consent and duplicate preferences. Existing stricter form-specific rules win. Published versions are immutable; bulk application targets only the latest version of draft forms.

## Items that require external input or a business decision

- Entry exports for the other 117 forms. Empty source data is not invented.
- The correct legacy WordPress database if global WPForms table reconciliation is required.
- A decision for each open data-quality observation before changing historical evidence.
- Confirmation before deleting the one native test/draft application.
- SMTP recipient/credentials for a real delivery test, production host settings, and a backup/restore target.
- WhatsApp destination number and optional pre-filled support message.
- OP Center API contract and test credentials. Secrets must be entered through encrypted integration settings, not committed to source control.
- The legacy public page URL inventory (WordPress sitemap/export/database mapping) and a publication decision for each of the 117 drafts before their redirects can be generated.
- Review of duplicate-title forms and the unpublished version 5 before merging or deleting either.
