<?php
/**
 * Passes the booking details to the bookings html template.
 *
 * @since 6.0.0
 * @package wp-travel-engine/includes/templates/account/tab-content/bookings
 */

use WPTravelEngine\Core\Models\Post\Booking;

$booking_details = [];
foreach ( $args['bookings'] ?? [] as $booking ) {

    if ( empty( get_metadata( 'post', $booking ) ) ) {
        continue;
    }

    $booking_instance   = new Booking( $booking );
    $booking_metas      = $booking_instance->get_meta( 'wp_travel_engine_booking_setting' );
    $_booking_meta = get_post_meta( $booking, 'cart_info', true );
    if ( 'publish' !== $booking_instance->post->post_status || empty( $booking_metas ) ) {
        continue;
    }

    $trip_date = $booking_instance->get_trip_datetime();
  
    $order_items = $booking_instance->get_order_items();
    $booked_trip = is_array( $order_items ) ? array_pop( $order_items ) : '';
    $booked_trip = is_null( $booked_trip ) || empty( $booked_trip ) ? '' : (object) $booked_trip;
    if ( ( empty( $trip_date ) ) || ( ( $trip_date < gmdate( 'Y-m-d' ) ) && 'active' === $type ) ) {
        continue;
    }

    $active_payment_methods = wp_travel_engine_get_active_payment_gateways();
    $booking_payments       = (array) $booking_instance->get_payment_detail();
	if ( empty( $booking_payments ) ) {
        $total_paid       = ( float ) ( $booking_metas['place_order']['cost'] ?? 0 );
        $due              = (float) ( $booking_metas['place_order']['due'] ?? 0 );
        $due              = $due < 1 ? 0 : $due;
        $show_pay_now_btn = ( 'partially-paid' === $payment_status || $due > 0 ) && ! empty( $active_payment_methods );
    } else {
        $total_paid       = (float) $booking_instance->get_paid_amount();
        $due              = (float) $booking_instance->get_due_amount();
        $due              = $due < 1 ? 0 : $due;
        $show_pay_now_btn = $due > 0;
    }

    $total_paid = $booking_instance->get_paid_amount() ?? 0;
    $due = $booking_instance->get_due_amount() ?? 0;
    $show_pay_now_btn = $due > 0;
    $payment_status = $show_pay_now_btn && $total_paid > 0 ? __( 'Partially Paid', 'wp-travel-engine' ) : ( $show_pay_now_btn ? __( 'Pending', 'wp-travel-engine' ) : __( 'Paid', 'wp-travel-engine' ) );

    if ( 'active' !== $type && ! $payment_status ) {
        $payment_status = __( 'Pending', 'wp-travel-engine' );
    }

	$currency_code = $booking_instance->get_cart_info( 'currency' ) ?? '';

	$booking_details[] = compact(
		'active_payment_methods',
		'booked_trip',
        'trip_date',
		'payment_status',
		'total_paid',
		'due',
		'show_pay_now_btn',
		'booking_instance',
		'currency_code'
    );

}

?>
<div class="wpte-bookings-contents">
<?php
if ( ! empty( $booking_details ) ) :
    foreach ( $booking_details as $details ) :
        wte_get_template( "account/tab-content/bookings/bookings-html.php", array_merge( $details, [ 'type' => $type ] ) );
    endforeach;
else :
    esc_html_e( 'You haven\'t booked any trip yet.', 'wp-travel-engine' );
endif;
?>
</div>
