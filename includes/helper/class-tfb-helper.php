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

class TFBDashboard_Helper {
    public function __construct() {
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'show_all_custom_order_meta_in_custom_fields'), 10, 2);
    }

    public function show_all_custom_order_meta_in_custom_fields($order) {
        // Get all metadata for the order
        $order_id = $order->get_id();
        $meta_data = get_post_meta($order_id);

        echo '<h4 style="margin-top: 400px;">All Custom Metadata</h4>';
        echo '<table class="" cellspacing="0" style="margin-top: 10px; width:100%; max-width:100%;">';
        echo '<tbody>';

        if (!empty($meta_data)) {
            foreach ($meta_data as $meta_key => $meta_value) {
                // Show each meta key and value pair
                echo '<tr>';
                echo '<td>' . esc_html($meta_key) . '</td>';
                echo '<td>' . esc_html(is_array($meta_value) ? json_encode($meta_value) : $meta_value[0]) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="2">No metadata found for this order.</td></tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}



