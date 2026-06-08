<?php
/**
 * Plugin Name: WooCommerce MCP Ability
 * Plugin URI: https://github.com/bucagdas/wc-mcp-ability
 * Description: WooCommerce abilities for MCP. Manage product categories and run any WooCommerce wc/v3 REST request: products, orders, customers, coupons, settings and reports.
 * Version: 1.0.1
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: bucagdas
 * Author URI: https://github.com/bucagdas/
 * Text Domain: wc-mcp-ability
 * Requires Plugins: woocommerce
 */

namespace WCMCPAbility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	const CATEGORY = 'wc-mcp';

	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_filter( 'woocommerce_mcp_include_ability', array( __CLASS__, 'include_in_woocommerce_mcp' ), 10, 2 );
	}

	/**
	 * Register the ability category. Must run on wp_abilities_api_categories_init.
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'WooCommerce MCP', 'wc-mcp-ability' ),
				'description' => __( 'Full-access WooCommerce store management abilities.', 'wc-mcp-ability' ),
			)
		);
	}

	/**
	 * Register all abilities. Must run on wp_abilities_api_init.
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// 1. List product categories (read-only).
		wp_register_ability(
			'wc-mcp/list-product-categories',
			array(
				'label'               => __( 'List product categories', 'wc-mcp-ability' ),
				'description'         => __( 'Returns all WooCommerce product categories with id, name, slug, parent, description and product count.', 'wc-mcp-ability' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'search'   => array(
							'type'        => 'string',
							'description' => 'Optional search term to filter categories by name.',
						),
						'parent'   => array(
							'type'        => 'integer',
							'description' => 'Optional parent category id to list children of.',
						),
						'per_page' => array(
							'type'        => 'integer',
							'description' => 'Maximum number of categories to return. Default 100.',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'array',
					'description' => 'List of product categories.',
					'items'       => array(
						'type' => 'object',
					),
				),
				'execute_callback'    => array( __CLASS__, 'cb_list_categories' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'meta'                => self::meta( true, false, true ),
			)
		);

		// 2. Create product category.
		wp_register_ability(
			'wc-mcp/create-product-category',
			array(
				'label'               => __( 'Create product category', 'wc-mcp-ability' ),
				'description'         => __( 'Creates a new WooCommerce product category. The name is required; slug, description and parent are optional.', 'wc-mcp-ability' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'        => array(
							'type'        => 'string',
							'description' => 'Category name.',
						),
						'slug'        => array(
							'type'        => 'string',
							'description' => 'Optional URL slug. Auto-generated from name when omitted.',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Optional category description.',
						),
						'parent'      => array(
							'type'        => 'integer',
							'description' => 'Optional parent category id.',
						),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => 'The created category.',
				),
				'execute_callback'    => array( __CLASS__, 'cb_create_category' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'meta'                => self::meta( false, false, false ),
			)
		);

		// 3. Update product category.
		wp_register_ability(
			'wc-mcp/update-product-category',
			array(
				'label'               => __( 'Update product category', 'wc-mcp-ability' ),
				'description'         => __( 'Updates an existing WooCommerce product category identified by id. Provide only the fields to change.', 'wc-mcp-ability' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'          => array(
							'type'        => 'integer',
							'description' => 'Category id to update.',
						),
						'name'        => array(
							'type'        => 'string',
							'description' => 'New name.',
						),
						'slug'        => array(
							'type'        => 'string',
							'description' => 'New slug.',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'New description.',
						),
						'parent'      => array(
							'type'        => 'integer',
							'description' => 'New parent category id.',
						),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => 'The updated category.',
				),
				'execute_callback'    => array( __CLASS__, 'cb_update_category' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'meta'                => self::meta( false, false, true ),
			)
		);

		// 4. Delete product category (destructive).
		wp_register_ability(
			'wc-mcp/delete-product-category',
			array(
				'label'               => __( 'Delete product category', 'wc-mcp-ability' ),
				'description'         => __( 'Permanently deletes a WooCommerce product category identified by id.', 'wc-mcp-ability' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Category id to delete.',
						),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => 'Deletion result.',
				),
				'execute_callback'    => array( __CLASS__, 'cb_delete_category' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'meta'                => self::meta( false, true, true ),
			)
		);

		// 5. Generic WooCommerce REST request (full store access).
		wp_register_ability(
			'wc-mcp/wc-request',
			array(
				'label'               => __( 'WooCommerce REST request', 'wc-mcp-ability' ),
				'description'         => __( 'Performs any WooCommerce REST API (wc/v3) request. Provide method (GET, POST, PUT or DELETE), endpoint (e.g. "products", "orders", "products/categories", "customers", "coupons", "settings", "reports") and optional params. This covers the full WooCommerce store: products, variations, orders, customers, coupons, shipping, taxes, settings and reports.', 'wc-mcp-ability' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'method'   => array(
							'type'        => 'string',
							'description' => 'HTTP method: GET, POST, PUT or DELETE.',
						),
						'endpoint' => array(
							'type'        => 'string',
							'description' => 'WooCommerce REST endpoint after wc/v3/, e.g. "products" or "products/categories".',
						),
						'params'   => array(
							'type'        => 'object',
							'description' => 'Optional request parameters (query for GET/DELETE, body for POST/PUT).',
						),
					),
					'required'             => array( 'method', 'endpoint' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'        => 'object',
					'description' => 'Response containing success flag, HTTP status and data.',
				),
				'execute_callback'    => array( __CLASS__, 'cb_wc_request' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'meta'                => self::meta( false, false, false ),
			)
		);
	}

	/**
	 * Build the meta array for an ability.
	 */
	private static function meta( bool $readonly, bool $destructive, bool $idempotent ): array {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
			),
			'annotations'  => array(
				'readonly'    => $readonly,
				'destructive' => $destructive,
				'idempotent'  => $idempotent,
			),
			'expose_in_deprecated_woocommerce_mcp' => true,
		);
	}

	/**
	 * Permission check for all abilities.
	 */
	public static function permission( $input = null ): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Include our abilities in the deprecated WooCommerce MCP endpoint.
	 */
	public static function include_in_woocommerce_mcp( $include, $ability_name ) {
		if ( is_string( $ability_name ) && str_starts_with( $ability_name, 'wc-mcp/' ) ) {
			return true;
		}
		return $include;
	}

	// ---------------------------------------------------------------------
	// Execute callbacks
	// ---------------------------------------------------------------------

	public static function cb_list_categories( $input ) {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => isset( $input['per_page'] ) ? (int) $input['per_page'] : 100,
		);
		if ( ! empty( $input['search'] ) ) {
			$args['search'] = (string) $input['search'];
		}
		if ( isset( $input['parent'] ) ) {
			$args['parent'] = (int) $input['parent'];
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$out = array();
		foreach ( $terms as $term ) {
			$out[] = array(
				'id'          => (int) $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'parent'      => (int) $term->parent,
				'description' => $term->description,
				'count'       => (int) $term->count,
			);
		}
		return $out;
	}

	public static function cb_create_category( $input ) {
		if ( empty( $input['name'] ) ) {
			return new \WP_Error( 'missing_name', __( 'A category name is required.', 'wc-mcp-ability' ) );
		}

		$args = array();
		if ( ! empty( $input['slug'] ) ) {
			$args['slug'] = (string) $input['slug'];
		}
		if ( ! empty( $input['description'] ) ) {
			$args['description'] = (string) $input['description'];
		}
		if ( ! empty( $input['parent'] ) ) {
			$args['parent'] = (int) $input['parent'];
		}

		$res = wp_insert_term( (string) $input['name'], 'product_cat', $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$term = get_term( $res['term_id'], 'product_cat' );
		return array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'parent'      => (int) $term->parent,
			'description' => $term->description,
		);
	}

	public static function cb_update_category( $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error( 'missing_id', __( 'A category id is required.', 'wc-mcp-ability' ) );
		}

		$args = array();
		foreach ( array( 'name', 'slug', 'description' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$args[ $key ] = (string) $input[ $key ];
			}
		}
		if ( isset( $input['parent'] ) ) {
			$args['parent'] = (int) $input['parent'];
		}

		$res = wp_update_term( (int) $input['id'], 'product_cat', $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$term = get_term( (int) $input['id'], 'product_cat' );
		return array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'parent'      => (int) $term->parent,
			'description' => $term->description,
		);
	}

	public static function cb_delete_category( $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error( 'missing_id', __( 'A category id is required.', 'wc-mcp-ability' ) );
		}

		$res = wp_delete_term( (int) $input['id'], 'product_cat' );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return array(
			'deleted' => (bool) $res,
			'id'      => (int) $input['id'],
		);
	}

	public static function cb_wc_request( $input ) {
		if ( empty( $input['method'] ) || empty( $input['endpoint'] ) ) {
			return new \WP_Error( 'missing_args', __( 'Both method and endpoint are required.', 'wc-mcp-ability' ) );
		}

		$method   = strtoupper( (string) $input['method'] );
		$endpoint = ltrim( (string) $input['endpoint'], '/' );
		$params   = ( isset( $input['params'] ) && is_array( $input['params'] ) ) ? $input['params'] : array();

		$route   = '/wc/v3/' . $endpoint;
		$request = new \WP_REST_Request( $method, $route );

		if ( in_array( $method, array( 'GET', 'DELETE' ), true ) ) {
			foreach ( $params as $key => $value ) {
				$request->set_param( $key, $value );
			}
		} else {
			$request->set_body_params( $params );
		}

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			$error = $response->as_error();
			return array(
				'success' => false,
				'status'  => $response->get_status(),
				'error'   => $error->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'status'  => $response->get_status(),
			'data'    => $response->get_data(),
		);
	}
}

add_action( 'plugins_loaded', array( __NAMESPACE__ . '\\Plugin', 'init' ) );