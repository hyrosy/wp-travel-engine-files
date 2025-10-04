<?php
/**
 * Lead Travellers Form Fields.
 *
 * @since 6.4.3
 */

namespace WPTravelEngine\Builders\FormFields;

/**
 * Form field class to render lead travellers form fields.
 */
class LeadTravellersFormFields extends LeadTravellerFormFields {

	/**
	 * @var array
	 */
	public $fields;

	public function __construct( $args = array() ) {
		$this->fields = DefaultFormFields::lead_traveller_form_fields();
		parent::__construct();
	}

	/**
	 * Render the form fields.
	 *
	 * @return void
	 */
	public function render() {
		if( empty( $this->fields ) ) {
			return;
		}
		$this->lead_traveller_form_fields();
	}

	/**
	 * Render the traveler fields.
	 *
	 * @param array $fields Form fields.
	 */
	protected function render_traveler_fields( array $fields ) {
		$instance = new parent();
		$fields   = $this->map_fields( $fields );
		$instance->init( $fields )->render();
	}

	/**
	 * Render the lead traveller form fields.
	 */
	public function lead_traveller_form_fields() {?>
			<div id="wpte-lead-traveller" class="wpte-checkout__form-section">
				<h5 class="wpte-checkout__form-title"><?php echo sprintf( __( 'Lead Traveller %d', 'wp-travel-engine' ), 1 ); ?></h5>
				<?php
				$this->render_traveler_fields( $this->fields );
				?>
			</div>
		<?php
	}


	/**
	 * Map the fields.
	 *
	 * @param array $fields Form fields.
	 * @return array
	 */
	protected function map_fields( $fields ) {
		$form_data = WTE()->session->get( 'travellers_form_data' );
		if ( ! $form_data ) {
			$form_data = [];
		}

		$fields = array_map( function ( $field ) use ( $form_data ) {
			$name = preg_match( "#\[([^\[]+)]$#", $field[ 'name' ], $matches ) ? $matches[ 1 ] : $field[ 'name' ];
			if ( $name ) {
				$field[ 'class' ]         = 'wpte-checkout__input';
				$field[ 'wrapper_class' ] = 'wpte-checkout__form-col';
				$field[ 'name' ]          = sprintf( 'travellers[%d][%s]', 0, $name );;
				$field[ 'id' ] = sprintf( 'travellers_%d_%s', 0, $name );;
			}
			$field[ 'field_label' ] = isset( $field[ 'placeholder' ] ) && $field[ 'placeholder' ] !== '' ? $field[ 'placeholder' ] : $field[ 'field_label' ];
			$field[ 'default' ]     = $form_data[0][ $name ] ?? $field[ 'default' ] ?? '';

			return $field;
		}, $fields );

		return $fields;
	}

}
