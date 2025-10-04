<?php
/**
 * Events dispatcher hooks.
 *
 * @since 6.5.2
 */

namespace WPTravelEngine\Filters;

use WPTravelEngine\Core\Models\Post\Booking;
use WPTravelEngine\Core\Models\Post\Customer;
use WPTravelEngine\Core\Models\Post\Enquiry;
use WPTravelEngine\Core\Models\Post\Payment;
use WPTravelEngine\Core\Models\Review;
use WPTravelEngine\Helpers\Translators;

class Events {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected static string $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		static::$table_name = $wpdb->prefix . 'wptravelengine_events';
		add_action( 'wptravelengine_check_events', array( $this, 'check_events' ) );
		add_action( 'updated_postmeta', array( $this, 'trigger_payment_status_update' ), 10, 4 );
	}

	/**
	 * Trigger customer creation event.
	 *
	 * @return void
	 */
	public function check_events() {

		global $wpdb;

		$table = $wpdb->prefix . 'wptravelengine_events';
		$now   = current_time( 'mysql', true );

		$events = $wpdb->get_results( "SELECT * FROM $table WHERE `trigger_time` <= '$now'", ARRAY_A );

		foreach ( $events as $event ) :

			extract( $event );

			switch ( $object_type ) :
				case 'customer':
					$object = new Customer( $object_id );
					break;
				case 'enquiry':
					$object = new Enquiry( $object_id );
					break;
				case 'booking':
					$object = new Booking( $object_id );
					break;
				case 'wte-payments':
					$object = new Payment( $object_id );
					break;
				case 'comment':
					$object = new Review( get_comment( $object_id ) );
					break;
				default:
					$object = $object_id;
			endswitch;

			do_action( $event_name, $object, json_decode( $event_data, true ) );

			$wpdb->delete( $table, compact( 'id' ), [ '%d' ] );

		endforeach;
	}

	/**
	 * Trigger payment status update.
	 *
	 * @param int $meta_id Meta ID.
	 * @param int $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed $meta_value Meta value.
	 *
	 * @return void
	 */
	public function trigger_payment_status_update( int $meta_id, int $post_id, string $meta_key, $meta_value ) {

		if ( 'payment_status' !== $meta_key ) {
			return;
		}

		$post = get_post( $post_id );

		$success_values = array_keys( wptravelengine_payment_status() );

		if ( 'wte-payments' === $post->post_type && in_array( $meta_value, $success_values, true ) ) {
			$payment = new Payment( $post );
			if ( wptravelengine_toggled( $payment->get_meta( 'is_due_payment' ) ) ) {
				static::add_event( 'wptravelengine.booking.due.payment.completed', $payment->get_id(), $payment->get_post_type() );
			} else {
				static::add_event( 'wptravelengine.booking.payment.completed', $payment->get_id(), $payment->get_post_type() );
			}
		}
	}

	/**
	 * Customer created event.
	 *
	 * @param Customer $customer Customer instance.
	 * @return void
	 */
	public static function customer_created( Customer $customer ) {
		static::add_event( 'wptravelengine.customer.created', $customer->get_id(), $customer->get_post_type() );
	}

	/**
	 * Enquiry created event.
	 *
	 * @param Enquiry $enquiry Enquiry instance.
	 *
	 * @return void
	 */
	public static function enquiry_created( Enquiry $enquiry ) {
		static::add_event( "wptravelengine.enquiry.created", $enquiry->get_id(), $enquiry->get_post_type() );
	}

	/**
	 * Booking created event.
	 *
	 * @param Booking $booking Booking instance.
	 *
	 * @return void
	 */
	public static function booking_created( Booking $booking ) {
		static::add_event( "wptravelengine.booking.created", $booking->get_id(), $booking->get_post_type() );
	}

	/**
	 * Booking updated event.
	 *
	 * @param Booking $booking Booking instance.
	 *
	 * @return void
	 */
	public static function booking_updated( Booking $booking ) {
		static::add_event( "wptravelengine.booking.updated", $booking->get_id(), $booking->get_post_type() );
	}

	/**
	 * Review created event.
	 *
	 * @param Review $review Review instance.
	 *
	 * @return void
	 */
	public static function review_created( Review $review ) {
		static::add_event( "wptravelengine.review.created", $review->get_id(), 'comment' );
	}

	/**
	 * Add event.
	 *
	 * @param string $event_name Event name.
	 * @param int $object_id Object ID.
	 * @param string $object_type Object type.
	 * @param string $trigger_time Trigger time.
	 * @param array $event_data Data.
	 *
	 * @return int|false
	 */
	public static function add_event( string $event_name, $object_id, $object_type, $trigger_time = null, $event_data = array() ) {
		global $wpdb;

		if ( Translators::is_wpml_multilingual_active() ) {
			$event_data['wpml_lang'] = apply_filters( 'wpml_current_language', null );
		}

		$trigger_time ??= gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) + 60 );

		$table = static::$table_name;

		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (`object_id`, `event_name`, `object_type`, `event_data`, `trigger_time`, `event_created_at`)
			VALUES (%d, %s, %s, %s, %s, %s)
			ON DUPLICATE KEY UPDATE
				`event_data` = VALUES(`event_data`),
				`trigger_time` = VALUES(`trigger_time`),
				`event_created_at` = VALUES(`event_created_at`)
			",
			$object_id,
			$event_name,
			$object_type,
			wp_json_encode( $event_data ),
			$trigger_time,
			current_time( 'mysql', true )
		);

		$result = $wpdb->query( $sql );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Event Exists.
	 *
	 * @param string $event_name Event name.
	 * @param int $object_id Object ID.
	 * @param string $object_type Object type.
	 *
	 * @return bool
	 */
	public static function exists( string $event_name, int $object_id, string $object_type ): bool {
		global $wpdb;

		$table = static::$table_name;

		$sql_check = $wpdb->prepare(
			"SELECT 1 FROM {$table} WHERE object_id = %d AND event_name = %s AND object_type = %s LIMIT 1",
			$object_id,
			$event_name,
			$object_type
		);

		return $wpdb->get_var( $sql_check ) !== null;
	}
}
