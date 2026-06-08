# WooCommerce MCP Ability

![WordPress](https://img.shields.io/badge/WordPress-7.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![WooCommerce](https://img.shields.io/badge/WooCommerce-required-96588a)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-3da638)

A WordPress plugin that exposes WooCommerce store management to AI agents as **MCP tools**, built on the [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/). It registers a set of WooCommerce abilities — product-category CRUD plus a generic `wc/v3` REST passthrough — and marks them public so the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) surfaces them to AI clients such as Claude.

The abilities also register on the core Abilities REST API (`/wp-json/wp-abilities/v1/`), so you can call them over plain HTTP with an application password.

## Features

- Full **product-category CRUD**: list, create, update, delete.
- A generic **`wc-mcp/wc-request`** ability that runs any WooCommerce REST (`wc/v3`) request — products, variations, orders, customers, coupons, shipping, taxes, settings and reports.
- Registered the correct way: categories on `wp_abilities_api_categories_init`, abilities on `wp_abilities_api_init`, each with proper MCP annotations (`readonly` / `destructive` / `idempotent`).
- Exposed on the MCP Adapter **default server** (`meta.mcp.public`) and opted into WooCommerce's built-in (deprecated) MCP endpoint via the `woocommerce_mcp_include_ability` filter.
- Permission-gated behind the `manage_woocommerce` capability.

## Requirements

- **WordPress 7.0+** (the Abilities API ships from WordPress 6.9).
- **PHP 8.0+**.
- **WooCommerce** active.
- To expose the abilities over MCP, the **WordPress MCP Adapter** must be active on the site. It automatically creates a default MCP server that surfaces public abilities. The abilities still register on the Abilities API (and its REST endpoints) without it.

## Installation

**From a release / ZIP**

1. Download `wc-mcp-ability.zip`.
2. In wp-admin, go to **Plugins → Add New → Upload Plugin**, choose the ZIP, and install.
3. Click **Activate**.

**From source**

```bash
cd wp-content/plugins
git clone https://github.com/bucagdas/wc-mcp-ability.git
```

Then activate **WooCommerce MCP Ability** from the Plugins screen.

After activating, confirm the abilities registered by visiting (authenticated):

```
https://example.com/wp-json/wp-abilities/v1/abilities?category=wc-mcp
```

You should see all five `wc-mcp/*` abilities listed.

## Abilities

| Ability | Type | Description |
| --- | --- | --- |
| `wc-mcp/list-product-categories` | read-only | List product categories (`id`, `name`, `slug`, `parent`, `description`, `count`). |
| `wc-mcp/create-product-category` | write | Create a product category. `name` is required. |
| `wc-mcp/update-product-category` | write | Update a product category by `id`. |
| `wc-mcp/delete-product-category` | destructive | Delete a product category by `id`. |
| `wc-mcp/wc-request` | write | Run any WooCommerce REST (`wc/v3`) request. Full store access. |

### Inputs

- **`list-product-categories`** — `search` *(string, optional)*, `parent` *(integer, optional)*, `per_page` *(integer, optional, default 100)*.
- **`create-product-category`** — `name` *(string, **required**)*, `slug` *(string, optional)*, `description` *(string, optional)*, `parent` *(integer, optional)*.
- **`update-product-category`** — `id` *(integer, **required**)*, `name` / `slug` / `description` / `parent` *(optional)*.
- **`delete-product-category`** — `id` *(integer, **required**)*.
- **`wc-request`** — `method` *(string, **required**: GET, POST, PUT or DELETE)*, `endpoint` *(string, **required**, e.g. `products` or `products/categories`)*, `params` *(object, optional — query for GET/DELETE, body for POST/PUT)*.

## Usage

### Over MCP

On the MCP Adapter default server, public abilities are reached through the adapter's meta-tools (`discover-abilities`, `get-ability-info`, `execute-ability`) rather than being listed individually. A client discovers them, then executes by name:

```json
{
  "ability_name": "wc-mcp/create-product-category",
  "parameters": {
    "name": "Bouquets",
    "slug": "bouquets",
    "description": "Hand-tied seasonal bouquets."
  }
}
```

Using the generic ability for anything in the store:

```json
{
  "ability_name": "wc-mcp/wc-request",
  "parameters": {
    "method": "GET",
    "endpoint": "products",
    "params": { "per_page": 5 }
  }
}
```

### Over the REST API

The abilities are also available through the core Abilities REST API. The required HTTP method follows the ability's annotations: read-only → `GET`, write → `POST`, destructive → `DELETE`.

```bash
# Run a read-only ability (GET)
curl -u 'USER:APP_PASSWORD' \
  "https://example.com/wp-json/wp-abilities/v1/wc-mcp/list-product-categories/run"

# Create a category (POST — input wrapped in "input")
curl -u 'USER:APP_PASSWORD' -X POST -H 'Content-Type: application/json' \
  -d '{"input":{"name":"Bouquets","slug":"bouquets"}}' \
  "https://example.com/wp-json/wp-abilities/v1/wc-mcp/create-product-category/run"

# Generic WooCommerce request via wc-request (POST)
curl -u 'USER:APP_PASSWORD' -X POST -H 'Content-Type: application/json' \
  -d '{"input":{"method":"GET","endpoint":"orders","params":{"per_page":10}}}' \
  "https://example.com/wp-json/wp-abilities/v1/wc-mcp/wc-request/run"
```

## Permissions & security

All abilities require the `manage_woocommerce` capability. When invoked over MCP or REST, they run **as the authenticated WordPress user**, so that user must hold the capability (typically Administrator or Shop Manager).

> ⚠️ **`wc-mcp/wc-request` grants full WooCommerce admin access** over the REST API. Treat it accordingly:
> - Connect with a **dedicated user and application password**, not a shared admin login.
> - Grant the **least privilege** necessary.
> - Remember that any MCP client with access can perform any WooCommerce operation the user is allowed to.

## About this fork

This project started as a fork of [`woocommerce/wc-mcp-ability`](https://github.com/woocommerce/wc-mcp-ability), the upstream demo that shows a single ability. It was extended into a practical store-management toolset by adding:

- complete product-category CRUD,
- a generic `wc/v3` REST passthrough ability covering the whole store,
- correct MCP annotations on every ability, and
- exposure on both the MCP Adapter default server and WooCommerce's deprecated MCP endpoint.

## Changelog

### 1.0.1
- Registered abilities and categories strictly on the official `wp_abilities_api_init` / `wp_abilities_api_categories_init` hooks.
- Added `readonly` / `destructive` / `idempotent` annotations to all abilities.
- Confirmed `meta.mcp.public` and `show_in_rest` exposure.

## License

Released under the **GNU General Public License v2.0 or later** (`GPL-2.0-or-later`), in keeping with WordPress and the upstream WooCommerce project.

## Credits

Built on the [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/) and the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter). Forked from [`woocommerce/wc-mcp-ability`](https://github.com/woocommerce/wc-mcp-ability).