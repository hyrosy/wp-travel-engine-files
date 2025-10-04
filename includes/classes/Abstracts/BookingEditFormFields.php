<?php
/**
 * Base Class For Booking Edit Forms.
 *
 * @since 6.4.0
 */

namespace WPTravelEngine\Abstracts;

use WPTravelEngine\Builders\FormFields\FormField;
use WPTravelEngine\Core\Models\Post\Booking;

abstract class BookingEditFormFields extends FormField {

	protected array $defaults = array();

	protected static string $mode;

	public function __construct( array $defaults = array(), string $mode = 'edit' ) {
		$this->defaults = $defaults;
		parent::__construct();
		static::$mode = $mode;
	}

	/**
	 * @inheritDoc
	 */
	public function render(): void {
		echo $this->process();
	}

	protected function map_field( $field ) {
		$field[ 'wrapper_class' ] = 'wpte-field';

		return $field;
	}

	protected function map_fields( $fields ): array {
		return array_map( array( $this, 'map_field' ), $fields );
	}

	abstract static function structure( string $mode = 'edit' );
}
