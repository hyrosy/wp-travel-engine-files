<?php
/**
 * Cart Info Parser.
 *
 * @since 6.4.0
 */

namespace WPTravelEngine\Helpers;

use WPTravelEngine\Utilities\ArrayUtility;

class CartInfoParser {

	/**
	 * @var array
	 */
	protected array $data;

	/**
	 * @var array
	 */
	protected array $totals = array();

	/**
	 * @var array
	 */
	protected array $deductible_items = array();

	/**
	 * @var array
	 */
	protected array $fees = array();

	/**
	 * @var array|mixed
	 */
	protected array $items = array();

	/**
	 * @var string
	 */
	protected string $currency;

	public function __construct( array $data ) {
		$this->data = $data;
		$this->parse( $data );
	}

	protected function parse( $data ) {
		$this->totals           = $data[ 'totals' ] ?? array();
		$this->deductible_items = $this->parse_deductible_items( $data );
		$this->fees             = $this->parse_fees( $data );
		$this->items            = $data[ 'items' ] ?? array();
		$this->currency         = $data[ 'currency' ] ?? '';
	}

	protected function parse_deductible_items( $data ) {
		if ( isset( $data[ 'deductible_items' ] ) ) {
			return $data[ 'deductible_items' ];
		}

		if ( ! empty( $data[ 'discounts' ] ) ) {
			return array_map( function ( $item ) {
				return array(
					'name'                     => 'coupon',
					'order'                    => '-1',
					'label'                    => $item[ 'name' ],
					'description'              => '',
					'adjustment_type'          => $item[ 'type' ],
					'apply_to_actual_subtotal' => true,
					'percentage'               => $item[ 'value' ],
					'value'                    => $this->totals[ 'discount_total' ] ?? 0,
				);
			}, $data[ 'discounts' ] );
		}

		return array();
	}

	protected function parse_fees( $data ) {
		if ( isset( $data[ 'fees' ] ) ) {
			$unique_fees = [];
			foreach ( $data[ 'fees' ] as $fee ) {
				$unique_fees[ $fee[ 'name' ] ] = $fee;
			}

			return array_values( $unique_fees );
		}

		if ( ! empty( $data[ 'tax_amount' ] ) ) {
			return array(
				array(
					'name'                     => 'tax',
					'order'                    => '-1',
					'label'                    => __( 'Tax', 'wp-travel-engine' ),
					'description'              => '',
					'adjustment_type'          => 'percentage',
					'apply_to_actual_subtotal' => false,
					'percentage'               => $data[ 'tax_amount' ],
					'value'                    => $this->totals[ 'total_tax' ] ?? 0,
				),
			);
		}

		return array();
	}

	/**
	 * @param string|null $key
	 *
	 * @return array|float|null
	 */
	public function get_totals( string $key = null ) {
		if ( $key ) {
			return round( $this->totals[ $key ] ?? 0, 2 );
		}

		return $this->totals;
	}

	public function get_deductible_items(): array {
		return array_map( function ( $item ) {
			if ( ! isset( $item[ 'value' ] ) ) {
				$item[ 'value' ] = $this->get_totals( 'total_' . $item[ 'name' ] );
			}

			return $item;
		}, $this->deductible_items );
	}

	public function get_fees(): array {
		return array_map( function ( $item ) {
			if ( ! isset( $item[ 'value' ] ) ) {
				$item[ 'value' ] = $this->get_totals( 'total_' . $item[ 'name' ] );
			}

			return $item;
		}, $this->fees );
	}

	/**
	 * @return BookedItem[]
	 */
	public function get_items(): array {
		return array_map( function ( $line ) {
			return new BookedItem( $line );
		}, $this->items );
	}

	public function get_item( string $id = null ): BookedItem {
		if ( $id ) {
			foreach ( $this->get_items() as $item ) {
				if ( $item[ 'id' ] == $id ) {
					return $item;
				}
			}
		}

		return $this->get_items()[ 0 ] ?? new BookedItem( array() );
	}

	public function get_currency(): string {
		return $this->currency;
	}

	public function __get( $key ) {
		if ( method_exists( $this, "get_{$key}" ) ) {
			return $this->{"get_{$key}"}();
		}

		return $this->{$key} ?? $this->data[ $key ] ?? null;
	}
}
