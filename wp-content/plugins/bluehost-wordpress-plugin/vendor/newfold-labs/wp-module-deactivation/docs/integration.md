---
name: wp-module-deactivation
title: Integration
description: How the module registers and integrates.
updated: 2025-03-18
---

# Integration

## How the module runs

The module hooks into **`newfold_container_set`**. In the callback it defines `NFD_DEACTIVATION_DIR` (this package root) and instantiates `Deactivation( $container )`.

**Deactivation** class:

- Registers `register_deactivation_hook( $container->plugin()->file, [ $this, 'on_deactivate' ] )`.
- On `admin_head-plugins.php`, instantiates `DeactivationSurvey()` so the survey UI is available on the Plugins screen.

## On deactivate

`on_deactivate()` runs when the brand plugin is deactivated. It typically:

- Disables coming soon mode (via the coming soon module or option).
- Clears transients (e.g. `newfold_marketplace`, `newfold_notifications`, and others as defined in the class).
- May flush rewrite rules or perform other cleanup.

See `includes/Deactivation.php` for the full list.

## Dependencies

The module requires **wp-module-data**. Ensure the data module is loaded before or with the deactivation module so the container and any data cleanup are available.
