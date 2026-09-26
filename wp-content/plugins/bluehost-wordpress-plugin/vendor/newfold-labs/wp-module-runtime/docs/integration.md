---
name: wp-module-runtime
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

## How the runtime is loaded

1. **Container set** – When the host (or loader) sets the container, the action `newfold_container_set` runs. This package hooks there and creates a `Runtime` instance with that container, then calls `loadIntoPage( 'admin_enqueue_scripts' )`.
2. **Admin enqueue** – On `admin_enqueue_scripts`, the runtime registers an inline script that sets `window.NewfoldRuntime` to the result of `prepareRuntime()`. No separate script file is required for the PHP output; the host may additionally enqueue a built JS file that wraps the global.

## What the host must provide

- **Container** with at least:
  - `capabilities` – An object with an `all()` method returning an associative array of capability name => boolean. Exposed in the runtime as `capabilities` so the app can call `hasCapability( name )`.

Other keys (e.g. `plugin`, `ecommerce`) may be added by the host or other modules via the filter and will appear on `window.NewfoldRuntime` if merged into the array.

## Filter: newfold_runtime

The runtime array is built in `Runtime::prepareRuntime()` and passed to:

```php
apply_filters( 'newfold_runtime', $array );
```

Modules (e.g. wp-module-context) merge in their data. The host and other packages should use this filter to add or override keys. Common keys include:

- `siteUrl`, `siteTitle`, `adminUrl`, `homeUrl`
- `restUrl`, `restNonce`
- `capabilities`
- `context` (from wp-module-context)
- `plugin`, `ecommerce`, etc. (from host)

## JS helper (src/index.js)

The package includes a small JS module that wraps `window.NewfoldRuntime` with helpers such as:

- `hasCapability( name )`
- `adminUrl( path )`
- `createApiUrl( url, qs )`
- Getters for `siteDetails`, `sdk`, `isWoo`, `plugin`, `siteTitle`, etc.

Host plugins typically build this with webpack and enqueue it on the same admin pages that receive the inline runtime. The runtime must be output before this script runs.
