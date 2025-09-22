<?php
/**
 * Plugin Name: WooCommerce MCP Ability Demo
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Description: Demonstrates how third-party plugins can integrate with the WooCommerce MCP server by registering custom abilities.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: wc-mcp-ability
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 */

declare( strict_types=1 );

namespace WCAbilitiesDemo;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Abilities Demo Plugin
 *
 * This plugin demonstrates how third-party developers can integrate with the
 * WooCommerce MCP (Model Context Protocol) server by registering their own abilities
 * using the WordPress Abilities API.
 */
class WCAbilitiesDemo {

	/**
	 * Plugin version.
	 */
	public const VERSION = '1.0.0';

	/**
	 * Initialize the plugin.
	 */
	public static function init(): void {
		// Register our store info ability when the abilities API is ready
		add_action( 'abilities_api_init', array( __CLASS__, 'register_store_info_ability' ) );

		// Allow our demo ability to be included in MCP server
		add_filter( 'woocommerce_mcp_include_ability', array( __CLASS__, 'include_demo_ability_in_mcp' ), 10, 2 );
	}

	/**
	 * Register the store info ability with the WordPress Abilities API.
	 *
	 * This demonstrates how third-party plugins can register abilities that
	 * will be automatically discovered and made available through the MCP server.
	 */
	public static function register_store_info_ability(): void {
		// Only proceed if wp_register_ability function exists (from WordPress Abilities API)
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce-demo/store-info',
			array(
				'label'             => __( 'Get Store Information (Demo)', 'wc-mcp-ability' ),
				'description'       => __( 'Demo implementation: Retrieves basic information about the WooCommerce store including name, URL, version, and basic statistics.', 'wc-mcp-ability' ),
				'input_schema'      => array(
					'type'       => 'object',
					'properties' => array(
						'include_stats' => array(
							'type'        => 'boolean',
							'description' => 'Whether to include basic store statistics (product count, order count, etc.)',
							'default'     => false,
						),
					),
				),
				'output_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'store_name'          => array( 'type' => 'string' ),
						'store_url'           => array( 'type' => 'string' ),
						'admin_email'         => array( 'type' => 'string' ),
						'woocommerce_version' => array( 'type' => 'string' ),
						'wordpress_version'   => array( 'type' => 'string' ),
						'currency'            => array( 'type' => 'string' ),
						'country'             => array( 'type' => 'string' ),
						'plugin_source'       => array( 'type' => 'string' ),
						'stats'               => array(
							'type'       => 'object',
							'properties' => array(
								'product_count'   => array( 'type' => 'integer' ),
								'order_count'     => array( 'type' => 'integer' ),
								'order_breakdown' => array(
									'type'       => 'object',
									'properties' => array(
										'completed'  => array( 'type' => 'integer' ),
										'processing' => array( 'type' => 'integer' ),
										'pending'    => array( 'type' => 'integer' ),
										'on-hold'    => array( 'type' => 'integer' ),
										'cancelled'  => array( 'type' => 'integer' ),
										'refunded'   => array( 'type' => 'integer' ),
										'failed'     => array( 'type' => 'integer' ),
									),
								),
								'customer_count'  => array( 'type' => 'integer' ),
							),
						),
					),
					'required'   => array( 'store_name', 'store_url', 'woocommerce_version', 'plugin_source' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_store_info_ability' ),
				'permission_callback' => array( __CLASS__, 'check_store_info_permission' ),
			)
		);
	}

	/**
	 * Execute the store info ability.
	 *
	 * @param array $input Input parameters.
	 * @return array Store information.
	 */
	public static function execute_store_info_ability( array $input ): array {
		// Build the basic store information
		$result = array(
			'store_name'          => get_bloginfo( 'name' ),
			'store_url'           => get_site_url(),
			'admin_email'         => get_bloginfo( 'admin_email' ),
			'woocommerce_version' => WC()->version,
			'wordpress_version'   => get_bloginfo( 'version' ),
			'currency'            => get_woocommerce_currency(),
			'country'             => WC()->countries->get_base_country(),
			'plugin_source'       => 'WooCommerce Abilities Demo Plugin v' . self::VERSION,
		);

		// Include statistics if requested
		if ( ! empty( $input['include_stats'] ) ) {
			$result['stats'] = self::get_store_statistics();
		}

		return $result;
	}

	/**
	 * Get store statistics.
	 *
	 * @return array Store statistics.
	 */
	private static function get_store_statistics(): array {
		// Products use 'publish' status
		$product_count = (int) wp_count_posts( 'product' )->publish;

		// Orders - using WooCommerce order status constants
		$completed_count  = wc_orders_count( 'completed' );
		$processing_count = wc_orders_count( 'processing' );
		$pending_count    = wc_orders_count( 'pending' );
		$on_hold_count    = wc_orders_count( 'on-hold' );
		$cancelled_count  = wc_orders_count( 'cancelled' );
		$refunded_count   = wc_orders_count( 'refunded' );
		$failed_count     = wc_orders_count( 'failed' );

		$order_breakdown = array(
			'completed'  => $completed_count,
			'processing' => $processing_count,
			'pending'    => $pending_count,
			'on-hold'    => $on_hold_count,
			'cancelled'  => $cancelled_count,
			'refunded'   => $refunded_count,
			'failed'     => $failed_count,
		);

		$order_count = array_sum( $order_breakdown );

		// Customers - count users with 'customer' role
		$users_counts   = count_users();
		$customer_count = isset( $users_counts['avail_roles']['customer'] ) ? (int) $users_counts['avail_roles']['customer'] : 0;

		return array(
			'product_count'    => $product_count,
			'order_count'      => $order_count,
			'order_breakdown'  => $order_breakdown,
			'customer_count'   => $customer_count,
		);
	}

	/**
	 * Check permission for the store info ability.
	 *
	 * @return bool Whether user has permission.
	 */
	public static function check_store_info_permission(): bool {
		// Allow users who can manage WooCommerce (same as system_status endpoint)
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Filter to include demo ability in MCP server.
	 *
	 * @param bool   $include    Whether to include the ability.
	 * @param string $ability_id The ability ID being checked.
	 * @return bool Whether to include the ability.
	 */
	public static function include_demo_ability_in_mcp( bool $include, string $ability_id ): bool {
		// Include our demo ability even though it doesn't have woocommerce/ namespace
		if ( 'woocommerce-demo/store-info' === $ability_id ) {
			return true;
		}

		return $include;
	}
}

// Initialize the plugin
WCAbilitiesDemo::init();