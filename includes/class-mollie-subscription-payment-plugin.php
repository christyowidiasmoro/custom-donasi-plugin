<?php

// Check if the class 'Mollie_Subscription_Payment_Plugin' does not already exist
if ( !class_exists( 'Mollie_Subscription_Payment_Plugin' ) ) {
    // Define the 'Mollie_Subscription_Payment_Plugin' class
    class Mollie_Subscription_Payment_Plugin {

        // Constructor method
        public function __construct() {
            // Constructor code here
        }

        // Method to run the plugin
        public function run() {
            // Hook to add the Mollie payment gateway
            add_filter( 'woocommerce_payment_gateways', [ $this, 'add_mollie_payment_gateway' ] );

            $this->init_mollie_subscription_gateway();

        }

        // Method to add the Mollie payment gateway
        public function add_mollie_payment_gateway( $gateways ) {
            $gateways[] = 'WC_Gateway_Mollie_Subscription';
            return $gateways;
        }

        function init_mollie_subscription_gateway() {
            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
            }

            require_once __DIR__ . '/class-gateway-mollie-subscription-plugin.php';
        }



	}
}
