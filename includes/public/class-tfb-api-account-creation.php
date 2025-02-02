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
     * ensuring the API call is sent only once.
     *
     * @param int      $order_id
     * @param string   $old_status
     * @param string   $new_status
     * @param WC_Order $order
     */
    public function tfbdashboard_order_status_changed( $order_id, $old_status, $new_status, $order ) {
        // Only trigger if the status changes to 'completed' from a different status.
        if ( 'completed' !== $new_status || 'completed' === $old_status ) {
            return;
        }

        // Check if the API call has already been made.
        $connection_completed = get_post_meta( $order_id, '_tfbdashboard_connection_completed', true );
        if ( 1 == $connection_completed ) {
            return;
        }

        // Check for a transient lock to prevent duplicate API calls.
        if ( false !== get_transient( 'send_api_lock_' . $order_id ) ) {
            return;
        }
        set_transient( 'send_api_lock_' . $order_id, true, 3 );

        // Retrieve environment settings.
        $environment = get_option( 'tfbdashboard_environment', 'sandbox' );
        if ( 'live' === $environment ) {
            $base_url = get_option( 'tfbdashboard_live_endpoint', 'https://gateway-dev.thefundedbettor.com' );
            $api_key  = get_option( 'tfbdashboard_live_api_key', 'YOUR_LIVE_API_KEY' );
        } else {
            $base_url = get_option( 'tfbdashboard_sandbox_endpoint', 'https://gateway-dev.thefundedbettor.comhttps://gateway-dev.thefundedbettor.com' );
            $api_key  = get_option( 'tfbdashboard_sandbox_test_key', 'YOUR_SANDBOX_API_KEY' );
        }

        // Construct the full API endpoint URL.
        $api_endpoint = trailingslashit( $base_url ) . 'api/source/challenge-accounts';
        
        $api_data = array(
            'order_id'           => $order_id,
            'challengePricingId' => $order->get_meta( 'challengePricingId', true ),
            'stageId'            => $order->get_meta( 'stageId', true ),
            'userEmail'          => $order->get_billing_email(),
            'brandId'            => $order->get_meta( 'brandId', true ),
        );


        // Get a masked version of the API key.
        $masked_api_key = TFBDashboard_Helper::tfbdashboard_connection_mask_api_key( $api_key );

        // Retrieve logger and context.
        $save_log_response = get_option( 'tfbdashboard_save_log_response', 1 );
        $logger_data       = TFBDashboard_Helper::tfbdashboard_connection_response_logger();
        $logger            = $logger_data['logger'];
        $context           = $logger_data['context'];

        // Log the API call details.
        if ( $save_log_response ) {
            $api_call_log  = "--Begin TFBDashboard API Call --\n";
            $api_call_log .= "Endpoint URL: " . $api_endpoint . "\n";
            $api_call_log .= "API Key: " . $masked_api_key . "\n";
            $api_call_log .= "Body: " . json_encode( $api_data ) . "\n";
            $api_call_log .= "--End Log--";
            $logger->info( 'TFBDashboard API Account Creation API call :' . "\n" . $api_call_log, $context );
        }

        // Send the API request using the helper function.
        $result = TFBDashboard_Helper::tfbdashboard_send_wp_remote_post_request( $api_endpoint, $api_key, $api_data );
        $http_status  = $result['http_status'];
        $api_response = $result['api_response'];

        // Prepare and log the API response.
        if ( $save_log_response ) {
            $api_response_log  = "--Begin TFBDashboard API Response--\n";
            $api_response_log .= "Response: " . $api_response . "\n";
            $api_response_log .= "--End Response--";
            if ( $http_status >= 200 && $http_status < 300 ) {
                $logger->info( 'TFBDashboard API Account Creation' . "\n" . $api_response_log, $context );
            } else {
                $logger->error( 'TFBDashboard API Account Creation' . "\n" . $api_response_log, $context );
            }
        }

        // Add an order note based on the HTTP status code.
        if ( $http_status >= 200 && $http_status < 300 ) {
            $order->add_order_note( __( 'TFBDashboard API Account Creation successful.', 'tfbdashboard' ) );
        } else {
            $decoded = json_decode( $api_response, true );
            $message = isset( $decoded['message'] ) ? $decoded['message'] : 'Unknown error';
            $order->add_order_note( sprintf( __( 'TFBDashboard API Account Creation failed: %s', 'tfbdashboard' ), $message ) );
        }

        // Mark the order as processed and remove the transient lock.
        update_post_meta( $order_id, '_tfbdashboard_connection_completed', 1 );
        delete_transient( 'send_api_lock_' . $order_id );
    }
}