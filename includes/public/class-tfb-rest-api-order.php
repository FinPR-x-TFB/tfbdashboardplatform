<?php
/**
 * Plugin functions and definitions for Public.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package tfbdashboard
 */
if (!defined('ABSPATH')) {
    exit;
}

class TFBDashboard_REST_API_Order {
    public function __construct() {
        add_action('rest_api_init', array($this, 'tfbdashboard_register_custom_order_fields'));
        add_action('woocommerce_rest_insert_order_object', array($this, 'tfbdashboard_save_custom_order_fields'), 10, 2);
        add_filter('woocommerce_rest_create_order_validation', array($this, 'tfbdashboard_validate_custom_order_fields'), 10, 2);
        add_filter('woocommerce_rest_prepare_shop_order_object', array($this, 'tfbdashboard_add_custom_order_fields_to_response'), 10, 3);
    }

    /**
     * Register custom fields for WooCommerce Order API.
     */
    public function tfbdashboard_register_custom_order_fields() {
        register_rest_field('shop_order', 'challengePricingId', array(
            'schema' => array(
                'description' => __('Challenge Pricing ID'),
                'type'        => 'string',
                'context'     => array('view', 'edit'),
            ),
        ));

        register_rest_field('shop_order', 'stageId', array(
            'schema' => array(
                'description' => __('Stage ID'),
                'type'        => 'string',
                'context'     => array('view', 'edit'),
            ),
        ));

        register_rest_field('shop_order', 'userEmail', array(
            'schema' => array(
                'description' => __('User Email'),
                'type'        => 'string',
                'context'     => array('view', 'edit'),
            ),
        ));

        register_rest_field('shop_order', 'brandId', array(
            'schema' => array(
                'description' => __('Brand ID'),
                'type'        => 'string',
                'context'     => array('view', 'edit'),
            ),
        ));
    }

    /**
     * Validate custom fields before creating an order.
     */
    public function tfbdashboard_validate_custom_order_fields($valid, $request) {
        if (!isset($request['challengePricingId'], $request['stageId'], $request['userEmail'], $request['brandId'])) {
            return new WP_Error(
                'missing_required_fields',
                __('Required fields are missing: challengePricingId, stageId, userEmail, brandId.'),
                array('status' => 400)
            );
        }

        return $valid;
    }

    /**
     * Save custom fields when creating an order via API.
     */
    public function tfbdashboard_save_custom_order_fields($order, $request) {
        error_log('Saving custom fields: ' . print_r($request->get_params(), true));

        if (isset($request['challengePricingId'])) {
            $order->update_meta_data('_challenge_pricing_id', sanitize_text_field($request['challengePricingId']));
        }
        if (isset($request['stageId'])) {
            $order->update_meta_data('_stage_id', sanitize_text_field($request['stageId']));
        }
        if (isset($request['userEmail'])) {
            $order->update_meta_data('_user_email', sanitize_email($request['userEmail']));
        }
        if (isset($request['brandId'])) {
            $order->update_meta_data('_brand_id', sanitize_text_field($request['brandId']));
        }
        $order->save(); // Ensure meta is saved
    }


    /**
     * Add custom fields to the API response.
     */
    public function tfbdashboard_add_custom_order_fields_to_response($response, $order, $request) {
        error_log('Fetching order meta for response: Order ID ' . $order->get_id());

        $response->data['challengePricingId'] = $order->get_meta('_challenge_pricing_id', true);
        $response->data['stageId'] = $order->get_meta('_stage_id', true);
        $response->data['userEmail'] = $order->get_meta('_user_email', true);
        $response->data['brandId'] = $order->get_meta('_brand_id', true);

        error_log('Response values: ' . print_r($response->data, true));

        return $response;
    }
}
