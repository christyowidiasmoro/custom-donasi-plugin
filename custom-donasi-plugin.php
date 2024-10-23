<?php
/*
Plugin Name: Custom Donasi Plugin
Description: Customizations for donasi.generasibaru.nl
Version: 1.0
Author: Christyowidiasmoro
License: GPL2
GitHub Plugin URI: https://github.com/christyowidiasmoro/custom-donasi-plugin
GitHub Branch: master
*/

// Prevent direct access to the file
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Define Plugin Constants
define( 'CUSTOM_DONASI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_DONASI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once CUSTOM_DONASI_PLUGIN_PATH . 'includes/class-restricted-category-plugin.php';
require_once CUSTOM_DONASI_PLUGIN_PATH . 'includes/class-restricted-payment-gateway-plugin.php';
require_once CUSTOM_DONASI_PLUGIN_PATH . 'includes/class-open-price-product-plugin.php';
require_once CUSTOM_DONASI_PLUGIN_PATH . 'includes/class-mollie-subscription-payment-plugin.php';

// Initialize the plugin
function custom_donasi_plugin_init() {
    $restricted_category_plugin = new Restricted_Category_Plugin();
    $restricted_category_plugin->run();
    
    $restricted_payment_gateway_plugin = new Restricted_Payment_Gateway_Plugin();
    $restricted_payment_gateway_plugin->run();

    $open_price_product_plugin = new Open_Price_Product_Plugin();
    $open_price_product_plugin->run();

    $mollie_subscription_payment_plugin = new Mollie_Subscription_Payment_Plugin();
    $mollie_subscription_payment_plugin->run();

    // Include admin script if in admin area
    if ( is_admin() ) {
        require_once CUSTOM_DONASI_PLUGIN_PATH . 'admin/class-restricted-category-plugin-admin.php';
        $restricted_category_plugin_admin = new Restricted_Category_Plugin_Admin();
        $restricted_category_plugin_admin->run();

        require_once CUSTOM_DONASI_PLUGIN_PATH . 'admin/class-restricted-payment-gateway-plugin-admin.php';
        $restricted_payment_gateway_plugin_admin = new Restricted_Payment_Gateway_Plugin_Admin();
        $restricted_payment_gateway_plugin_admin->run();

        require_once CUSTOM_DONASI_PLUGIN_PATH . 'admin/class-open-price-product-plugin-admin.php';
        $open_price_product_plugin_admin = new Open_Price_Product_Plugin_Admin();
        $open_price_product_plugin_admin->run();

        require_once CUSTOM_DONASI_PLUGIN_PATH . 'admin/class-mollie-subscription-payment-plugin-admin.php';
        $mollie_subscription_payment_plugin_admin = new Mollie_Subscription_Payment_Plugin_Admin();
        $mollie_subscription_payment_plugin_admin->run();

        require_once CUSTOM_DONASI_PLUGIN_PATH . 'admin/class-custom-plugin-admin.php';
        $custom_plugin_admin = new Custom_Plugin_Admin(
            $restricted_category_plugin_admin,
            $mollie_subscription_payment_plugin_admin
        );
        $custom_plugin_admin->run();
    }
}
add_action( 'plugins_loaded', 'custom_donasi_plugin_init' );

