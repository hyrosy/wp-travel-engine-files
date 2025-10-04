<?php
/**
 * Text form field class
 *
 * @package WP Travel Engine
 */

class WP_Travel_Engine_Form_Field_Text {

	/**
	 * Field with attributes
	 *
	 * @var [type]
	 */
	protected $field;

	/**
	 * Field type name
	 *
	 * @var string
	 */
	protected $field_type = 'text';

	/**
	 * Initialize field type class.
	 *
	 * @param array $field
	 * @return void
	 */
	function init( $field ) {

		$this->field = $field;

		return $this;
	}

	/**
	 * Field type render.
	 *
	 * @param boolean $display
	 * @return void
	 */
	function render( $display = true ) {

		$validations = '';

		if ( isset( $this->field['validations'] ) ) :

			foreach ( $this->field['validations'] as $key => $attr ) :

				$validations .= sprintf( ' %1$s="%2$s" ', $key, $attr );

			endforeach;

		endif;

		$attributes = '';

		if ( isset( $this->field['attributes'] ) ) :

			foreach ( $this->field['attributes'] as $attribute => $attribute_val ) :
				/* Added to handle data-options attribute
				* @since 6.4.0
				*/
				if ( $attribute === 'data-options' ) {
					$attribute_val = htmlspecialchars(json_encode($attribute_val, JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
					$attributes .= sprintf(' %1$s=\'%2$s\'', $attribute, $attribute_val);
				} else {
					$attributes .= sprintf(' %1$s="%2$s"', $attribute, $attribute_val);
				}

			endforeach;

		endif;

		$before_field = '';

		if ( isset( $this->field['before_field'] ) ) :

			$before_field_class = isset( $this->field['before_field_class'] ) ? $this->field['before_field_class'] : '';
			$before_field       = sprintf( '<span class="%1$s">%2$s</span>', $before_field_class, $this->field['before_field'] );

		endif;

		$after_field = '';

		if ( isset( $this->field['after_field'] ) ) :

			$after_field_class = isset( $this->field['after_field_class'] ) ? $this->field['after_field_class'] : '';
			$after_field       = sprintf( '<span class="%1$s">%2$s</span>', $after_field_class, $this->field['after_field'] );

		endif;

		$output = sprintf( '%1$s<input type="%2$s" id="%3$s" name="%4$s" value="%5$s" %6$s class="%7$s" %8$s>%9$s', $before_field, esc_attr( $this->field_type ), esc_attr( $this->field['id'] ), esc_attr( $this->field['name'] ), esc_attr( $this->field['default'] ), $validations, esc_attr( $this->field['class'] ), $attributes, $after_field );

		if ( ! $display ) {

			return $output;

		}

		echo $output;
	}
}
