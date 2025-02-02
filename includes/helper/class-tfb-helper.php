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

    /**
     * Initialize the connection meta field for new orders.
     *
     * This method hooks into 'woocommerce_new_order' to add a meta field
     * that indicates whether the API call for challenge account creation has been sent.
     */
    public static function tfbdashboard_init_order_meta() {
        add_action( 'woocommerce_new_order', array( __CLASS__, 'tfbdashboard_post_meta_on_order_creation' ) );
    }

    /**
     * Set the default value for connection completed meta.
     *
     * @param int $order_id
     */
    public static function tfbdashboard_post_meta_on_order_creation( $order_id ) {
        // Set to 0 to indicate the API call has not yet been sent.
        update_post_meta( $order_id, '_tfbdashboard_connection_completed', 0 );
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

