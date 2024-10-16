<?php

if ( !class_exists( 'Custom_Donasi_Plugin' ) ) {
    class Custom_Donasi_Plugin {

        public function __construct() {
            // Constructor code here
        }

        public function run() {
            // Hook for frontend customizations
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

            // Hook for admin customizations
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        }

        public function enqueue_frontend_scripts() {
            // Enqueue frontend styles and scripts
            wp_enqueue_style( 'custom-donasi-style', CUSTOM_DONASI_PLUGIN_URL . 'assets/css/style.css' );
            wp_enqueue_script( 'custom-donasi-script', CUSTOM_DONASI_PLUGIN_URL . 'assets/js/script.js', [], false, true );
        }

        public function enqueue_admin_scripts() {
            // Enqueue admin panel styles and scripts
            wp_enqueue_style( 'custom-donasi-admin-style', CUSTOM_DONASI_PLUGIN_URL . 'assets/css/admin-style.css' );
            wp_enqueue_script( 'custom-donasi-admin-script', CUSTOM_DONASI_PLUGIN_URL . 'assets/js/admin-script.js', [], false, true );
        }
    }
}
