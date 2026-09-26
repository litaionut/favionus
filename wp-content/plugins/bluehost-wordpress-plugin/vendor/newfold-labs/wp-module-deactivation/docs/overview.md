---
name: wp-module-deactivation
title: Overview
description: What the module does and who maintains it.
updated: 2025-03-18
---

# Overview

## What the module does

**wp-module-deactivation** handles deactivation of Newfold WordPress brand plugins and related cleanup. When the container is set, it:

- **Registers the plugin deactivation hook** – On deactivation it disables coming soon mode, clears transients (e.g. newfold_marketplace, newfold_notifications), and performs other cleanup.
- **Deactivation survey** – On the plugins screen (`admin_head-plugins.php`), it loads a deactivation survey so users can optionally provide feedback when deactivating the brand plugin.

The module depends on **wp-module-data** (e.g. for context or data cleanup). It does not register as a toggleable loader module; it runs when the container is set.

## Who maintains it

- **Newfold Labs** (Newfold Digital). Distributed via Newfold Satis.
