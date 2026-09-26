---
name: wp-module-link-tracker
title: Getting started
description: Prerequisites, install, and run.
updated: 2025-03-18
---

# Getting started

## Prerequisites

- **PHP** 7.4+.
- **Composer** (no production deps; dev deps include loader for tests).

## Install

```bash
composer install
```

## Run tests

```bash
composer run test
composer run test-coverage
```

## Lint

```bash
composer run lint
composer run fix
```

## Using in a host plugin

1. Depend on `newfold-labs/wp-module-link-tracker` via Composer.
2. Load the module’s bootstrap. When the host sets the container, the module registers the `nfd_build_url` filter and enqueues its script.
3. Use `build_link( $url, $params )` (from `NewfoldLabs\WP\Module\LinkTracker\Functions`) to build URLs with tracking params. Other code can also use the filter directly.

See [integration.md](integration.md).
