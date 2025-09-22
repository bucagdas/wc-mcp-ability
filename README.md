# WooCommerce MCP Ability Demo Plugin

⚠️ **DEMO TOOL - NOT PRODUCTION READY** ⚠️

This is a demonstration plugin showing how third-party developers can integrate with the WooCommerce MCP (Model Context Protocol) server by registering custom abilities using the WordPress Abilities API. **This plugin is for educational and testing purposes only and should not be used in production environments.**

## Overview

This plugin demonstrates the integration between:
- [WordPress Abilities API](https://github.com/WordPress/abilities-api) - A standardized way to register capabilities in WordPress
- [WooCommerce MCP Adapter](https://github.com/WordPress/mcp-adapter) - Integrating into WooCommerce 10.3 release

**Important Notice**: The MCP implementation in WooCommerce 10.3 is a **developer preview**. Implementation details, APIs, and integration patterns may change in future releases. This demo is designed to showcase current capabilities and may require updates as the feature matures.

The demo creates a custom ability that retrieves WooCommerce store information and statistics, showcasing how external plugins can extend the MCP server's capabilities.

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- WooCommerce 10.3+ (includes WordPress Abilities API and MCP Adapter)

## Installation

1. Clone or download this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone [repository-url] wc-mcp-ability
   ```

2. Activate the plugin through the WordPress admin dashboard or WP-CLI:
   ```bash
   wp plugin activate wc-mcp-ability
   ```

## What This Demo Does

The plugin registers a custom ability called `woocommerce-demo/store-info` that:

### Functionality
- Retrieves basic store information (name, URL, admin email, versions)
- Optionally includes store statistics (product count, order counts by status, customer count)
- Demonstrates proper schema definition for inputs and outputs
- Shows permission handling for MCP abilities

### Technical Implementation
- **Ability Registration**: Uses `wp_register_ability()` from the WordPress Abilities API
- **MCP Integration**: Implements the `woocommerce_mcp_include_ability` filter to ensure the ability is exposed through the MCP server
- **Permission Control**: Requires `manage_woocommerce` capability for access
- **Schema Definition**: Provides complete JSON schema for both input parameters and output structure

## Usage Example

Once installed and the MCP server is running, the ability can be called with:

```json
{
  "ability": "woocommerce-demo/store-info",
  "input": {
    "include_stats": true
  }
}
```

### Sample Response

```json
{
  "store_name": "My WooCommerce Store",
  "store_url": "https://example.com",
  "admin_email": "admin@example.com",
  "woocommerce_version": "8.5.0",
  "wordpress_version": "6.4",
  "currency": "USD",
  "country": "US",
  "plugin_source": "WooCommerce Abilities Demo Plugin v1.0.0",
  "stats": {
    "product_count": 150,
    "order_count": 1250,
    "order_breakdown": {
      "completed": 1100,
      "processing": 50,
      "pending": 25,
      "on-hold": 15,
      "cancelled": 35,
      "refunded": 20,
      "failed": 5
    },
    "customer_count": 500
  }
}
```

## Key Integration Points

### 1. Ability Registration
```php
add_action( 'abilities_api_init', array( __CLASS__, 'register_store_info_ability' ) );
```

### 2. WooCommerce MCP Server Inclusion
```php
add_filter( 'woocommerce_mcp_include_ability', array( __CLASS__, 'include_demo_ability_in_mcp' ), 10, 2 );
```

**Note**: This filter is specific to the WooCommerce MCP server and is only needed if you want your ability included in the WooCommerce MCP server. Other MCP servers or implementations may require different approaches for including tools/abilities.

### 3. Schema Definition
The plugin demonstrates proper schema definition for both input and output, enabling automatic validation and documentation generation.

## Development Guidelines

When creating your own abilities:

1. **Use Proper Namespacing**: Choose a unique namespace for your abilities (e.g., `your-plugin/ability-name`)
2. **Define Complete Schemas**: Provide thorough input and output schemas for better integration
3. **Implement Permission Checks**: Always include appropriate permission callbacks
4. **Handle WooCommerce MCP Inclusion**: Use the `woocommerce_mcp_include_ability` filter if your ability namespace doesn't start with `woocommerce/` and you want it included in the WooCommerce MCP server specifically
5. **Follow WordPress Coding Standards**: Maintain consistency with WordPress development practices

## Architecture

```
WordPress Abilities API
    ↓ (registers abilities)
WooCommerce MCP Adapter
    ↓ (exposes via MCP protocol)
MCP Server
    ↓ (serves to clients)
MCP Clients (AI assistants, tools, etc.)
```

## Related Resources

- [WordPress Abilities API Repository](https://github.com/WordPress/abilities-api)
- [WooCommerce MCP Adapter Repository](https://github.com/WordPress/mcp-adapter)
- [Model Context Protocol Specification](https://modelcontextprotocol.io/)
- [WooCommerce Developer Documentation](https://woocommerce.com/developers/)

## License

This demo plugin follows the same licensing as WooCommerce core.

## Important Disclaimers

- **This is a demo tool**: Not intended for production use
- **Developer Preview**: The WooCommerce MCP feature in 10.3 is in developer preview status
- **API Changes**: Implementation details and APIs may change in future releases
- **MCP Server Specific**: The namespace filter shown is specific to WooCommerce MCP server integration

## Contributing

This is a demonstration plugin. For contributions to the underlying systems:
- WordPress Abilities API: See the [abilities-api repository](https://github.com/WordPress/abilities-api)
- WooCommerce MCP Adapter: See the [mcp-adapter repository](https://github.com/WordPress/mcp-adapter)