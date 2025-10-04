<?php
/**
 * Standard Paypal Payment Gateway
 *
 * @package WPTravelEngine\PaymentGateways
 * @since 6.0.0
 */

namespace WPTravelEngine\PaymentGateways\StandardPaypal;

use WPTravelEngine\Core\Models\Post\Booking;
use WPTravelEngine\Core\Models\Post\Payment;
use WPTravelEngine\Core\Models\Settings\PluginSettings;
use WPTravelEngine\PaymentGateways\BaseGateway;

/**
 * Standard PayPal Payment Gateway.
 *
 * @since 6.0.0
 */
class Gateway extends BaseGateway {

	/**
	 * Get gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id(): string {
		return 'paypal_payment';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Paypal Standard', 'wp-travel-engine' );
	}

	/**
	 * Gateway Description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Please check this to enable Paypal Standard booking system for trip booking and fill the account info below.', 'wp-travel-engine' );
	}

	/**
	 * Get public label.
	 *
	 * @return string
	 */
	public function get_public_label(): string {
		return __( 'Paypal', 'wp-travel-engine' );
	}

	/**
	 * Get icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return WP_TRAVEL_ENGINE_URL . '/public/css/icons/paypal-payment.png';
	}

	/**
	 * Redirect URL to PayPal.
	 *
	 * @param array $args
	 * @param bool $ssl_check
	 *
	 * @return string
	 */
	public function paypal_gateway_url( array $args = array(), bool $ssl_check = false ): string {
		$use_sandbox = defined( 'WP_TRAVEL_ENGINE_PAYMENT_DEBUG' ) && WP_TRAVEL_ENGINE_PAYMENT_DEBUG;

		$url_parts = array(
			is_ssl() || ! $ssl_check ? 'https://' : 'http://',
			$use_sandbox ? 'www.sandbox.paypal.com' : 'www.paypal.com',
			'/cgi-bin/webscr',
		);

		return esc_url( implode( '', $url_parts ) ) . '?' . http_build_query( $args, '', '&' );
	}

	/**
	 * @inerhitDoc
	 */
	public function process_payment( Booking $booking, Payment $payment, $booking_instance ): void {

		$paypal_id = PluginSettings::make()->get( 'paypal_id' );

		$args = array(
			'amount'         => round( $payment->get_payable_amount(), 2 ),
			'currency_code'  => $payment->get_payable_currency(),
			'cmd'            => '_cart',
			'upload'         => '1',
			'business'       => $paypal_id,
			'bn'             => '',
			'rm'             => '2',
			'tax_cart'       => 0,
			'charset'        => get_bloginfo( 'charset' ),
			'cbt'            => get_bloginfo( 'name' ),
			'return'         => $this->get_callback_url( $payment, 'success' ),
			'cancel'         => $this->get_callback_url( $payment, 'cancel' ),
			'notify_url'     => $this->get_callback_url( $payment, 'notification' ),
			'handling'       => 0,
			'handling_cart'  => 0,
			'no_shipping'    => 0,
			'option_index_0' => 1,
			'custom'         => $booking->get_id(),
		);

		$order_items = $booking->get_order_items();

		foreach ( $order_items as $index => $item ) {
			$key                        = $index + 1;
			$item                       = (object) $item;
			$args[ "item_name_$key" ]   = $item->title;
			$args[ "quantity_$key" ]    = 1;
			$args[ "amount_$key" ]      = $args[ 'amount' ];
			$args[ "item_number_$key" ] = $item->ID;
			$args[ "on2_$key" ]         = __( 'Total Price', 'wp-travel-engine' );
			$args[ "os2_$key" ]         = $args[ 'amount' ];
		}

		$args = apply_filters( 'wp_travel_engine_paypal_request_args', $args );

		wp_redirect( $this->paypal_gateway_url( $args ) );
		exit;
	}

	/**
	 * Handle Notification Request.
	 *
	 * @return void
	 */
	public function handle_notification_request( Booking $booking, Payment $payment ) {

		$listener = new IPNListener();

		$listener->use_sandbox = defined( 'WP_TRAVEL_ENGINE_PAYMENT_DEBUG' ) && WP_TRAVEL_ENGINE_PAYMENT_DEBUG;

		if ( $listener->processIpn() ) {
			$transactionData = $listener->getPostData();
			file_put_contents( 'ipn_success.log', wp_json_encode( array( 'data' => $transactionData ), JSON_PRETTY_PRINT ) . PHP_EOL, LOCK_EX | FILE_APPEND );

			$message = '';
			if ( sanitize_text_field( wp_unslash( $_POST[ 'mc_currency' ] ) ) != $payment->get_payable_currency() ) {
				$message .= "\nCurrency does not match those assigned in settings\n";
			}

			$transaction_id = sanitize_text_field( wp_unslash( $_REQUEST[ 'txn_id' ] ) ) ?? false;
			if ( $transaction_id === $payment->get_meta( '_transaction_id' ) ) {
				header( 'HTTP/1.1 200 OK' );
			}

			if ( isset( $_REQUEST[ 'payment_status' ] ) ) {
				$payment->set_meta( 'payment_status', strtolower( sanitize_text_field( wp_unslash( $_REQUEST[ 'payment_status' ] ) ) ) );
				$payment->set_meta( '_transaction_id', $transaction_id );

				if ( 'Completed' == $_REQUEST[ 'payment_status' ] ) {
					$amount = (float) ( wp_unslash( $_REQUEST[ 'mc_gross' ] ) );
					$booking->set_meta( 'paid_amount', (float) $booking->get_paid_amount() + $amount );
					$booking->set_meta( 'due_amount', (float) $booking->get_due_amount() - $amount );
					$booking->set_meta( 'wp_travel_engine_booking_status', 'booked' );

					$payment->set_meta( 'payment_amount', array(
						'value'    => $amount,
						'currency' => sanitize_text_field( wp_unslash( $_REQUEST[ 'mc_currency' ] ) ),
					) );

				}
			}
			$payment->set_meta( 'gateway_response', $transactionData );

			$payment->save();
			$booking->save();
			wptravelengine_send_booking_emails( $payment->get_id(), 'order_confirmation', 'all' );

			// Delete the saved key for generated JWT, sent in notification url.
			do_action( 'wte_booking_cleanup', $payment->get_id(), 'notification' );

			exit;
		} else {
			/**
			 * Log errors
			 */
			$errors = $listener->getErrors();
			file_put_contents( 'ipn_errors.log', print_r( $errors, true ) . PHP_EOL, LOCK_EX | FILE_APPEND );

			/**
			 * An Invalid IPN *may* be caused by a fraudulent transaction attempt. It's
			 * a good idea to have a developer or sysadmin manually investigate any
			 * invalid IPN.
			 */
			$from_email = PluginSettings::make()->get( 'email.from' );
			if ( ! empty( $from_email ) ) {
				wp_mail( $from_email, __( 'Invalid IPN', 'wp-travel-engine' ), $listener->getTextReport() );
			}
		}

		header( 'HTTP/1.1 200 OK' );
	}
}
