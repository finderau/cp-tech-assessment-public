# Comparison Experience

## Overview

A React-based comparison table that displays health insurance products from the product-data plugin. Features include:

- Horizontal scrolling product cards with collapsible detail sections (Extras, Hospital, Ambulance)
- Filter sidebar with multiple filter types (radio, checkbox, multi-select dropdown)
- URL synchronization for shareable filter states
- Mobile-responsive design with slide-out filter drawer

## Quick Start

### Development

```bash
cd app
yarn install
yarn dev
```

This starts a Vite dev server at `http://localhost:5173` with hot reload. The API is proxied to `https://playground.finder.dev`.

### Production Build

```bash
cd app
yarn build
```

This outputs to the `assets/` folder with an `asset-manifest.json` for WordPress to load.

## Usage

Add the shortcode to any WordPress page or post:

```php
[comparison_table]
```

### Development Mode

To enable development mode (loads from Vite dev server instead of built assets), add this to `wp-config.php`:

```php
define('COMPARISON_EXPERIENCE_DEV_MODE', true);
```

## Tech Design

See the [plan file](/.cursor/plans/comparison_experience_plugin_*.plan.md) for full technical design documentation.

## API Interfaces

The plugin fetches data from the product-data plugin's REST API:

**Endpoint:** `POST /wp-json/api/v1/product-data`

See [product-data README](../product-data/README.md) for full API documentation.

## WP Hooks

### Actions

| Hook | Description | Parameters |
|------|-------------|------------|
| `finder/comparison_experience/initialized` | Fires after plugin initialization | `Plugin $plugin` |
| `finder/comparison_experience/enabled` | Fires when plugin is enabled | - |
| `finder/comparison_experience/disabled` | Fires when plugin is disabled | - |
| `finder/comparison_experience/activated` | Fires on plugin activation | - |
| `finder/comparison_experience/deactivated` | Fires on plugin deactivation | - |
| `finder/comparison_experience/before_register_post_types` | Fires before CPT registration | - |
| `finder/comparison_experience/after_register_post_types` | Fires after CPT registration | - |
| `finder/comparison_experience/before_register_taxonomies` | Fires before taxonomy registration | - |
| `finder/comparison_experience/after_register_taxonomies` | Fires after taxonomy registration | - |
| `finder/comparison_experience/admin/register_settings` | Fires after settings registration | `string $pageSlug`, `string $optionGroup` |

### Filters

| Hook | Description | Parameters |
|------|-------------|------------|
| `finder/comparison_experience/capabilities` | Modify plugin capabilities | `array $capabilities` |

## Testing

<!-- TODO: Add testing instructions -->
