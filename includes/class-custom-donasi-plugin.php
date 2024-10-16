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

            add_action( 'woocommerce_check_cart_items', [ $this, 'restrict_cart_multiple_categories' ] );

            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        }

        public function enqueue_frontend_scripts() {
            // Enqueue frontend styles and scripts
            wp_enqueue_style( 'custom-donasi-style', CUSTOM_DONASI_PLUGIN_URL . 'assets/css/style.css' );
            wp_enqueue_script( 'custom-donasi-script', CUSTOM_DONASI_PLUGIN_URL . 'assets/js/script.js', ['jquery'], false, true );
        }

        public function enqueue_admin_scripts() {
            // Enqueue admin panel styles and scripts
            wp_enqueue_style( 'custom-donasi-admin-style', CUSTOM_DONASI_PLUGIN_URL . 'assets/css/admin-style.css' );
            wp_enqueue_script( 'custom-donasi-admin-script', CUSTOM_DONASI_PLUGIN_URL . 'assets/js/admin-script.js', [], false, true );
        }

        public function restrict_cart_multiple_categories() {
            // Get restricted categories from admin settings
            $restricted_categories = get_option( 'restricted_categories', [] );
        
            if ( empty( $restricted_categories ) ) {
                return;
            }
        
            // Get product categories in the current cart
            $categories_in_cart = array();
        
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product_id = $cart_item['product_id'];
                $product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
                $categories_in_cart = array_merge( $categories_in_cart, $product_categories );
            }
        
            // Get unique categories
            $unique_categories = array_intersect( array_unique( $categories_in_cart ), $restricted_categories );
        
            // Check if more than one restricted category is in the cart
            if ( count( $unique_categories ) > 1 ) {
                wc_add_notice( 'You cannot add products from multiple restricted categories to the cart. Please remove one to proceed.', 'error' );
            }
        }
        

        public function add_admin_menu() {
            add_menu_page(
                'Restricted Categories',         // Page title
                'Donasi Settings',               // Menu title
                'manage_options',                // Capability
                'donasi-settings',               // Menu slug
                [ $this, 'admin_settings_page' ], // Callback function
                'dashicons-admin-generic',       // Icon
                60                               // Position
            );
        }

        public function admin_settings_page() {
            ?>
            <div class="wrap">
                <h1>Customize Restricted Categories</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'donasi-settings-group' );
                    do_settings_sections( 'donasi-settings' );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
        
        public function register_settings() {
            // Register the setting
            register_setting( 'donasi-settings-group', 'restricted_categories' );
        
            // Add a section in the admin settings page
            add_settings_section(
                'donasi-settings-section',
                'Category Restriction Settings',
                null,
                'donasi-settings'
            );
        
            // Add the settings field to allow category selection
            add_settings_field(
                'restricted_categories',
                'Restricted Categories',
                [ $this, 'restricted_categories_callback' ],
                'donasi-settings',
                'donasi-settings-section'
            );
        }        

        public function restricted_categories_callback() {
            $selected_categories = get_option( 'restricted_categories', [] );
            $categories = get_terms( [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            ] );
        
            if ( !empty( $categories ) ) {
                foreach ( $categories as $category ) {
                    ?>
                    <label>
                        <input type="checkbox" name="restricted_categories[]" value="<?php echo esc_attr( $category->slug ); ?>"
                        <?php echo in_array( $category->slug, (array) $selected_categories ) ? 'checked' : ''; ?>>
                        <?php echo esc_html( $category->name ); ?>
                    </label><br/>
                    <?php
                }
            } else {
                echo '<p>No categories found.</p>';
            }
        }
        
        
    }
}
