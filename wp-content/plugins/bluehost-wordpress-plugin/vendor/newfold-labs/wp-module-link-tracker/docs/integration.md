---
name: wp-module-link-tracker
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

## How the module runs

On **`newfold_container_set`** the bootstrap:

1. Defines `NFD_LINK_TRACKER_BUILD_URL` and `NFD_LINK_TRACKER_BUILD_DIR` from the container’s plugin URL/dir and the vendor path to this package’s `build` folder.
2. Instantiates `LinkTracker( $container )` and calls `add_hooks()`.

**LinkTracker::add_hooks()** registers:

- `admin_enqueue_scripts` (priority 99) – Enqueues the module’s JS build from `NFD_LINK_TRACKER_BUILD_DIR`.
- Filter **`nfd_build_url`** (priority 10, 2 args: `$url`, `$params`) – Implements `build_url()` to add tracking parameters to the URL.

## Using build_link

From PHP, call:

```php
use function NewfoldLabs\WP\Module\LinkTracker\Functions\build_link;

$url = build_link( 'https://example.com/page', [ 'utm_source' => 'plugin', 'utm_medium' => 'admin' ] );
```

The filter `nfd_build_url` receives the URL and params; this module (and others) can append or override. So host plugins and modules should use `build_link()` for outbound links that need tracking.
