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
    }

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

    public function tfbdashboard_save_custom_order_fields($order, $request) {
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
        $order->save();
    }

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