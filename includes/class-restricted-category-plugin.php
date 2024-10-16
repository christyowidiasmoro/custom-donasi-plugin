<?php

// Check if the class 'Restricted_Category_Plugin' does not already exist
if ( !class_exists( 'Restricted_Category_Plugin' ) ) {
    // Define the 'Restricted_Category_Plugin' class
    class Restricted_Category_Plugin {

        // Constructor method
        public function __construct() {
            // Constructor code here
        }

        // Method to run the plugin
        public function run() {
            // Hook for frontend customizations
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

            // Hook to restrict cart items based on categories
            add_action( 'woocommerce_check_cart_items', [ $this, 'restrict_cart_multiple_categories' ] );

            // Hook to validate product addition to cart
            add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 3 );
        }

        // Method to enqueue frontend styles and scripts
        public function enqueue_frontend_scripts() {
            wp_enqueue_style( 'restricted-category-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
            wp_enqueue_script( 'restricted-category-script', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', ['jquery'], false, true );
        }

        // Method to restrict cart items based on multiple categories
        public function restrict_cart_multiple_categories() {
            // Get restricted category groups from admin settings
            $restricted_groups = get_option( 'restricted_category_groups', [] );
        
            // If there are no restricted groups, return
            if ( empty( $restricted_groups ) ) {
                return;
            }
        
            // Get all categories in the current cart
            $categories_in_cart = [];
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product_id = $cart_item['product_id'];
                $product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'slugs' ] );
                $categories_in_cart = array_merge( $categories_in_cart, $product_categories );
            }
        
            // Get unique categories
            $unique_categories_in_cart = array_unique( $categories_in_cart );
        
            // Check if any restricted group is violated
            foreach ( $restricted_groups as $group ) {
                $categories_in_group = array_intersect( $unique_categories_in_cart, $group );
                if ( count( $categories_in_group ) > 1 ) {
                    wc_add_notice( 'You cannot add products from multiple categories within the same restricted group. Please remove one to proceed.', 'error' );
                    return;
                }
            }
        }                
        // Method to validate product addition to cart
        public function validate_add_to_cart( $passed, $product_id, $quantity ) {
            // Get restricted category groups from admin settings
            $restricted_groups = get_option( 'restricted_category_groups', [] );
        
            if ( empty( $restricted_groups ) ) {
                return $passed; // No groups to restrict
            }
        
            // Get all categories in the current cart
            $categories_in_cart = [];
        
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $cart_product_id = $cart_item['product_id'];
                $product_categories = wp_get_post_terms( $cart_product_id, 'product_cat', [ 'fields' => 'slugs' ] );
                $categories_in_cart = array_merge( $categories_in_cart, $product_categories );
            }
        
            // Get categories of the product being added
            $new_product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'slugs' ] );
            $categories_in_cart = array_merge( $categories_in_cart, $new_product_categories );
        
            // Get unique categories
            $unique_categories_in_cart = array_unique( $categories_in_cart );
        
            // Check if any restricted group is violated
            foreach ( $restricted_groups as $group ) {
                $categories_in_group = array_intersect( $unique_categories_in_cart, $group );
                if ( count( $categories_in_group ) > 1 ) {
                    wc_add_notice( 'You cannot add products from multiple categories within the same restricted group. Please remove one to proceed.', 'error' );
                    return false;
                }
            }
        
            return $passed;
        }
	}
}
