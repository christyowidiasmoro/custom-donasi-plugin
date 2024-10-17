<?php

// Check if the class 'Restricted_Payment_Gateway_Plugin' does not already exist
if ( !class_exists( 'Restricted_Payment_Gateway_Plugin' ) ) {
    // Define the 'Restricted_Payment_Gateway_Plugin' class
    class Restricted_Payment_Gateway_Plugin {

        // Constructor method
        public function __construct() {
            // Constructor code here
        }

        // Method to run the plugin
        public function run() {
            // Hook for frontend customizations
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

            // Hook to filter available payment gateways
            add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_payment_gateways' ] );
        }

        // Method to enqueue frontend styles and scripts
        public function enqueue_frontend_scripts() {
            wp_enqueue_style( 'restricted-payment-gateway-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
            wp_enqueue_script( 'restricted-payment-gateway-script', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', ['jquery'], false, true );
        }

        // Method to filter available payment gateways
        public function filter_payment_gateways( $available_gateways ) {
            if ( is_admin() ) {
                return $available_gateways;
            }

            // Check if any product in the cart has a restricted payment gateway
            $product_in_cart = false;
            $allowed_gateway_id = '';

            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product_id = $cart_item['product_id'];
                $restricted_gateway = get_post_meta( $product_id, '_restricted_payment_gateway', true );

                if ( ! empty( $restricted_gateway ) ) {
                    $product_in_cart = true;
                    $allowed_gateway_id = $restricted_gateway;
                    break;
                }
            }

            // If the restricted product is in the cart, remove all other gateways
            if ( $product_in_cart ) {
                foreach ( $available_gateways as $gateway_id => $gateway ) {
                    if ( $gateway_id !== $allowed_gateway_id ) {
                        unset( $available_gateways[$gateway_id] );
                    }
                }
            }

            return $available_gateways;
        }

    }
}
