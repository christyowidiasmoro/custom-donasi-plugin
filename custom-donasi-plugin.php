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
require_once CUSTOM_DONASI_PLUGIN_PATH . 'includes/class-custom-donasi-plugin.php';

// Initialize the plugin
function custom_donasi_plugin_init() {
    $custom_donasi_plugin = new Custom_Donasi_Plugin();
    $custom_donasi_plugin->run();
}
add_action( 'plugins_loaded', 'custom_donasi_plugin_init' );

