---
name: wp-module-runtime
title: Getting started
description: Prerequisites, install, and run.
updated: 2025-03-18
---

# Getting started

## Prerequisites

- **PHP** 7.4+ (see `composer.json` platform).
- **Composer** for dependencies.
- **Git** for the repository.

## Install

From the package root:

```bash
composer install
```

The package has no production Composer dependencies; it expects to run inside a host that provides the loader and container. For dev, it pulls in wp-module-loader, WordPress, Codeception, etc.

## Run tests

```bash
composer run test
```

Uses Codeception with the `wpunit` suite. For coverage:

```bash
composer run test-coverage
```

Then open `tests/_output/html/index.html` to view the report.

## Lint

```bash
composer run lint
composer run fix
```

## Using the runtime in a host plugin

1. Depend on `newfold-labs/wp-module-runtime` via Composer (and ensure the loader and container are set up).
2. Register `capabilities` on the container (object with an `all()` method returning key-value capability flags).
3. Other modules can add to the runtime via the `newfold_runtime` filter.
4. On admin pages, `window.NewfoldRuntime` will be set automatically. The host may also build and enqueue the JS helper from `src/index.js` if desired.

See [integration.md](integration.md) for details.
