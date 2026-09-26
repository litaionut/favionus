---
name: wp-module-link-tracker
title: Development
description: Lint, test, and workflow.
updated: 2025-03-18
---

# Development

## Linting

- **PHP:** `composer run lint`, `composer run fix`. Uses `phpcs.xml` and `newfold-labs/wp-php-standards`.

## Testing

- **Codeception wpunit:** `composer run test`, `composer run test-coverage`. Open `tests/_output/html/index.html` for coverage.

## Build

The module expects a built JS asset at `build/` (see `NFD_LINK_TRACKER_BUILD_DIR`). Build tooling may live in the repo or be documented in the root; ensure the build output exists when testing enqueue behavior.

## Workflow

1. Make changes in `includes/` or `bootstrap.php`.
2. Run `composer run lint` and `composer run test` before committing.
3. When changing the filter or public API, update [integration.md](integration.md) and [overview.md](overview.md). When cutting a release, update **docs/changelog.md**.
