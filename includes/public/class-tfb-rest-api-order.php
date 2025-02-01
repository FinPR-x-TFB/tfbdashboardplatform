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
        // Register custom fields for the REST API
        add_action('rest_api_init', array($this, 'tfbdashboard_register_custom_order_fields'));

        // Save custom fields when creating an order via REST API
        add_action('woocommerce_rest_insert_order_object', array($this, 'tfbdashboard_save_custom_order_fields'), 10, 3);

        // Add custom fields to the REST API response
        add_filter('woocommerce_rest_prepare_shop_order_object', array($this, 'tfbdashboard_add_custom_fields_to_response'), 10, 3);

        // Validate custom fields before creating an order
        add_filter('woocommerce_rest_create_order_validation', array($this, 'tfbdashboard_validate_custom_order_fields'), 10, 2);
    }

    /**
     * Register custom fields for the REST API.
     */
    public function tfbdashboard_register_custom_order_fields() {
        register_rest_field('shop_order', 'challengePricingId', array(
            'schema' => array(
                'description' => 'Challenge Pricing ID for the order.',
                'type' => 'string',
                'context' => array('view', 'edit'),
                'required' => true,
            ),
        ));

        register_rest_field('shop_order', 'stageId', array(
            'schema' => array(
                'description' => 'Stage ID for the order.',
                'type' => 'string',
                'context' => array('view', 'edit'),
                'required' => true,
            ),
        ));

        register_rest_field('shop_order', 'userEmail', array(
            'schema' => array(
                'description' => 'User email for the order.',
                'type' => 'string',
                'context' => array('view', 'edit'),
                'required' => true,
            ),
        ));

        register_rest_field('shop_order', 'brandId', array(
            'schema' => array(
                'description' => 'Brand ID for the order.',
                'type' => 'string',
                'context' => array('view', 'edit'),
                'required' => true,
            ),
        ));
    }

    /**
     * Save custom fields when creating an order via REST API.
     */
    public function tfbdashboard_save_custom_order_fields($order, $request, $creating) {
        if ($creating) {
            // Debug: Log the request payload
            error_log('TFB Dashboard: Request Payload - ' . print_r($request, true));

            // Validate required fields
            if (!isset($request['challengePricingId'], $request['stageId'], $request['userEmail'], $request['brandId'])) {
                throw new WP_Error('missing_required_fields', 'Required fields are missing: challengePricingId, stageId, userEmail, brandId.', ['status' => 400]);
            }

            // Save custom fields to order meta
            $order->update_meta_data('_challenge_pricing_id', sanitize_text_field($request['challengePricingId']));
            $order->update_meta_data('_stage_id', sanitize_text_field($request['stageId']));
            $order->update_meta_data('_user_email', sanitize_email($request['userEmail']));
            $order->update_meta_data('_brand_id', sanitize_text_field($request['brandId']));

            // Debug: Log the saved meta data
            error_log('TFB Dashboard: Saved Meta Data - ' . print_r([
                '_challenge_pricing_id' => $order->get_meta('_challenge_pricing_id'),
                '_stage_id' => $order->get_meta('_stage_id'),
                '_user_email' => $order->get_meta('_user_email'),
                '_brand_id' => $order->get_meta('_brand_id'),
            ], true));

            $order->save(); // Save the order to persist meta data
        }
    }

    /**
     * Add custom fields to the REST API response.
     */
    public function tfbdashboard_add_custom_fields_to_response($response, $order, $request) {
        // Debug: Log the retrieved meta data
        error_log('TFB Dashboard: Retrieved Meta Data - ' . print_r([
            '_challenge_pricing_id' => $order->get_meta('_challenge_pricing_id'),
            '_stage_id' => $order->get_meta('_stage_id'),
            '_user_email' => $order->get_meta('_user_email'),
            '_brand_id' => $order->get_meta('_brand_id'),
        ], true));

        // Add custom fields to the REST API response
        $response->data['challengePricingId'] = $order->get_meta('_challenge_pricing_id');
        $response->data['stageId'] = $order->get_meta('_stage_id');
        $response->data['userEmail'] = $order->get_meta('_user_email');
        $response->data['brandId'] = $order->get_meta('_brand_id');

        return $response;
    }
    
    /**
     * Validate custom fields before creating an order.
     */
    public function tfbdashboard_validate_custom_order_fields($errors, $request) {
        if (empty($request['challengePricingId'])) {
            $errors->add('missing_challenge_pricing_id', __('Challenge Pricing ID is required.', 'tfbdashboard'));
        }
        if (empty($request['stageId'])) {
            $errors->add('missing_stage_id', __('Stage ID is required.', 'tfbdashboard'));
        }
        if (empty($request['userEmail'])) {
            $errors->add('missing_user_email', __('User email is required.', 'tfbdashboard'));
        }
        if (empty($request['brandId'])) {
            $errors->add('missing_brand_id', __('Brand ID is required.', 'tfbdashboard'));
        }
        return $errors;
    }
}