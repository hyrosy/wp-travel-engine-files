<?php
/**
 * @var array $remaining_payment
 * @var Booking $booking
 */

use WPTravelEngine\Core\Models\Post\Booking;
use WPTravelEngine\Core\Models\Post\Payment;

$_cart_info              = $booking->get_meta( 'cart_info' );
$is_booking_edit_enabled = isset( $_cart_info['items'] );
$due_amount = $booking->get_total_due_amount();
if( round( $due_amount, 2 ) <= 0 ){
	return;
}
$payments = $booking->get_payment_detail();
$payment_amount = 0;
if ( is_array( $payments ) && count( $payments ) > 0 ) {
	foreach( $payments as $payment ){
		$payment_id = Payment::make( $payment );
		$payment_amount += $payment_id->get_amount();
	}
}
$is_customized_reservation = $booking->get_meta( '_user_edited' );
if( $payment_amount >= $due_amount && $is_customized_reservation ){
	return;
}
if ( ! $is_booking_edit_enabled || ! $booking->has_due_payment() || ! $booking->get_order_items() ) {
	return;
}
?>

<div class="wpte-field">
	<label for=""><?php echo __( 'Remaining Payment Link', 'wp-travel-engine' ); ?></label>
	<div class="wpte-copy-field">
		<input type="url" name="" id="" value="<?php echo esc_url( $booking->get_due_payment_link() ); ?>" readonly>
		<button type="button" class="wpte-button wpte-link wpte-tooltip" data-content="Copy Link">
			<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path
					d="M6.25 2.5H12.1667C14.0335 2.5 14.9669 2.5 15.68 2.86331C16.3072 3.18289 16.8171 3.69282 17.1367 4.32003C17.5 5.03307 17.5 5.96649 17.5 7.83333V13.75M5.16667 17.5H11.9167C12.8501 17.5 13.3168 17.5 13.6733 17.3183C13.9869 17.1586 14.2419 16.9036 14.4017 16.59C14.5833 16.2335 14.5833 15.7668 14.5833 14.8333V8.08333C14.5833 7.14991 14.5833 6.6832 14.4017 6.32668C14.2419 6.01308 13.9869 5.75811 13.6733 5.59832C13.3168 5.41667 12.8501 5.41667 11.9167 5.41667H5.16667C4.23325 5.41667 3.76654 5.41667 3.41002 5.59832C3.09641 5.75811 2.84144 6.01308 2.68166 6.32668C2.5 6.6832 2.5 7.14991 2.5 8.08333V14.8333C2.5 15.7668 2.5 16.2335 2.68166 16.59C2.84144 16.9036 3.09641 17.1586 3.41002 17.3183C3.76654 17.5 4.23325 17.5 5.16667 17.5Z"
					stroke="currentColor" stroke-width="1.39" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</button>
	</div>
</div>
