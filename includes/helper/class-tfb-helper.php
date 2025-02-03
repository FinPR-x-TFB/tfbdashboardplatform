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
    public function __construct() {
        add_action( 'wp', array( $this, 'clear_notices_on_order_pay' ) );
        // Hook the auto-fill function to run on init.
        add_action( 'init', array( $this, 'init_session'));
        add_action( 'init', array( $this, 'tfbdashboard_auto_fill_guest_order_email' ) );
        add_action( 'woocommerce_admin_order_data_after_order_details', array($this, 'show_all_custom_order_meta_in_custom_fields'), 10, 2);
    }

    public function init_session() {
        if ( ! is_admin() && ! WC()->session ) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
    }

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
     * Hooks into 'woocommerce_new_order' to set a meta flag that indicates
     * whether the API call for challenge account creation has been sent.
     */
    public static function tfbdashboard_init_order_meta() {
        add_action( 'woocommerce_new_order', array( __CLASS__, 'tfbdashboard_post_meta_on_order_creation' ) );
    }

    /**
     * Set the default value for connection completed meta and add an order note.
     *
     * @param int $order_id
     */
    public static function tfbdashboard_post_meta_on_order_creation( $order_id ) {
        // Set to 0 to indicate the API call has not yet been sent.
        update_post_meta( $order_id, '_tfbdashboard_connection_completed', 0 );

        // Retrieve the order and add an order note.
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $order->add_order_note( __( 'TFBDashboard: Connection initialized; API call pending.', 'tfbdashboard' ) );
        }
    }

    /**
     * Mask the provided API key.
     *
     * @param string $api_key
     * @return string Masked API key.
     */
    public static function tfbdashboard_connection_mask_api_key( $api_key ) {
        $key_length = strlen( $api_key );
        if ( $key_length <= 8 ) {
            return str_repeat( '*', $key_length );
        }
        $start  = substr( $api_key, 0, 4 );
        $end    = substr( $api_key, -4 );
        $masked = str_repeat( '*', $key_length - 8 );
        return $start . $masked . $end;
    }

    /**
     * Send a WP remote POST request.
     *
     * @param string $endpoint_url The full API endpoint URL.
     * @param string $api_key      The API key to send.
     * @param array  $api_data     The data payload for the API.
     * @param int    $request_delay Optional delay in seconds (default 0).
     * @return array Returns an array with 'http_status' and 'api_response'.
     */
    public static function tfbdashboard_send_wp_remote_post_request( $endpoint_url, $api_key, $api_data, $request_delay = 2 ) {
        $headers = array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'X-Client-Key' => $api_key,
        );

        $response = wp_remote_post(
            $endpoint_url,
            array(
                'timeout'     => 30,
                'redirection' => 5,
                'headers'     => $headers,
                'body'        => json_encode( $api_data ),
            )
        );

        $http_status  = wp_remote_retrieve_response_code( $response );
        $api_response = wp_remote_retrieve_body( $response );

        // Delay execution if a delay is specified.
        if ( $request_delay > 0 ) {
            usleep( $request_delay * 1000000 );
        }

        return array(
            'http_status'  => $http_status,
            'api_response' => $api_response,
        );
    }

    public function clear_notices_on_order_pay() {
        if (is_wc_endpoint_url('order-pay')) {
            wc_clear_notices();
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
     * Generate a strong random password.
     *
     * Generates a 12-character password containing uppercase, lowercase, numbers, and symbols.
     *
     * @param int $length Optional. Length of the password. Default 12.
     * @return string
     */
    public static function tfbdashboard_generate_strong_random_password( $length = 12 ) {
        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $all     = $lower . $upper . $numbers . $symbols;

        // Ensure each character category is represented.
        $password  = $lower[random_int(0, strlen($lower) - 1)];
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the remaining characters.
        for ( $i = 4; $i < $length; $i++ ) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle to remove predictable patterns.
        return str_shuffle( $password );
    }

    /**
     * Auto-fill the guest order email on the order-pay page.
     *
     * If the current URL is for the order-pay endpoint, the 'email' parameter is missing,
     * and the user is not logged in, this function retrieves the order by the order key
     * and auto-fills the email parameter using the order's billing email.
     */
    public static function tfbdashboard_auto_fill_guest_order_email() {
        if ( is_wc_endpoint_url( 'order-pay' ) && empty( $_GET['email'] ) && ! is_user_logged_in() && ! empty( $_GET['key'] ) ) {
            // Retrieve the order ID using the order key from the URL.
            $order_id = wc_get_order_id_by_order_key( sanitize_text_field( $_GET['key'] ) );
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
                // Only for guest orders (customer_id == 0)
                if ( $order && ! $order->get_customer_id() ) {
                    $_GET['email'] = $order->get_billing_email();
                }
            }
        }
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
