<?php

// Check if the class 'Custom_Plugin_Admin' does not already exist
if ( !class_exists( 'Custom_Plugin_Admin' ) ) {
    // Define the 'Custom_Plugin_Admin' class
    class Custom_Plugin_Admin {
		private $restricted_category_plugin_admin;
		private $mollie_subscription_payment_plugin_admin;

        // Constructor method
        public function __construct(
			$restricted_category_plugin_admin,
			$mollie_subscription_payment_plugin_admin
		) {
            // Constructor code here
			$this->restricted_category_plugin_admin = $restricted_category_plugin_admin;
			$this->mollie_subscription_payment_plugin_admin = $mollie_subscription_payment_plugin_admin;
        }

        // Method to run the admin plugin
        public function run() {
            // Hook to add admin menu
            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        }

		// Method to add multi-level admin menu
		public function add_admin_menu() {
			add_menu_page(
				'Custom Donasi Options',         // Page title
				'Custom Donasi',                 // Menu title
				'manage_options',                // Capability
				'custom-donasi-settings',        // Menu slug
				null, // Callback function
				'dashicons-admin-generic',       // Icon
				60                               // Position
			);

			add_submenu_page(
				'custom-donasi-settings',		 // Parent slug
				'Custom Product Rules Options',	 // Page title
				'Product Rules',          // Menu title
				'manage_options',                // Capability
				'custom-donasi-settings',		 // Menu slug
				[ $this->restricted_category_plugin_admin, 'category_plugin_admin_page' ] // Callback function
			);

			add_submenu_page(
				'custom-donasi-settings',			// Parent slug
				'Custom Payment Gateway Options',   // Page title
				'Payment Gateway',    		// Menu title
				'manage_options',                	// Capability
				'custom-payment-gateway',    		// Menu slug
				[ $this->mollie_subscription_payment_plugin_admin, 'mollie_subscription_payment_plugin_admin_page' ] // Callback function
			);
		}    
    }
}
