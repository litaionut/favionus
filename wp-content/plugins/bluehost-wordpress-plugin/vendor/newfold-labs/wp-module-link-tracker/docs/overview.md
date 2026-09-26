---
name: wp-module-link-tracker
title: Overview
description: What the module does and who maintains it.
updated: 2025-03-18
---

# Overview

## What the module does

**wp-module-link-tracker** adds tracking parameters (e.g. UTM) to URLs used in brand plugins. Other code calls `NewfoldLabs\WP\Module\LinkTracker\Functions\build_link( $url, $params )`, which passes the URL and params through the filter **`nfd_build_url`**. This module’s `LinkTracker` class implements that filter to append or merge tracking params. It also enqueues a JS build file in the admin for any front-end tracking behavior.

The module runs when the container is set; it defines build URL/dir constants and adds hooks so the filter and script are available.

## Who maintains it

- **Newfold Labs** (Newfold Digital). Distributed via Newfold Satis.
