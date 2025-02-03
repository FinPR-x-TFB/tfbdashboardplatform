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
        add_action( 'woocommerce_rest_insert_order', array( $this, 'tfbdashboard_save_custom_order_fields' ), 10, 2 );

    
        // Use filter to handle customer linking/creation before order insert.
        add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'handle_order_customer' ), 10, 2 );
        add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );
        add_filter( 'woocommerce_email_enabled_admin_new_user', '__return_false' );
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
                    return $this->tfbdashboard_get_custom_field_callback( $order, $field );
                },
                'update_callback' => function( $value, $order, $field_name ) use ( $field ) {
                    return $this->tfbdashboard_update_custom_field_callback( $value, $order, $field_name, $field );
                },
                'schema'          => array(
                    'description' => $args['description'],
                    'type'        => $args['type'],
                    'context'     => array( 'view', 'edit' ),
                    'required'    => true,
                ),
            ) );
        }
    }


    /**
     * Get callback for custom fields.
     *
     * @param array $order The order array from the REST response.
     * @param string $field The custom field name.
     * @return mixed
     */
    public function tfbdashboard_get_custom_field_callback( $order, $field ) {
        return get_post_meta( $order['id'], $field, true );
    }
    
    /**
     * Update callback for custom fields with validation.
     *
     * If the field is "userEmail" and the value is empty, it falls back to the billing email.
     * If the final value is empty, it returns a WP_Error.
     *
     * @param mixed $value The new value.
     * @param WC_Order $order The order object.
     * @param string $field_name The meta key.
     * @param string $field The custom field name.
     * @return void|WP_Error
     */
    public function tfbdashboard_update_custom_field_callback( $value, $order, $field_name, $field ) {
        // For userEmail, if empty, use the billing email.
        if ( 'userEmail' === $field && empty( $value ) ) {
            $value = $order->get_billing_email();
        }
        
        // Trim the value to catch empty strings.
        $value = trim( $value );
        if ( empty( $value ) ) {
            return new WP_Error( 'rest_order_field_required', sprintf( __( '%s is required.', 'tfbdashboard' ), $field_name ), array( 'status' => 400 ) );
        }
        
        update_post_meta( $order->get_id(), $field_name, sanitize_text_field( $value ) );
    }
    
    /**
     * Additional validation using the validation filter.
     * (This is optional if you want to perform separate validation.)
     */
    public function tfbdashboard_validate_custom_order_fields( $errors, $request ) {
        $fields = array( 'challengePricingId', 'stageId', 'userEmail', 'brandId' );
        foreach ( $fields as $field ) {
            $value = trim( $request->get_param( $field ) );
            if ( '' === $value ) {
                $errors->add( 'missing_' . $field, sprintf( __( '%s is required.', 'tfbdashboard' ), $field ) );
            }
        }
        return $errors;
    }
    
    /**
     * Save custom fields into order meta.
     */
    public function tfbdashboard_save_custom_order_fields( $order, $request ) {
        $fields = array( 'challengePricingId', 'stageId', 'userEmail', 'brandId' );
        foreach ( $fields as $field ) {
            $value = trim( $request->get_param( $field ) );
            if ( ! empty( $value ) ) {
                $order->update_meta_data( $field, sanitize_text_field( $value ) );
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