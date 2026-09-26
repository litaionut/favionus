---
name: wp-module-runtime
title: Overview
description: What the module does and who maintains it.
updated: 2025-03-18
---

# Overview

## What the module does

**wp-module-runtime** provides the data that the Newfold admin app (React) needs at runtime. It builds a runtime object (site URL, admin URL, REST URL and nonce, capabilities, WooCommerce/Jetpack Boost flags, theme, etc.), runs it through the `newfold_runtime` filter so other modules can add data, then outputs it as `window.NewfoldRuntime` on admin enqueue. A small JS helper (from `src/index.js`) can wrap this global for typed access (e.g. `createApiUrl`, `hasCapability`).

- **PHP:** Registers on `newfold_container_set`; on `admin_enqueue_scripts` it injects an inline script that sets `window.NewfoldRuntime`.
- **Filter:** Host and other modules use `newfold_runtime` to merge in context, plugin info, and other keys.
- **Container:** The container must provide `capabilities` (object with `all()` method) so the runtime can expose capability flags to the app.

## Who maintains it

- **Newfold Labs** (Newfold Digital). Distributed via Newfold Satis and used by all Newfold WordPress brand plugins.

## High-level features

- Central place for admin app config (URLs, nonce, capabilities, feature flags).
- Extensible via `newfold_runtime` filter.
- Optional JS helper for `createApiUrl()`, `hasCapability()`, and other accessors.
