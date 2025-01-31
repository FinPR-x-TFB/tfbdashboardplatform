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

class TFBDashboard_API_Account_Creation {
    public function __construct() {
        add_action('woocommerce_order_status_changed', array($this, 'tfbdashboard_create_challenge_account'), 10, 3);
    }

    public function tfbdashboard_create_challenge_account($order_id, $old_status, $new_status) {
        if ($new_status === 'completed') {
            $order = wc_get_order($order_id);
            $challenge_pricing_id = $order->get_meta('_challenge_pricing_id');
            $stage_id = $order->get_meta('_stage_id');
            $user_email = $order->get_meta('_user_email');
            $brand_id = $order->get_meta('_brand_id');

            $data = array(
                'challengePricingId' => $challenge_pricing_id,
                'stageId' => $stage_id,
                'userEmail' => $user_email,
                'brandId' => $brand_id,
            );

            $response = wp_remote_post('https://gateway-dev.thefundedbettor.com/api/source/challenge-accounts', array(
                'headers' => array(
                    'Authorization' => 'Bearer YOUR_SECRET_TOKEN',
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode($data),
            ));

            if (is_wp_error($response)) {
                error_log('TFB Dashboard: Error creating challenge account - ' . $response->get_error_message());
            }
        }
    }
}