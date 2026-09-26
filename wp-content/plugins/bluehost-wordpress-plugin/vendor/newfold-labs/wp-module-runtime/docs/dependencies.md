---
name: wp-module-runtime
title: Dependencies
description: Composer and npm dependencies.
updated: 2025-03-18
---

# Dependencies

## Runtime expectations

This package has **no production Composer dependencies**. It expects to run in an environment where:

- **Newfold Module Loader** is loaded and the container has been set via `NewfoldLabs\WP\ModuleLoader\container( $container )`.
- The **container** provides at least:
  - `capabilities` – Object with an `all()` method returning an array of capability name => boolean.

Other modules (e.g. wp-module-context) and the host plugin add data to the runtime via the `newfold_runtime` filter. The host is responsible for ensuring the loader and container are set up before the runtime runs.

## Dev dependencies

- **newfold-labs/wp-php-standards** – PHPCS rules.
- **newfold-labs/wp-module-loader** – Required for tests (container, loader).
- **johnpbloch/wordpress** – WordPress core for WPUnit.
- **lucatume/wp-browser** – Codeception and WPUnit.
- **phpunit/phpcov** – Coverage.
- **wp-cli/wp-cli-bundle**, **wp-cli/i18n-command** – Optional tooling.
