<?php

// Check if the class 'Mollie_Subscription_Payment_Plugin_Admin' does not already exist
if ( !class_exists( 'Mollie_Subscription_Payment_Plugin_Admin' ) ) {
    // Define the 'Mollie_Subscription_Payment_Plugin_Admin' class
    class Mollie_Subscription_Payment_Plugin_Admin {

        // Constructor method
        public function __construct() {
            // Constructor code here
        }

        // Method to run the admin plugin
        public function run() {
			// Add a new tab in the product data metabox
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_custom_product_data_tab' ] );

			// Add content to the new tab
			add_action( 'woocommerce_product_data_panels', [ $this, 'add_custom_product_data_fields' ] );

			add_action( 'woocommerce_process_product_meta', [ $this, 'save_custom_product_data_fields' ] );

            // Hook to register settings
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        }

		// Method to render the admin settings page
		public function mollie_subscription_payment_plugin_admin_page() {
			?>
			<div class="wrap">
				<h1>Payment for Subscription</h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'donasi-settings-mollie-subscription' );
					do_settings_sections( 'donasi-settings-mollie-subscription' );
					submit_button();
					?>
				</form>
			</div>
			<?php
		}
      
		// Method to register settings
		public function register_settings() {
			// Register a new setting for the API key
			register_setting( 'donasi-settings-mollie-subscription', 'mollie_subscription_api_key' );

			// Add a new section in the settings page
			add_settings_section(
				'mollie_subscription_settings_section',
				'Mollie Subscription Settings',
				null,
				'donasi-settings-mollie-subscription'
			);

			// Add a new field for the API key
			add_settings_field(
				'mollie_subscription_api_key',
				'Mollie API Key',
				[ $this, 'mollie_subscription_api_key_callback' ],
				'donasi-settings-mollie-subscription',
				'mollie_subscription_settings_section'
			);


		}

		public function mollie_subscription_api_key_callback() {
			// Get the value of the API key
			$value = get_option( 'mollie_subscription_api_key' );

			// Render the input field
			echo '<input type="text" name="mollie_subscription_api_key" value="' . $value . '" />';
		}

		public function add_custom_product_data_tab( $tabs ) {
			$tabs['mollie_subscription'] = [
				'label' 	=> 'Mollie Subscription',
				'target' 	=> 'mollie_subscription_data',
				'class' 	=> ['show_if_simple', 'show_if_variable'],
				// 'priority' 	=> 80
			];

			return $tabs;
		}

		public function add_custom_product_data_fields() {
			global $post; 
			// echo `<div id="mollie_subscription_data" class="panel woocommerce_options_panel">mollie subscriptions</div>`;

			// Check if the product type is simple or variable
			if ( in_array( get_post_type( $post->ID ), ['product', 'product_variation'] ) ) {
				
				?>
				<div id="mollie_subscription_data" class="panel woocommerce_options_panel">
				<?php

				woocommerce_wp_checkbox( [
					'id'            => '_enable_mollie_subscription',
					'label'         => __( 'Enable Mollie Subscription', 'woocommerce' ),
					'description'   => __( 'Allow customers to pay with Mollie Subscription.', 'woocommerce' ),
				] );

				woocommerce_wp_text_input( [
					'id'            => '_mollie_subscription_interval',
					'label'         => __( 'Subscription Interval', 'woocommerce' ),
					'description'   => __( 'Interval to wait between payments. Possible values: ... days ... weeks ... months.', 'woocommerce' ),
					'type'          => 'text',
				] );

				woocommerce_wp_text_input( [
					'id'            => '_mollie_subscription_interval_count',
					'label'         => __( 'Subscription Interval Count', 'woocommerce' ),
					'description'   => __( 'Total number of payments for the subscription. Once this number of payments is reached, the subscription is considered completed.', 'woocommerce' ),
					'type'          => 'number',
					'custom_attributes' => [
						'step'  => '1',
						'min'   => '1'
					]
				] );

				woocommerce_wp_text_input( array( // Text Field type
					'id'          => '_mollie_subscription_start_time',
					'label'       => __( 'Start Time', 'woocommerce' ),
					'description' => __( 'The start of subscription. Ex: first day of next month, next friday, now, +1 hour, +1 day', 'woocommerce' ),
					'type'          => 'text',
				) );

				woocommerce_wp_text_input( array( // Text Field type
					'id'          => '_mollie_subscription_description',
					'label'       => __( 'Description', 'woocommerce' ),
					'description' => __( 'The description of subscription.', 'woocommerce' ),
					'type'          => 'text',
				) );
			
				?>
				</div>
				<?php
			}
		}

		function save_custom_product_data_fields( $post_id ) {
            $enable_mollie_subscription = isset( $_POST['_enable_mollie_subscription'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_enable_mollie_subscription', $enable_mollie_subscription );

            if ( isset( $_POST['_mollie_subscription_interval'] ) ) {
                update_post_meta( $post_id, '_mollie_subscription_interval', sanitize_text_field( $_POST['_mollie_subscription_interval'] ) );
            }

            if ( isset( $_POST['_mollie_subscription_interval_count'] ) ) {
                update_post_meta( $post_id, '_mollie_subscription_interval_count', sanitize_text_field( $_POST['_mollie_subscription_interval_count'] ) );
            }

            if ( isset( $_POST['_mollie_subscription_start_time'] ) ) {
                update_post_meta( $post_id, '_mollie_subscription_start_time', sanitize_text_field( $_POST['_mollie_subscription_start_time'] ) );
            }

			if ( isset( $_POST['_mollie_subscription_description'] ) ) {
                update_post_meta( $post_id, '_mollie_subscription_description', sanitize_text_field( $_POST['_mollie_subscription_description'] ) );
            }
        }

	}
}
