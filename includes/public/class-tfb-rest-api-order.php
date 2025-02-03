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
        add_filter( 'woocommerce_rest_create_order_validation', array( $this, 'tfbdashboard_validate_custom_order_fields' ), 10, 2 );
        //add_action( 'woocommerce_rest_insert_order', array( $this, 'tfbdashboard_save_custom_order_fields' ), 10, 2 );


            
        // Use filter to handle customer linking/creation before order insert.
        add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'handle_order_customer' ), 10, 2 );
        
        // Disable new user notification emails.
        add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );
        add_filter( 'woocommerce_email_enabled_admin_new_user', '__return_false' );
    }

    public function tfbdashboard_register_custom_order_fields() {
        $custom_fields = array(
            'challengePricingId' => array(
                'description' => __( 'Challenge Pricing ID', 'tfbdashboard' ),
                'type'        => 'string',
                'required' => true,
            ),
            'stageId' => array(
                'description' => __( 'Stage ID', 'tfbdashboard' ),
                'type'        => 'string',
                'required' => true,
            ),
            'userEmail' => array(
                'description' => __( 'User Email', 'tfbdashboard' ),
                'type'        => 'string',
                'required' => true,
            ),
            'brandId' => array(
                'description' => __( 'Brand ID', 'tfbdashboard' ),
                'type'        => 'string',
                'required' => true,
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
                    'required' => true,
                ),
            ) );
        }
    }


    public function tfbdashboard_validate_custom_order_fields( $errors, $request ) {
        // Retrieve each parameter using get_param() and trim it.
        $challengePricingId = trim( $request->get_param( 'challengePricingId' ) );
        $stageId            = trim( $request->get_param( 'stageId' ) );
        $userEmail          = trim( $request->get_param( 'userEmail' ) );
        $brandId            = trim( $request->get_param( 'brandId' ) );

        if ( '' === $challengePricingId ) {
            $errors->add( 'missing_challenge_pricing_id', __( 'Challenge Pricing ID is required.', 'tfbdashboard' ) );
        }
        if ( '' === $stageId ) {
            $errors->add( 'missing_stage_id', __( 'Stage ID is required.', 'tfbdashboard' ) );
        }
        if ( '' === $userEmail ) {
            $errors->add( 'missing_user_email', __( 'User email is required.', 'tfbdashboard' ) );
        }
        if ( '' === $brandId ) {
            $errors->add( 'missing_brand_id', __( 'Brand ID is required.', 'tfbdashboard' ) );
        }
        
        return $errors;
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
     * Handles the order's customer.
     *
     * Checks the billing email (from $request['billing']['email']). If a customer with that email exists,
     * sets the order's customer ID accordingly. Otherwise, creates a new user with a strong random password,
     * saves the billing information to the new user meta, and sets the order's customer ID.
     *
     * @param WC_Order        $order   The order object.
     * @param WP_REST_Request $request The REST API request.
     * @return WC_Order
     */
    public function handle_order_customer( $order, $request ) {
        $billing_email = isset( $request['billing']['email'] ) ? sanitize_email( $request['billing']['email'] ) : '';
        if ( empty( $billing_email ) ) {
            return $order;
        }

        // Check if a user exists with this billing email.
        $existing_customer_id = email_exists( $billing_email );
        
        try {
            if ( $existing_customer_id ) {
                $order->set_customer_id( $existing_customer_id );
            } else {
                // Create new customer.
                $username = sanitize_email( $billing_email );
                // Generate a strong random password (12 characters, using our helper function).
                $password = TFBDashboard_Helper::tfbdashboard_generate_strong_random_password( 12 );
                
                $customer_data = array(
                    'user_login'  => $username,
                    'user_email'  => $billing_email,
                    'user_pass'   => $password,
                    'role'        => 'customer',
                    'first_name'  => isset( $request['billing']['first_name'] ) ? sanitize_text_field( $request['billing']['first_name'] ) : '',
                    'last_name'   => isset( $request['billing']['last_name'] ) ? sanitize_text_field( $request['billing']['last_name'] ) : ''
                );
                
                // Temporarily disable new user notification emails.
                remove_all_actions( 'user_register' );
                remove_all_actions( 'wp_new_user_notification' );
                
                $customer_id = wp_insert_user( $customer_data );
                if ( is_wp_error( $customer_id ) ) {
                    return $order;
                }
                
                $order->set_customer_id( $customer_id );
                
                // Save additional billing fields to the new user's meta.
                $billing_fields = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' );
                foreach ( $billing_fields as $field ) {
                    if ( isset( $request['billing'][ $field ] ) ) {
                        update_user_meta( $customer_id, 'billing_' . $field, sanitize_text_field( $request['billing'][ $field ] ) );
                    }
                }
            }
            
            // Save the updated order.
            $order->save();
        } catch ( Exception $e ) {
            // Optional: log or handle exception as needed.
        }
        
        return $order;
    }
}