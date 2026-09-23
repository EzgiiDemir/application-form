# Critical Test Strategy — 20 Production Flows

> Maps the minimum automated scenarios from Aşama 17 to existing PHPUnit coverage.
> Run full suite: `cd backend && php artisan test`

## Coverage matrix

| # | Scenario | Status | Test file | Test method |
|---|----------|--------|-----------|-------------|
| 1 | Public application oluşturma | ✅ Covered | `PublicSubmissionTest` | `test_a_published_form_can_be_submitted_and_retrieved_with_its_edit_token` |
| 2 | Duplicate submit engelleme | ✅ Covered | `PublicApplicationFeaturesTest` | `test_idempotent_submit_does_not_create_duplicate_submissions` |
| 3 | Edit token authorization | ✅ Covered | `PublicSubmissionTest` | `test_a_submission_cannot_be_read_with_an_invalid_edit_token` |
| 4 | Expired token | ✅ Covered | `PublicApplicationFeaturesTest` | `test_expired_edit_token_is_rejected` |
| 5 | Unauthorized file access | ✅ Covered | `SecureFileManagementTest` | `test_unauthorized_file_download_is_forbidden` |
| 6 | Application status transition | ✅ Covered | `ApplicationLifecycleTransitionTest` | `test_a_valid_stage_transition_updates_submission_and_writes_history` |
| 7 | Invalid status transition | ✅ Covered | `ApplicationLifecycleTransitionTest` | `test_an_invalid_stage_transition_is_rejected` |
| 8 | Faculty scope | ✅ Covered | `ResourceScopePolicyTest` | `test_category_scope_grants_access_to_all_forms_in_category` |
| 9 | Department scope | ✅ Covered | `ResourceScopePolicyTest` | `test_direct_form_scope_limits_access_to_specific_form` |
| 10 | Jury yalnızca assigned application | ✅ Covered | `CriticalFlowsTest` | `test_trainer_cannot_access_unassigned_candidate` |
| 11 | Student başka application göremez | ✅ Covered | `CriticalFlowsTest` | `test_student_cannot_access_other_students_application` |
| 12 | Jury review submit | ✅ Covered | `JuryEngineTest` | `test_rubric_weighted_score_is_computed_on_submit` |
| 13 | Jury review lock | ✅ Covered | `JuryEngineTest` | `test_locked_evaluation_cannot_be_resubmitted` |
| 14 | Multiple jury aggregation | ✅ Covered | `JuryEngineTest` | `test_multi_jury_aggregation_calculates_average_and_consensus` |
| 15 | Form version değişince eski submission bozulmaz | ✅ Covered | `FormVersioningTest` | `test_publishing_a_new_version_does_not_change_existing_submission_field_links` |
| 16 | Notification queued | ✅ Covered | `NotificationEngineTest` | `test_application_submitted_event_queues_notification_job` |
| 17 | Failed notification retry | ✅ Covered | `NotificationEngineTest` | `test_failed_notification_can_be_retried` |
| 18 | Document request | ✅ Covered | `StudentPortalTest` | `test_staff_can_create_document_request_and_student_can_respond` |
| 19 | Student document upload | ✅ Covered | `StudentPortalTest` | `test_staff_can_create_document_request_and_student_can_respond` |
| 20 | Audit log generation | ✅ Covered | `ActivityAuditSeparationTest` | `test_audit_log_service_writes_immutable_audit_entries` |

**Result: 20/20 scenarios covered** (18 pre-existing + 2 added in `CriticalFlowsTest`).

---

## Additional regression suites (beyond the 20)

These suites protect production features built in Aşamalar 2–15:

| Suite | Focus |
|-------|-------|
| `PrivacyComplianceTest` | KVKK consent, anonymization, retention |
| `SecurityHeadersTest` | HTTP security headers |
| `ApplicationReportingTest` | Scoped reporting metrics |
| `RealtimeArchitectureTest` | Broadcast channels, polling fallback |
| `SystemHealthTest` | Health endpoints, correlation ID, ops metrics |
| `CrmProductionTest` | Merge, export, tasks |
| `AuthorizationRoutingTest` | Panel routing by role |

---

## CI recommendation

```yaml
# Minimum gate before deploy
- run: php artisan test
- run: php artisan framework:version-audit --json
```

Optional next steps (not implemented):

- Playwright E2E for `FormRenderer` submit flow against staging API
- Contract tests for `/api/forms/{slug}` schema stability across form versions

---

## Running targeted critical flows

```bash
php artisan test --filter=CriticalFlowsTest
php artisan test --filter="PublicSubmissionTest|PublicApplicationFeaturesTest|ResourceScopePolicyTest|JuryEngineTest|StudentPortalTest|ActivityAuditSeparationTest"
```
