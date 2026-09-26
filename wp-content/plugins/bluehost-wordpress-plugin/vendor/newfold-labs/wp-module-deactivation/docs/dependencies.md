---
name: wp-module-deactivation
title: Dependencies
description: Composer and npm dependencies.
updated: 2025-03-18
---

# Dependencies

## Runtime

| Package | Purpose |
|---------|---------|
| **newfold-labs/wp-module-data** | Used for context or data-related cleanup on deactivation. Required so the container and plugin state are available. |

## Dev

- **newfold-labs/wp-php-standards** – PHPCS.
- **johnpbloch/wordpress** – WordPress core for tests.
- **lucatume/wp-browser** – Codeception wpunit.
- **phpunit/phpcov** – Coverage.
- **wp-cli/i18n-command** – i18n pot/po/mo/json.
