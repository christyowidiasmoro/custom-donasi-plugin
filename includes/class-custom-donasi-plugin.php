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
            // Get restricted category groups from admin settings
            $restricted_groups = get_option( 'restricted_category_groups', [] );
        
            if ( empty( $restricted_groups ) ) {
                return; // No groups to restrict
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
            // Register the new setting for restricted category groups
            register_setting( 'donasi-settings-group', 'restricted_category_groups' );
        
            // Add a section in the admin settings page
            add_settings_section(
                'donasi-settings-section',
                'Category Restriction Settings',
                null,
                'donasi-settings'
            );

            // Add the settings field for restricted category groups
            add_settings_field(
                'restricted_category_groups',
                'Restricted Category Groups',
                [ $this, 'restricted_categories_callback' ],
                'donasi-settings',
                'donasi-settings-section'
            );
        }

        public function restricted_categories_callback() {
            $restricted_groups = get_option( 'restricted_category_groups', [] );
            $categories = get_terms( [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            ] );
        
            echo '<div id="restricted-category-groups">';
        
            if ( !empty( $restricted_groups ) ) {
                foreach ( $restricted_groups as $index => $group ) {
                    echo '<div class="restricted-group" data-index="' . esc_attr( $index ) . '">';
                    echo '<h4>Group ' . ( (int) $index + 1 ) . '</h4>';
                    echo $this->render_category_group( $group, $categories, $index );
                    echo '<button type="button" class="button delete-group">Delete Group</button>';
                    echo '</div>';
                }
            }
        
            echo '</div>';
        
            // Button to add new groups
            echo '<button type="button" class="button" id="add-group">Add Group</button>';
            echo '<script>
                var groupIndex = ' . count($restricted_groups) . ';
                jQuery("#add-group").click(function() {
                    groupIndex++;
                    jQuery("#restricted-category-groups").append(`<div class="restricted-group" data-index="${groupIndex}"><h4>Group ${groupIndex}</h4>` + ' . json_encode($this->render_category_group([], $categories, 'new')) . ' + `<button type="button" class="button delete-group">Delete Group</button></div>`);
                });
                jQuery(document).on("click", ".delete-group", function() {
                    jQuery(this).closest(".restricted-group").remove();
                });
            </script>';
        }

        public function render_category_group( $group, $categories, $index ) {
            ob_start();
            foreach ( $categories as $category ) {
                ?>
                <label>
                    <input type="checkbox" name="restricted_category_groups[<?php echo esc_attr( $index ); ?>][]"
                        value="<?php echo esc_attr( $category->slug ); ?>"
                        <?php echo in_array( $category->slug, $group ) ? 'checked' : ''; ?>>
                    <?php echo esc_html( $category->name ); ?>
                </label><br/>
                <?php
            }
            return ob_get_clean();
        }
        
                
    }
}