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

class TFBDashboard_Rest_API_Order {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'tfbdashboard_register_custom_order_fields' ) );
        add_filter( 'rest_pre_insert_shop_order', array( $this, 'tfbdashboard_validate_custom_order_fields' ), 10, 2 );
        add_action( 'woocommerce_rest_insert_order', array( $this, 'tfbdashboard_save_custom_order_fields' ), 10, 2 );
        add_filter( 'woocommerce_rest_order_query', array( $this, 'tfbdashboard_filter_orders_by_billing_email' ), 10, 2 );
    }

    public function tfbdashboard_register_custom_order_fields() {
        $custom_fields = array(
            'challengePricingId' => array(
                'description' => __( 'Challenge Pricing ID', 'tfbdashboard' ),
                'type'        => 'string',
                'required'    => true,
            ),
            'stageId' => array(
                'description' => __( 'Stage ID', 'tfbdashboard' ),
                'type'        => 'string',
                'required'    => true,
            ),
            'userEmail' => array(
                'description' => __( 'User Email', 'tfbdashboard' ),
                'type'        => 'string',
                'required'    => true,
            ),
            'brandId' => array(
                'description' => __( 'Brand ID', 'tfbdashboard' ),
                'type'        => 'string',
                'required'    => true,
            ),
        );

        foreach ( $custom_fields as $field => $args ) {
            register_rest_field( 'shop_order', $field, array(
                'get_callback'    => function( $order ) use ( $field ) {
                    return get_post_meta( $order['id'], $field, true );
                },
                'update_callback' => function( $value, $order, $field_name ) {
                    if ( ! empty( $value ) ) {
                        update_post_meta( $order->get_id(), $field_name, sanitize_text_field( $value ) );
                    }
                },
                'schema'          => array(
                    'description' => $args['description'],
                    'type'        => $args['type'],
                    'required'    => true,
                    'context'     => array( 'view', 'edit' ),
                ),
            ));
        }
    }

    public function tfbdashboard_validate_custom_order_fields( $prepared_post, $request ) {
        $required_fields = array( 'challengePricingId', 'stageId', 'userEmail', 'brandId' );
        foreach ( $required_fields as $field ) {
            if ( empty( $request[ $field ] ) ) {
                return new WP_Error( 'rest_order_missing_field', sprintf( __( '%s is required.', 'tfbdashboard' ), $field ), array( 'status' => 400 ) );
            }
        }
        return $prepared_post;
    }

    public function tfbdashboard_save_custom_order_fields( $order, $request ) {
        $custom_fields = array( 'challengePricingId', 'stageId', 'userEmail', 'brandId' );
        foreach ( $custom_fields as $field ) {
            if ( isset( $request[ $field ] ) && ! empty( $request[ $field ] ) ) {
                $order->update_meta_data( $field, sanitize_text_field( $request[ $field ] ) );
            }
        }
    }

    /**
     * Filter orders by billing email if provided via the REST API.
     *
     * Allows API requests like:
     * /wp-json/wc/v3/orders?billing_email=john.doe@example.com
     *
     * @param array           $args    The query arguments.
     * @param WP_REST_Request $request The REST API request object.
     * @return array Modified query arguments.
     */
    public function tfbdashboard_filter_orders_by_billing_email( $args, $request ) {
        if ( ! empty( $request['billing_email'] ) ) {
            $billing_email = sanitize_text_field( $request['billing_email'] );
            // Ensure meta_query exists.
            if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
                $args['meta_query'] = array();
            }
            $args['meta_query'][] = array(
                'key'     => '_billing_email',
                'value'   => $billing_email,
                'compare' => '='
            );
        }
        return $args;
    }
}
