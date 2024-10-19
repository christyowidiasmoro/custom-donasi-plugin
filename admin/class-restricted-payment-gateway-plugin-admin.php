<?php
// Check if the class 'Restricted_Category_Plugin_Admin' does not already exist
if ( !class_exists( 'Restricted_Payment_Gateway_Plugin_Admin' ) ) {
    // Define the 'Restricted_Payment_Gateway_Plugin_Admin' class
	class Restricted_Payment_Gateway_Plugin_Admin {

		// Constructor method
		public function __construct() {
			// Constructor code here
		}

		// Method to run the admin plugin
		public function run() {
			add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
            
            add_action( 'save_post', [ $this, 'save_meta_box_data' ] );
		}

		public function add_meta_box() {
            add_meta_box(
                'restricted_payment_gateway_meta_box',
                __( 'Restricted Payment Gateway', 'restricted-payment-gateway' ),
                [ $this, 'render_meta_box' ],
                'product',
                'side',
                'default'
            );
        }

        public function render_meta_box( $post ) {
            wp_nonce_field( 'restricted_payment_gateway_meta_box', 'restricted_payment_gateway_meta_box_nonce' );

            $value = get_post_meta( $post->ID, '_restricted_payment_gateway', true );

            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

            echo '<label for="restricted_payment_gateway">';
            _e( 'Select a payment gateway for this product:', 'restricted-payment-gateway' );
            echo '</label> ';
            echo '<select id="restricted_payment_gateway" name="restricted_payment_gateway">';
            echo '<option value="">' . __( 'None', 'restricted-payment-gateway' ) . '</option>';
            foreach ( $available_gateways as $gateway_id => $gateway ) {
                echo '<option value="' . esc_attr( $gateway_id ) . '" ' . selected( $value, $gateway_id, false ) . '>' . esc_html( $gateway->get_title() ) . '</option>';
            }
            echo '</select>';
        }

        public function save_meta_box_data( $post_id ) {
            if ( ! isset( $_POST['restricted_payment_gateway_meta_box_nonce'] ) ) {
                return;
            }

            if ( ! wp_verify_nonce( $_POST['restricted_payment_gateway_meta_box_nonce'], 'restricted_payment_gateway_meta_box' ) ) {
                return;
            }

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            if ( ! isset( $_POST['restricted_payment_gateway'] ) ) {
                return;
            }

            $restricted_payment_gateway = sanitize_text_field( $_POST['restricted_payment_gateway'] );

            update_post_meta( $post_id, '_restricted_payment_gateway', $restricted_payment_gateway );
        }

	}
}
