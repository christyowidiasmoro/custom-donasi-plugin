<?php
if ( ! class_exists( 'WC_Gateway_Mollie_Subscription' ) ) {

	require_once __DIR__ . "/../../mollie-payments-for-woocommerce/vendor/autoload.php";

    class WC_Gateway_Mollie_Subscription extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'mollie_subscription';
            $this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields         = true; // in case you need a custom credit card form
            $this->method_title       = __( 'Mollie Subscription', 'mollie-subscription-payment' );
            $this->method_description = __( 'Allows payments with Mollie Subscription.', 'mollie-subscription-payment' );

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

			add_action( 'woocommerce_api_' . $this->id, [ $this, 'webhook' ] );
        }

        // Initialize Gateway Settings Form Fields
        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => __( 'Enable/Disable', 'mollie-subscription-payment' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Mollie Subscription Payment', 'mollie-subscription-payment' ),
                    'default' => 'yes'
                ],
                'title' => [
                    'title'       => __( 'Title', 'mollie-subscription-payment' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'mollie-subscription-payment' ),
                    'default'     => __( 'Mollie Subscription Payment', 'mollie-subscription-payment' ),
                    'desc_tip'    => true,
                ],
                'description' => [
                    'title'       => __( 'Description', 'mollie-subscription-payment' ),
                    'type'        => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'mollie-subscription-payment' ),
                    'default'     => __( 'Pay with Mollie Subscription Payment.', 'mollie-subscription-payment' ),
                ],
                'api_key' => [
					'title'       => __( 'API Key', 'mollie-subscription-payment' ),
					'type'        => 'text'
				]      
            ];
        }
		
		public function payment_fields() {}

		public function payment_scripts() {}
	
		public function validate_fields() {}
	
        // Process the payment
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey($this->get_option('api_key'));

			// create customer
			$customer = $this->create_customer( $mollie, $order );

			// create first payment as a mandate
			$payment = $this->create_first_payment( $mollie, $order, $customer );

			// if ( $payment->isPaid() ) {
				return ['result' => 'success', 'redirect' => $payment->getCheckoutUrl() ];
			// } else {
				// return ['result' => 'failure', 'message' => 'Payment failed.'];
			// }
		}
		
		public function webhook() {
			// Webhook test by Mollie
			if (isset($_GET['testByMollie'])) {
				error_log(__METHOD__ . ': Webhook tested by Mollie.');
				return;
			}
			if (empty($_GET['order_id']) || empty($_GET['key']) || empty($_GET['filter_flag']) ) {
				http_response_code(400);
				error_log(__METHOD__ . ":  No order ID or order key or flag provided.");
				return;
			}
			
			$order_id = sanitize_text_field(wp_unslash($_GET['order_id']));
			$key = sanitize_text_field(wp_unslash($_GET['key']));
			$filter_flag = sanitize_text_field(wp_unslash($_GET['filter_flag']));
			// $data_helper = $this->data;
			$order = wc_get_order($order_id);
			if (!$order instanceof WC_Order) {
				http_response_code(404);
				error_log(__METHOD__ . ":  Could not find order {$order_id}.");
				return;
			}
			if (!$order->key_is_valid($key)) {
				http_response_code(401);
				error_log(__METHOD__ . ":  Invalid key {$key} for order {$order_id}.");
				return;
			}
			$gateway = wc_get_payment_gateway_by_order($order);
			if (!$gateway instanceof WC_Gateway_Mollie_Subscription) {
				return;
			}
			if ($filter_flag !== "first_payment") {
				http_response_code(404);
				error_log(__METHOD__ . ":  Not valid filter flag {$filter_flag} for order {$order_id}.");
				return;
			}
			// $this->setGateway($gateway);

            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey($this->get_option('api_key'));

			$paymentId = filter_input(\INPUT_POST, 'id', \FILTER_SANITIZE_SPECIAL_CHARS);
			// No Mollie payment id provided
			if (empty($paymentId)) {
				http_response_code(400);
				error_log(__METHOD__ . ': No payment object ID provided.');
				return;
			}
			$payment_object_id = sanitize_text_field(wp_unslash($paymentId));

			$payment = $mollie->payments->get($payment_object_id);

			// Payment not found
			if (!$payment) {
				http_response_code(404);
				error_log( __METHOD__ . ": payment object {$payment_object_id} not found." );
				return;
			}
			if ($order_id != $payment->metadata->order_id) {
				http_response_code(400);
				error_log(__METHOD__ . ": Order ID does not match order_id in payment metadata. Payment ID {$payment->id}, order ID {$order_id}");
				return;
			}
			if (!$payment->customerId) {
				http_response_code(404);
				error_log(__METHOD__ . ": customer object for payment {$payment_object_id} not found.");
				return;
			}

			// get customer from payment
			$customer = $mollie->customers->get($payment->customerId);
			if (!$customer->hasValidMandate()) {
				http_response_code(404);
				error_log(__METHOD__ . ": customer {$payment->customerId} do not has a valid mandate.");
				error_log(__METHOD__ . json_encode($customer->mandates()));
				return;
			}
			
			error_log(__METHOD__ . ": payment object {$payment->id} (" . $payment->mode . ") webhook call for order {$order->get_id()} for customer {$customer->id}.");

			try {
				$subscription = $this->create_recurring_payment( $order, $customer );
				
				if (!$subscription) {
					http_response_code(404);
					error_log(__METHOD__ . ": subscription not created.");
					return;
				}
				error_log(__METHOD__ . ": subscription id {$subscription->id} (" . $subscription->status . ") webhook call for order {$order->get_id()}.");

			} catch (Exception $e) {
				error_log(__METHOD__ . ":" . $e->getPlainMessage());
			}

			// Status 200
		}

		private function create_customer( $mollie, $order ) {
			$customer = $mollie->customers->create([
				'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'email' => $order->get_billing_email(),
				'metadata' => [
					'order_id' => $order->get_id()
				]
			]);
			return $customer;
		}

		private function create_first_payment( $mollie, $order, $customer ) {

			$order_id = $order->get_id();
			$order_key = $order->get_order_key();
			$filterFlag = 'first_payment';
			$webhook_url = home_url('/wc-api/mollie_subscription/');
			$webhook_url = add_query_arg(['order_id' => $order_id, 'key' => $order_key, 'filter_flag' => $filterFlag], $webhook_url);

			$payment = $mollie->payments->create([
				'amount' => [
					'currency' => 'EUR',
					'value' => '0.39',
				],
				'customerId' => $customer->id,
				'sequenceType' => 'first',
				'description' => 'First payment for order #'.$order->get_id(),
				'redirectUrl' => $this->get_return_url($order),
				'webhookUrl' => $webhook_url,
				'method' => 'ideal',
				'metadata' => [
					'order_id' => $order->get_id(),
				],
			]);
			return $payment;
		}

		private function create_recurring_payment( $order, $customer ) {

			foreach ( $order->get_items() as $item_id => $item ) {
				$product = $item->get_product();
				if ( $product->get_meta( '_enable_mollie_subscription' ) === 'yes' ) {
					$interval = $product->get_meta( '_mollie_subscription_interval' );
					$intervalCount = $product->get_meta( '_mollie_subscription_interval_count' );
					$startTime = $product->get_meta( '_mollie_subscription_start_time' );
					$description = $product->get_meta( '_mollie_subscription_description' );
					break;
				}
			}			
			error_log(__METHOD__ . ": {$customer->hasValidMandate()}, {$interval}, {$intervalCount},  {$startTime}, {$description}.");

			if ($customer->hasValidMandate() && $interval && $intervalCount && $startTime) {
				$startDate = date('Y-m-d', strtotime($startTime));
				$valueAmount = strval( floatval( $order->get_total() ) + 0.31);
		
				error_log(__METHOD__ . ": create subscription: {$startDate}, {$valueAmount}.");
				$subscription = $customer->createSubscription([
					"amount" => [
						"currency" => "EUR",
						"value" => $valueAmount,
					],
					"times" => $intervalCount,
					"interval" => $interval,
					"startDate" => $startDate,
					"description" => ( !empty($description) ) ? $description : 'Subscription for order #'.$order->get_id(),
					// TODO: webhook to track the subscription payment
					// "webhookUrl" => home_url('/wc-api/mollie_subscription/'),
					"metadata" => [
						"order_id" => $order->get_id(),
					]
				]);
				error_log(__METHOD__ . ": create subscription id {$subscription->id}.");
				
				return $subscription;
			}
			error_log(__METHOD__ . ": create subscription null.");

			return \null;
		}
    }
}