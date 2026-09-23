# WPForms one-time export checklist

Place the manual exports in `backend/storage/app/migration-source/` before
running the migration commands, **or** configure the live WordPress connection
under **Admin → Entegrasyonlar → WPForms** and import forms directly from the
legacy site.

- `forms/`: one WPForms JSON export per form (15 expected files)
- `entries/`: entry CSV or JSON export per form
- `files/`: files downloaded from `wp-content/uploads/wpforms/`
- `design-reference/`: public Apply screenshots, HTML and CSS

Run `php artisan migration:verify-source` to check the inventory. No runtime
integration with WordPress is included; this directory is only consumed by the
one-time import workflow.

## Import and incremental cutover

Import an export with its stable source identifiers:

```bash
php artisan migration:import-wpforms form-137.json \
  --entries=form-137-entries.json \
  --site=https://apply.arucad.edu.tr \
  --form-id=137
```

The importer is idempotent. Re-running a full export, or importing a smaller
incremental export before cutover, updates records matched by source site plus
WPForms form/entry ID instead of creating duplicates. Original form, field and
entry IDs, timestamps and source metadata are retained. File URLs are recorded
as `reference_only` until the corresponding binary is copied to managed object
storage.

Unknown add-on field types are imported as `unsupported_legacy_field` with the
raw definition attached for migration review. Legacy notifications and
confirmations are retained as metadata with `needs_review`; they are never
activated automatically.

Recommended cutover order:

1. Run the initial full import.
2. Continue testing while WordPress remains the legacy write source.
3. Re-run with the latest full export or an incremental entry export.
4. Freeze legacy submissions and run the final import.
5. Validate counts and file-copy status before pointing traffic at ARUCAD Apply.
