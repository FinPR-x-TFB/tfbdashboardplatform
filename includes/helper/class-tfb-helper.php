<?php
/**
 * Plugin functions and definitions for Public.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package tfbdashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TFBDashboard_Helper {
    /**
     * Log a message when WP_DEBUG is enabled.
     *
     * @param string $message
     */
    public static function tfbdashboard_log( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[TFBDashboard] ' . $message );
        }
    }

    /**
     * Returns a logger instance and context for logging.
     *
     * @return array
     */
    public static function tfbdashboard_connection_response_logger() {
        $logger  = wc_get_logger();
        $context = array( 'source' => 'tfbdashboard_connection_response_log' );
        return array( 'logger' => $logger, 'context' => $context );
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

