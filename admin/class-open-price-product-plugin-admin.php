<?php
// Check if the class 'Open_Price_Product_Plugin_Admin' does not already exist
if ( !class_exists( 'Open_Price_Product_Plugin_Admin' ) ) {
    // Define the 'Open_Price_Product_Plugin_Admin' class
	class Open_Price_Product_Plugin_Admin {

		// Constructor method
		public function __construct() {
			// Constructor code here
		}

		// Method to run the admin plugin
		public function run() {
            add_action( 'woocommerce_product_options_pricing', [ $this, 'custom_donasi_add_custom_fields' ] );

            add_action( 'woocommerce_process_product_meta', [ $this, 'custom_donasi_save_custom_fields' ] );
		}

        // Add custom fields to the product data metabox
        function custom_donasi_add_custom_fields() {
            echo '<div class="options_group">';

            // Enable/Disable Open Price
            woocommerce_wp_checkbox( array(
                'id'            => '_enable_open_price',
                'label'         => __( 'Enable Open Price', 'woocommerce' ),
                'description'   => __( 'Allow customers to enter their own price.', 'woocommerce' ),
            ) );

            // Minimum Price
            woocommerce_wp_text_input( array(
                'id'            => '_min_open_price',
                'label'         => __( 'Minimum Price', 'woocommerce' ),
                'description'   => __( 'Set the minimum price for the open price feature.', 'woocommerce' ),
                'type'          => 'number',
                'custom_attributes' => array(
                    'step'  => '0.01',
                    'min'   => '0'
                )
            ) );

            // Maximum Price
            woocommerce_wp_text_input( array(
                'id'            => '_max_open_price',
                'label'         => __( 'Maximum Price', 'woocommerce' ),
                'description'   => __( 'Set the maximum price for the open price feature.', 'woocommerce' ),
                'type'          => 'number',
                'custom_attributes' => array(
                    'step'  => '0.01',
                    'min'   => '0'
                )
            ) );

            // Label for Open Price
            $label_open_price = get_post_meta( get_the_ID(), '_label_open_price', true );
            woocommerce_wp_text_input( array(
                'id'            => '_label_open_price',
                'label'         => __( 'Label Price', 'woocommerce' ),
                'description'   => __( 'Set the label for the open price feature.', 'woocommerce' ),
                'type'          => 'text',
                'placeholder'   => 'Enter your price'
            ) );

            echo '</div>';
        }

        // Save custom field values
        function custom_donasi_save_custom_fields( $post_id ) {
            $enable_open_price = isset( $_POST['_enable_open_price'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_enable_open_price', $enable_open_price );

            if ( isset( $_POST['_min_open_price'] ) ) {
                update_post_meta( $post_id, '_min_open_price', sanitize_text_field( $_POST['_min_open_price'] ) );
            }

            if ( isset( $_POST['_max_open_price'] ) ) {
                update_post_meta( $post_id, '_max_open_price', sanitize_text_field( $_POST['_max_open_price'] ) );
            }

            if ( isset( $_POST['_label_open_price'] ) ) {
                update_post_meta( $post_id, '_label_open_price', sanitize_text_field( $_POST['_label_open_price'] ) );
            }
        }
	}
}
