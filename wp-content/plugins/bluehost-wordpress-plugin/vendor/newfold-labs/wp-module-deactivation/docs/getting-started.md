---
name: wp-module-deactivation
title: Getting started
description: Prerequisites, install, and run.
updated: 2025-03-18
---

# Getting started

## Prerequisites

- **PHP** 7.3+.
- **Composer.** The module requires `newfold-labs/wp-module-data`.

## Install

```bash
composer install
```

## Run tests

```bash
composer run test
composer run test-coverage
```

## Lint and i18n

```bash
composer run lint
composer run fix
composer run i18n
```

Text domain: **wp-module-deactivation**. See [development.md](development.md).

## Using in a host plugin

1. Depend on `newfold-labs/wp-module-deactivation` (and ensure wp-module-data is present).
2. Load the module’s bootstrap (Composer autoload files). When the host sets the container, this module registers the deactivation hook and survey. No further configuration required.

See [integration.md](integration.md).
