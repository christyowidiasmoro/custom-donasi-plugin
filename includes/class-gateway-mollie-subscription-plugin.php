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

			if ( $payment->isPaid() ) {
				return ['result' => 'success', 'redirect' => $payment->getCheckoutUrl() ];
			} else {
				return ['result' => 'failure', 'message' => 'Payment failed.'];
			}
		}
		
		public function webhook() {
			// Webhook test by Mollie
			if (isset($_GET['testByMollie'])) {
				$this->logger->debug(__METHOD__ . ': Webhook tested by Mollie.', [\true]);
				return;
			}
			if (empty($_GET['order_id']) || empty($_GET['key'])) {
				$this->httpResponse->setHttpResponseCode(400);
				$this->logger->debug(__METHOD__ . ":  No order ID or order key provided.");
				return;
			}
			$order_id = sanitize_text_field(wp_unslash($_GET['order_id']));
			$key = sanitize_text_field(wp_unslash($_GET['key']));
			// $data_helper = $this->data;
			$order = wc_get_order($order_id);
			if (!$order instanceof WC_Order) {
				$this->httpResponse->setHttpResponseCode(404);
				$this->logger->debug(__METHOD__ . ":  Could not find order {$order_id}.");
				return;
			}
			if (!$order->key_is_valid($key)) {
				$this->httpResponse->setHttpResponseCode(401);
				$this->logger->debug(__METHOD__ . ":  Invalid key {$key} for order {$order_id}.");
				return;
			}
			$gateway = wc_get_payment_gateway_by_order($order);
			if (!$gateway instanceof WC_Gateway_Mollie_Subscription) {
				return;
			}
			// $this->setGateway($gateway);

            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey($this->get_option('api_key'));

			$paymentId = filter_input(\INPUT_POST, 'id', \FILTER_SANITIZE_SPECIAL_CHARS);
			// No Mollie payment id provided
			if (empty($paymentId)) {
				$this->httpResponse->setHttpResponseCode(400);
				$this->logger->debug(__METHOD__ . ': No payment object ID provided.', [\true]);
				return;
			}
			$payment_object_id = sanitize_text_field(wp_unslash($paymentId));

			$payment = $mollie->payments->get($payment_object_id);

			// Payment not found
			if (!$payment) {
				$this->httpResponse->setHttpResponseCode(404);
				$this->logger->debug(__METHOD__ . ": payment object {$payment_object_id} not found.", [\true]);
				return;
			}
			if ($order_id != $payment->metadata->order_id) {
				$this->httpResponse->setHttpResponseCode(400);
				$this->logger->debug(__METHOD__ . ": Order ID does not match order_id in payment metadata. Payment ID {$payment->id}, order ID {$order_id}");
				return;
			}
			if (!$payment->customerId) {
				$this->httpResponse->setHttpResponseCode(404);
				$this->logger->debug(__METHOD__ . ": customer object for payment {$payment_object_id} not found.", [\true]);
				return;
			}

			// Log a message that webhook was called, doesn't mean the payment is actually processed
			$this->logger->debug(__METHOD__ . ": Mollie Subscription payment object {$payment->id} (" . $payment->mode . ") webhook call for order {$order->get_id()}.", [\true]);

			// get customer from payment
			$customer = $mollie->customers->get($payment->customerId);
			if (!$customer->hasValidMandate()) {
				$this->httpResponse->setHttpResponseCode(404);
				$this->logger->debug(__METHOD__ . ": customer {$payment->customerId} do not a valid mandate.", [\true]);
				return;
			}

			$subscription = $this->create_recurring_payment( $order, $customer );
			if (!$subscription) {
				$this->httpResponse->setHttpResponseCode(404);
				$this->logger->debug(__METHOD__ . ": subscription not created.", [\true]);
				return;
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
					'value' => '0.36',
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

			if ($customer->hasValidMandate() && $interval && $intervalCount && $startTime) {
				$startDate = date('Y-m-d', strtotime($startTime));
		
				$subscription = $customer->createSubscription([
					"amount" => [
						"currency" => "EUR",
						"value" => $order->get_total(),
					],
					"times" => $intervalCount,
					"interval" => $interval,
					"startDate" => $startDate,
					"description" => ( !empty($description) ) ? $description : 'Subscription for order #'.$order->get_id(),
					"webhookUrl" => home_url('/wc-api/mollie_subscription/'),
					"metadata" => [
						"order_id" => $order->get_id(),
					]
				]);
				return $subscription;
			}

			return \null;
		}
    }
}