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
        add_action( 'woocommerce_rest_insert_order', array( $this, 'tfbdashboard_set_or_create_customer_based_on_email' ), 20, 2 );
    }

    public function tfbdashboard_register_custom_order_fields() {
        $custom_fields = array(
            'challengePricingId' => array(
                'description' => __( 'Challenge Pricing ID', 'tfbdashboard' ),
                'type'        => 'string',
            ),
            'stageId' => array(
                'description' => __( 'Stage ID', 'tfbdashboard' ),
                'type'        => 'string',
            ),
            'userEmail' => array(
                'description' => __( 'User Email', 'tfbdashboard' ),
                'type'        => 'string',
            ),
            'brandId' => array(
                'description' => __( 'Brand ID', 'tfbdashboard' ),
                'type'        => 'string',
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
                    'context'     => array( 'view', 'edit' ),
                ),
            ) );
        }
    }

    public function tfbdashboard_validate_custom_order_fields( $prepared_post, $request ) {
        $required_fields = array( 'challengePricingId', 'stageId', 'brandId' );
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
     * Checks the billing email on the order. If a user with that email does not exist,
     * creates a new user with a strong random password (silently, with no notification).
     * Then sets the order's customer_id to the new (or existing) user ID.
     *
     * @param WC_Order        $order   The order object.
     * @param WP_REST_Request $request The REST API request.
     */
    public function tfbdashboard_set_or_create_customer_based_on_email( $order, $request ) {
        $billing_email = $order->get_billing_email();
        if ( empty( $billing_email ) ) {
            return;
        }

        // Check if a user already exists with this billing email.
        $user = get_user_by( 'email', $billing_email );
        if ( ! $user ) {
            // No user found; create one silently.
            // Generate a username by replacing "@" with "_" from the billing email.
            $username = sanitize_user( str_replace( '@', '_', $billing_email ) );
            $original_username = $username;
            $i = 1;
            while ( username_exists( $username ) ) {
                $username = $original_username . '_' . $i;
                $i++;
            }

            // Generate a strong random password (using the helper function).
            $password = TFBDashboard_Helper::tfbdashboard_generate_strong_random_password( 12 );

            // Create the user silently.
            $user_id = wp_create_user( $username, $password, $billing_email );
            if ( ! is_wp_error( $user_id ) ) {
                // Set the order's customer ID.
                $order->set_customer_id( $user_id );
                $order->save();
                // Ensure the underlying meta is updated.
                update_post_meta( $order->get_id(), '_customer_user', $user_id );
            }
        } else {
            // User exists; set the order's customer_id.
            $order->set_customer_id( $user->ID );
            $order->save();
            // Also update the meta.
            update_post_meta( $order->get_id(), '_customer_user', $user->ID );
        }
    }
}