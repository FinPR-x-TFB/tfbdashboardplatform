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

class TFBDashboard_API_Account_Creation {
    public function __construct() {
        add_action( 'woocommerce_order_status_changed', array( $this, 'tfbdashboard_order_status_changed' ), 10, 4 );
    }

    /**
     * Trigger challenge account creation when an order is completed,
     * but only once per order.
     *
     * @param int      $order_id
     * @param string   $old_status
     * @param string   $new_status
     * @param WC_Order $order
     */
    public function tfbdashboard_order_status_changed( $order_id, $old_status, $new_status, $order ) {
        // Only trigger if the status is changing to 'completed'
        // and it was not already 'completed'
        if ( 'completed' !== $new_status || 'completed' === $old_status ) {
            return;
        }

        // Check if the API call has already been made for this order.
        $connection_completed = get_post_meta( $order_id, '_tfbdashboard_connection_completed', true );
        if ( 1 == $connection_completed ) {
            return;
        }

        // Check for transient to prevent duplicate API calls.
        if ( false !== get_transient( 'send_api_lock_' . $order_id ) ) {
            return;
        }

        // Set a transient lock for a short period (e.g., 3 seconds) to block duplicate API calls.
        set_transient( 'send_api_lock_' . $order_id, true, 3 );

        // Retrieve settings from the admin panel.
        $environment = get_option( 'tfbdashboard_environment', 'sandbox' );
        if ( 'live' === $environment ) {
            $base_url = get_option( 'tfbdashboard_live_endpoint', 'https://api.ypf.customers.sigma-ventures.cloud' );
            $api_key  = get_option( 'tfbdashboard_live_api_key', '18c98a659a174bd68c6380751ff821ac686b0f6dcba14e2497a01702d7f0584d' );
        } else {
            $base_url = get_option( 'tfbdashboard_sandbox_endpoint', 'https://bqsyp740n4.execute-api.ap-southeast-1.amazonaws.com' );
            $api_key  = get_option( 'tfbdashboard_sandbox_test_key', '18c98a659a174bd68c6380751ff821ac686b0f6dcba14e2497a01702d7f0584d' );
        }

        // Append API endpoint path.
        $api_url = trailingslashit( $base_url ) . 'api/source/challenge-accounts';

        // Prepare the POST arguments.
        $args = array(
            'method'  => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'order_id'           => $order_id,
                'challengePricingId' => $order->get_meta( 'challengePricingId' ),
                'stageId'            => $order->get_meta( 'stageId' ),
                'userEmail'          => $order->get_meta( 'userEmail' ),
                'brandId'            => $order->get_meta( 'brandId' ),
            ) ),
        );

        // Execute the API call.
        $response = wp_remote_post( $api_url, $args );

        // Retrieve the logging configuration.
        $save_log_response = get_option( 'tfbdashboard_save_log_response', 1 );
        $logger_data       = TFBDashboard_Helper::tfbdashboard_connection_response_logger();
        $logger            = $logger_data['logger'];
        $context           = $logger_data['context'];

        // Retrieve the HTTP response code and body.
        $response_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        if ( is_wp_error( $response ) ) {
            // If a WP error occurred.
            if ( $save_log_response ) {
                $logger->error( 'TFBDashboard API Account Creation Error: ' . $response->get_error_message(), $context );
            }
            $order->add_order_note( sprintf( __( 'TFBDashboard API Account Creation failed: %s', 'tfbdashboard' ), $response->get_error_message() ) );
        } else {
            if ( $response_code >= 200 && $response_code < 300 ) {
                // Success condition: HTTP status code is in the 200 range.
                if ( $save_log_response ) {
                    $logger->info( 'TFBDashboard API Account Creation Response: ' . $body, $context );
                }
                $order->add_order_note( __( 'TFBDashboard API Account Creation successful.', 'tfbdashboard' ) );
            } else {
                // Failure condition: non-success HTTP status code.
                if ( $save_log_response ) {
                    $logger->error( 'TFBDashboard API Account Creation Error: ' . $body, $context );
                }
                // Optionally, decode the response body to extract a more meaningful error message.
                $decoded = json_decode( $body, true );
                $message = isset( $decoded['message'] ) ? $decoded['message'] : 'Unknown error';
                $order->add_order_note( sprintf( __( 'TFBDashboard API Account Creation failed: %s', 'tfbdashboard' ), $message ) );
            }
        }


        // Mark the order as having been processed.
        update_post_meta( $order_id, '_tfbdashboard_connection_completed', 1 );
        // Remove the transient lock.
        delete_transient( 'send_api_lock_' . $order_id );
    }
}