---
name: wp-module-runtime
title: Development
description: Lint, test, and workflow.
updated: 2025-03-18
---

# Development

## Linting

- **PHP:** `composer run lint` (PHPCS), `composer run fix` (PHPCBF). Uses `phpcs.xml` and `newfold-labs/wp-php-standards`.

## Testing

- **WPUnit (Codeception):** `composer run test` runs the `wpunit` suite.
- **Coverage:** `composer run test-coverage`; then open `tests/_output/html/index.html`.

Tests require the loader and WordPress as dev dependencies; the suite bootstraps the runtime and container as needed.

## JS build

The `src/index.js` and `scripts/webpack.config.js` are used to build a small runtime helper. Check package.json for build scripts if the host expects a built asset; otherwise the PHP-injected `window.NewfoldRuntime` is sufficient for the app.

## Day-to-day workflow

1. Make changes in `includes/` or `bootstrap.php` (or `src/` for JS).
2. Run `composer run lint` and `composer run test` before committing.
3. When adding or changing runtime keys or the filter, update [integration.md](integration.md) and [overview.md](overview.md) as needed.

## Version and release

This package is versioned and released to the Newfold Satis repository. When cutting a release, update **docs/changelog.md** with the changes for that version.
