<?php
/**
 *
 * @since 6.4.0
 */

namespace WPTravelEngine\Builders\FormFields;

use WPTravelEngine\Abstracts\BookingEditFormFields;
use WPTravelEngine\Core\Models\Post\Booking;
use WPTravelEngine\Core\Models\Post\Payment;
use WPTravelEngine\Traits\Singleton;

/**
 * Form field class to render billing form fields.
 *
 * @since 6.4.0
 */
class PaymentEditFormFields extends BookingEditFormFields {

	protected ?Payment $payment = null;

	public function __construct( array $defaults = array(), string $mode = 'edit' ) {
		$this->use_legacy_template( true );
		parent::__construct( $defaults, $mode );
		static::$mode = $mode;
		$this->init( $this->map_fields( static::structure( $mode ) ) );
	}

	/**
	 * Create.
	 *
	 * @return PaymentEditFormFields
	 */
	public static function create( ...$args ): PaymentEditFormFields {
		return new static( ...$args );
	}

	/**
	 * Map field.
	 *
	 * @param array $field Field.
	 *
	 * @return array
	 */
	protected function map_field( $field ): array {

		$name = null;

		$field = parent::map_field( $field );
		if ( preg_match( '#\[([^\]]+)\]\[\]$#', $field[ 'name' ], $matches ) ) {
			$name = $matches[ 1 ];
		} else if ( preg_match( '#\[[^\]]+\]\[([^\]]+)\]$#', $field[ 'name' ], $matches ) ) {
			$name = $matches[ 1 ];
		}

		if ( $name ) {
			$field[ 'name' ] = sprintf( 'payments[%s][]', $name );
			$field[ 'id' ]   = sprintf( 'payments_%s', $name );
		}
		$field[ 'field_label' ] = isset( $field[ 'placeholder' ] ) && $field[ 'placeholder' ] !== '' ? $field[ 'placeholder' ] : $field[ 'field_label' ];
		$field[ 'default' ]     = $this->defaults[ $name ] ?? $field[ 'default' ] ?? '';

		if( static::$mode !== 'edit' ){
			$field['option_attributes'] = array(
				'disabled' => 'disabled',
			);
			$field['attributes'] = array(
				'disabled' => 'disabled',
			);
		}

		$field['wrapper_class'] = apply_filters( 'wptravelengine_payment_edit_form_fields_wrapper_class', 'wpte-field', $field );

		return $field;
	}

	public static function structure( string $mode = 'edit' ): array {
		return DefaultFormFields::payments( $mode );
	}

}
