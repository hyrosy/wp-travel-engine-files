<?php
/**
 * Partial Payment Adjustment class.
 *
 * @since 6.2.4
 */

namespace WPTravelEngine\Core;

use WPTravelEngine\Core\Cart\Item;
use WPTravelEngine\Core\Models\Post\Trip;
use WPTravelEngine\Core\Models\Settings\PluginSettings;
use WPTravelEngine\Traits\Singleton;

class PartialPayment {
	use Singleton;

	/**
	 * Payment type.
	 *
	 * @var bool
	 */
	public bool $is_enable;

	/**
	 * Payment type.
	 *
	 * @var string $type percentage|amount
	 */
	public string $type;

	/**
	 * Payment amount.
	 *
	 * @var int $amount
	 */
	public int $percentage;

	/**
	 * Payment amount.
	 *
	 * @var float $amount
	 */
	public float $amount;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$plugin_settings = PluginSettings::make();

		$this->is_enable  = $plugin_settings->get( 'partial_payment_enable', false );
		$this->type       = $plugin_settings->get( 'partial_payment_option', 'percent' );
		$this->percentage = (int) $plugin_settings->get( 'partial_payment_percent', 0 );
		$this->amount     = (float) $plugin_settings->get( 'partial_payment_amount', 0 );
	}

	public function apply( $price ) {
		if ( 'percent' === $this->type ) {
			return ( $price * $this->percentage ) / 100;
		}

		return $this->amount;
	}

	public function apply_to_cart_item( Item $cart_item ): float {

		$partial_amount = 0;

		$trip = new Trip( $cart_item->trip_id );

		$is_enable = wptravelengine_toggled( $trip->get_setting( 'partial_payment_enable' ) );
		if ( ! $is_enable || ! $this->is_enable ) {
			return $partial_amount;
		}

		$partial_payment_use  = $trip->get_setting( 'partial_payment_use' );
		$partial_payment_type = $trip->get_setting( 'partial_payment_type' );
		if ( 'global' === $partial_payment_use || ! $partial_payment_type ) {
			$partial_payment_type = $this->type;
		}

		switch ( $partial_payment_type ) {
			case 'percent':
				$percentage = $trip->get_setting( 'partial_payment_percent' );
				if ( 'global' === $partial_payment_use || ! $percentage ) {
					$percentage = $this->percentage;
				}

				$partial_amount = ( $cart_item->get_totals( 'total' ) * $percentage ) / 100;
				break;
			case 'amount':
				$amount_per_person = $trip->get_setting( 'partial_payment_amount' );
				if ( 'global' === $partial_payment_use || ! $amount_per_person ) {
					$amount_per_person = $this->amount;
				}
				$total_person = array_column(
					$cart_item->get_additional_line_items()[ 'pricing_category' ] ?? [],
					'quantity'
				);

				$partial_amount = $amount_per_person * array_sum( $total_person );
				break;
		}

		return $partial_amount;
	}

}
