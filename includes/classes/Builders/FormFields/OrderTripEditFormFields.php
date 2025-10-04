<?php
/**
 *
 * @since 6.4.0
 */

namespace WPTravelEngine\Builders\FormFields;


use WPTravelEngine\Abstracts\BookingEditFormFields;
use WPTravelEngine\Core\Models\Post\Booking;

class OrderTripEditFormFields extends BookingEditFormFields {

	protected ?Booking $booking = null;

	public function __construct( array $defaults = array(), string $mode = 'edit' ) {
		parent::__construct( $defaults, $mode );
		$this->init( $this->map_fields( static::structure( $mode ) ) );
	}

	protected function map_field( $field ) {
		$name = null;

		$field = parent::map_field( $field );

		if ( preg_match( '#\[([^\]]+)\]\[\]$#', $field[ 'name' ], $matches ) ) {
			$name = $matches[ 1 ];
		} else if ( preg_match( '#\[([^\]]+)\]$#', $field[ 'name' ], $matches ) ) {
			$name = $matches[ 1 ];
		}

		if ( $name ) {
			$field[ 'name' ] = sprintf( 'order_trip[%s]', $name );
			$field[ 'id' ]   = sprintf( 'order_trip_%s', $name );
		}
		$field[ 'field_label' ] = isset( $field[ 'placeholder' ] ) && $field[ 'placeholder' ] !== '' ? $field[ 'placeholder' ] : $field[ 'field_label' ];
		$field[ 'default' ]     = $this->defaults[ $name ] ?? $field[ 'default' ] ?? '';

		if ( static::$mode === 'readonly' ) {
			$field[ 'attributes' ][ 'readonly' ] = 'readonly';
			$field[ 'attributes' ][ 'disabled' ] = 'disabled';
		}

		if( $field['disabled'] ?? false ) {
			$field[ 'attributes' ][ 'disabled' ] = 'disabled';
		}

		if( $field['name'] == 'order_trip[id]' ) {
			$field['validations'] = array(
				'required' => true,
			);
		}

		if( $field['name'] == 'order_trip[number_of_travelers]' ) {
			$field['validations'] = array(
				'required' => true,
				'min' => 1,
			);
		}

		return $field;
	}

	public static function create(): OrderTripEditFormFields {
		return new static();
	}

	public static function structure( string $mode = 'edit' ): array {
		$fields = apply_filters( 'wptravelengine_order_trip_edit_fields_structure', array(
			'booked_trip'         => array(
				'type'          => 'trips_list',
				'wrapper_class' => 'row-repeater name-holder',
				'field_label'   => __( 'Booked Trip', 'wp-travel-engine' ),
				'name'          => 'order_trip[id]',
				'id'            => 'order_trip_booked_trip',
			),
			'booked_date'         => array(
				'type'          => 'text',
				'wrapper_class' => 'row-repeater name-holder',
				'field_label'   => __( 'Booked Date', 'wp-travel-engine' ),
				'name'          => 'order_trip[booked_date]',
				'id'            => 'order_trip_booked_date',
				'class'         => 'input',
				'disabled'      => true,
			),
			'start_date'          => array(
				'type'          => 'text',
				'wrapper_class' => 'row-repeater',
				'field_label'   => __( 'Start Date', 'wp-travel-engine' ),
				'name'          => 'order_trip[start_date]',
				'id'            => 'order_trip_start_date',
				'class'         => 'wpte-date-picker',
				'attributes'    => array(
					'data-options' => array(
						'enableTime' => true,
						'dateFormat' => 'Y-m-d H:i',
					),
				),
				'context'       => array(
					'readonly' => array(
						'type'          => 'text',
						'wrapper_class' => 'row-repeater',
						'field_label'   => __( 'Start Date', 'wp-travel-engine' ),
						'name'          => 'order_trip[start_date]',
						'id'            => 'order_trip_start_date',
					),
				),
			),
			'end_date'            => array(
				'type'          => 'text',
				'wrapper_class' => 'row-repeater',
				'field_label'   => __( 'End Date', 'wp-travel-engine' ),
				'name'          => 'order_trip[end_date]',
				'id'            => 'order_trip_end_date',
				'class'         => 'wpte-date-picker',
				'attributes'    => array(
					'data-options' => array(
						'enableTime' => true,
						'dateFormat' => 'Y-m-d H:i',
					),
				),
				'context'       => array(
					'readonly' => array(
						'type'          => 'text',
						'wrapper_class' => 'row-repeater',
						'field_label'   => __( 'End Date', 'wp-travel-engine' ),
						'name'          => 'order_trip[end_date]',
						'id'            => 'order_trip_end_date',
					),
				),
			),
			'trip_code'           => array(
				'type'          => 'text',
				'wrapper_class' => 'row-repeater',
				'field_label'   => __( 'Trip Code', 'wp-travel-engine' ),
				'name'          => 'order_trip[trip_code]',
				'id'            => 'order_trip_trip_code',
				'attributes'    => array( 'readonly' => 'readonly' ),
			),
			'number_of_travelers' => array(
				'type'          => 'number',
				'wrapper_class' => 'row-repeater',
				'field_label'   => __( 'Number of Travelers', 'wp-travel-engine' ),
				'name'          => 'order_trip[number_of_travelers]',
				'id'            => 'order_trip_number_of_travelers',
			),
			'package_name'        => array(
				'type'          => 'text',
				'wrapper_class' => 'row-repeater',
				'field_label'   => __( 'Package Name', 'wp-travel-engine' ),
				'name'          => 'order_trip[package_name]',
				'id'            => 'order_trip_package_name',
				'class'         => 'input',
			),
		) );

		return DefaultFormFields::by_mode( $fields, $mode );
	}
}
