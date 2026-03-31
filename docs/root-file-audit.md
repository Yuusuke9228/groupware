# Root File Audit

Date: 2026-03-31
Scope: repository root (`groupware/`) files outside the current MVC layout.

## Summary

- Runtime entrypoint is `public/index.php`.
- Namespaced runtime classes resolve to canonical paths such as `Core/*`, `Controllers/*`, `Models/*`, `views/*`.
- Root-level mirror files exist and can cause confusion during maintenance.

## Method

1. Enumerated tracked root files with `git ls-files`.
2. Verified runtime boot and autoload path in `public/index.php`.
3. Compared root file blobs against canonical paths (`git rev-parse HEAD:<path>`).
4. Checked direct references in tracked code paths.

## Findings

| Root file | Classification | Canonical / Note |
|---|---|---|
| `Controller.php` | Legacy mirror (different content) | `Core/Controller.php` |
| `Router.php` | Legacy mirror (different content) | `Core/Router.php` |
| `WorkflowController.php` | Legacy mirror (different content) | `Controllers/WorkflowController.php` |
| `form_fields.php` | Exact duplicate | `views/user/form_fields.php` |
| `request_view.php` | Exact duplicate | `views/workflow/request_view.php` |
| `header.php` | Legacy mirror (different content) | `views/layouts/header.php` |
| `view.php` | Legacy mirror candidate | `views/user/view.php` |
| `reset_admin_password.php` | Standalone admin utility | Not used by normal router flow |
| `tmp_smoke.js` | QA script | Playwright smoke |
| `tmp_suite.js` | QA script | Playwright suite |
| `tmp_task_smoke.js` | QA script | Playwright task smoke |
| `tmp_workflow_probe.js` | QA script | Playwright workflow probe |
| `tmp_daily_report_smoke.js` | QA script | Playwright daily-report smoke |
| `tmp_xw.js` | QA script | Playwright ad-hoc probe |

## Notes

- This audit avoids destructive cleanup in the same PR to prevent accidental runtime breakage.
- Recommended next cleanup PR:
  - Move `tmp_*.js` scripts to `scripts/qa/`.
  - Remove root mirror files after confirming no runtime/ops dependency in staging.
  - Keep `reset_admin_password.php` only if intentionally documented as emergency utility, otherwise move to `scripts/admin/`.
