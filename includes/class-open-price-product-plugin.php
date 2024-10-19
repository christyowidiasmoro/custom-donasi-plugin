<?php

// Check if the class 'Open_Price_Product_Plugin' does not already exist
if ( !class_exists( 'Open_Price_Product_Plugin' ) ) {
    // Define the 'Open_Price_Product_Plugin' class
    class Open_Price_Product_Plugin {

        // Constructor method
        public function __construct() {
            // Constructor code here
        }

        // Method to run the plugin
        public function run() {
            // Hook for frontend customizations
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

            add_filter( 'woocommerce_get_price_html',             array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );

            add_filter( 'woocommerce_get_variation_price_html',   array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );

            add_filter( 'woocommerce_is_sold_individually',       array( $this, 'hide_quantity_input_field' ), PHP_INT_MAX, 2 );

            add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'custom_donasi_price_input' ] );

            add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'custom_donasi_validate_price' ], 10, 3 );

            add_filter( 'woocommerce_add_cart_item_data', [ $this, 'custom_donasi_add_custom_price' ], 10, 3 );

            add_action( 'woocommerce_before_calculate_totals', [ $this, 'custom_donasi_set_custom_price' ] );
          }

        // Method to enqueue frontend styles and scripts
        public function enqueue_frontend_scripts() {
            wp_enqueue_style( 'open-price-product-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
            wp_enqueue_script( 'open-price-product-script', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', ['jquery'], false, true );
        }

        function hide_original_price( $price, $_product ) {
          $enable_open_price = get_post_meta( $_product->get_id(), '_enable_open_price', true );
          if ( $enable_open_price === 'yes' ) {
            $price = '';
          }
      
          return $price;
        }

        function hide_quantity_input_field( $return, $_product ) {
          $enable_open_price = get_post_meta( $_product->get_id(), '_enable_open_price', true );
          if ( $enable_open_price === 'yes' ) {
            return true;
          }
          return;
        }      

        // Add custom price input field on the product page
        function custom_donasi_price_input() {
          global $product;
          $enable_open_price = get_post_meta( $product->get_id(), '_enable_open_price', true );
          $min_open_price = get_post_meta( $product->get_id(), '_min_open_price', true );
          $max_open_price = get_post_meta( $product->get_id(), '_max_open_price', true );
          $regular_price = $product->get_regular_price();
          $currency_label_open_price = get_post_meta( $product->get_id(), '_currency_label_open_price', true );
          $label_open_price = get_post_meta( $product->get_id(), '_label_open_price', true );

          if ( $enable_open_price === 'yes' ) {
              remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );

              // Hide the quantity input field
              echo '<style>
                   .input-price-donasi {
                        position: relative;
                        display: inline-block;
                    }
                    .input-price-donasi input {
                        padding-left: ' . ( 10 + strlen(esc_attr($currency_label_open_price)) * 15) . 'px !important;
                    }
                    .input-price-donasi:before {
                        position: absolute;
                        top: 25%;
                        content:"' . esc_attr( $currency_label_open_price ) . '";
                        left: 10px;
                    }
              </style>';

              echo '<span class="custom-price-donasi">
                      <label for="custom_price">' . esc_attr( $label_open_price ) . '</label>                      
                      <span class="input-price-donasi">
                        <input type="number" id="custom_price" name="custom_price" min="' . esc_attr( $min_open_price ) . '" max="' . esc_attr( $max_open_price ) . '" step="0.01" class="input-text text" value="' . esc_attr( $regular_price ) . '">
                      </span>
                    </span>';
          }
        }

        // Validate custom price input
        function custom_donasi_validate_price( $passed, $product_id, $quantity ) {
          $enable_open_price = get_post_meta( $product_id, '_enable_open_price', true );
          $min_open_price = get_post_meta( $product_id, '_min_open_price', true );
          $max_open_price = get_post_meta( $product_id, '_max_open_price', true );

          if ( $enable_open_price === 'yes' && isset( $_POST['custom_price'] ) ) {
              $custom_price = floatval( $_POST['custom_price'] );
              if ( $custom_price < $min_open_price || $custom_price > $max_open_price ) {
                wc_add_notice( 'Please enter a price between ' . $min_open_price . ' and ' . $max_open_price . '.', 'error' );
                return false;
              }
          }
          return $passed;
        }

        // Add custom price to cart item data
        function custom_donasi_add_custom_price( $cart_item_data, $product_id, $variation_id ) {
          if ( isset( $_POST['custom_price'] ) ) {
              $cart_item_data['custom_price'] = $_POST['custom_price'];
              $cart_item_data['unique_key'] = md5( microtime().rand() ); // Ensure unique key for each item
          }
          return $cart_item_data;
        }

        // Set custom price in cart
        function custom_donasi_set_custom_price( $cart_object ) {
          foreach ( $cart_object->get_cart() as $cart_item ) {
              if ( isset( $cart_item['custom_price'] ) ) {
                  $cart_item['data']->set_price( $cart_item['custom_price'] );
              }
          }
        }
    }
}
