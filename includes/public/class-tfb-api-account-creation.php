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
     * Trigger challenge account creation when an order is completed.
     *
     * @param int      $order_id
     * @param string   $old_status
     * @param string   $new_status
     * @param WC_Order $order
     */
    public function tfbdashboard_order_status_changed( $order_id, $old_status, $new_status, $order ) {
        // Only trigger when the order is marked as completed.
        if ( 'completed' !== $new_status ) {
            return;
        }

        $api_url = 'https://gateway-dev.thefundedbettor.com/api/source/challenge-accounts';

        // Prepare the POST arguments.
        $args = array(
            'method'  => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer YOUR_SECRET_TOKEN', // Replace with your actual secret token.
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'order_id'           => $order_id,
                'challengePricingId' => $order->get_meta( 'challengePricingId' ),
                'stageId'            => $order->get_meta( 'stageId' ),
                'userEmail'          => $order->get_meta( 'userEmail' ),
                'brandId'            => $order->get_meta( 'brandId' ),
                // Add more fields as needed.
            ) ),
        );

        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'TFBDashboard API Account Creation Error: ' . $response->get_error_message() );
        } else {
            error_log( 'TFBDashboard API Account Creation Response: ' . wp_remote_retrieve_body( $response ) );
        }
    }
}
