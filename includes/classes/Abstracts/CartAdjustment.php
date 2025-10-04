<?php
/**
 * Fee Item.
 *
 * @since 6.3.0
 */

namespace WPTravelEngine\Abstracts;

use WPTravelEngine\Core\Cart\Cart;
use WPTravelEngine\Core\Cart\Item;
use WPTravelEngine\Interfaces\CartAdjustment as CartAdjustmentInterface;

abstract class CartAdjustment implements CartAdjustmentInterface {

	/**
	 * The order of the item.
	 *
	 * @var int
	 */
	public int $order;

	public array $args;

	/**
	 * A unique identifier for the item.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * @var string
	 */
	public string $label;

	/**
	 * Cart instance.
	 *
	 * @var Cart
	 */
	public Cart $cart;

	/**
	 * @var string
	 */
	public string $description;

	/**
	 * @var float
	 */
	public float $percentage;

	/**
	 * @var string
	 */
	public string $adjustment_type;

	/**
	 * Cart item type|name to apply this fee.
	 *
	 * @var array
	 */
	public array $applies_to;

	/**
	 * @var bool
	 */
	public bool $apply_to_actual_subtotal;


	/**
	 * @var float
	 */
	public float $value;

	/**
	 * @var string
	 */
	public string $type;

	/**
	 * Constructor.
	 *
	 * @param Cart $cart The cart instance.
	 * @param array $args The arguments for the item.
	 */
	public function __construct( Cart $cart, array $args = array() ) {
		$this->cart = $cart;

		$args = wp_parse_args(
			$args,
			array(
				'name'                     => '',
				'label'                    => '',
				'description'              => '',
				'percentage'               => 0,
				'adjustment_type'          => 'percentage',
				'applies_to'               => array(),
				'order'                    => - 1,
				'apply_to_actual_subtotal' => false,
				'value'                    => 0,
				'type'                     => '',
			)
		);

		$this->name                     = (string) $args[ 'name' ];
		$this->label                    = (string) $args[ 'label' ];
		$this->description              = (string) $args[ 'description' ] ?? '';
		$this->percentage               = (float) $args[ 'percentage' ];
		$this->adjustment_type          = (string) $args[ 'adjustment_type' ];
		$this->applies_to               = (array) $args[ 'applies_to' ];
		$this->order                    = (int) $args[ 'order' ];
		$this->apply_to_actual_subtotal = (bool) $args[ 'apply_to_actual_subtotal' ];
		$this->value                    = (float) $args['value'] ?? 0;
		$this->type                     = (string) $args['type'] ?? '';
	}

	/**
	 * Get the adjustment percentage.
	 *
	 * @return float
	 */
	public function get_percentage(): float {
		return (float) $this->percentage;
	}

	/**
	 * @param float $total
	 * @param Item $cart_item
	 *
	 * @return float
	 */
	public function apply( float $total, Item $cart_item ): float {
		if ( 'percentage' === $this->adjustment_type ) {
			return $total * $this->percentage / 100;
		}

		return (float) $this->percentage / count( $cart_item->get_additional_line_items() );
	}

	/**
	 * @return array
	 * @since 6.4.0
	 */
	public function data(): array {
		return array(
			'name'                     => $this->name,
			'order'                    => $this->order,
			'label'                    => $this->label,
			'description'              => $this->description,
			'adjustment_type'          => $this->adjustment_type,
			'apply_to_actual_subtotal' => $this->apply_to_actual_subtotal,
			'percentage'               => $this->percentage,
			'applies_to'               => $this->applies_to,
			'value'                    => $this->value,
			'type'                     => $this->type,
		);
	}

	/*
	   * Render.
	   *
	   * @return string
	   * @since 6.3.5
	   */
	public function render(): string {
		if ( ! isset( $this->cart->get_totals()[ "total_{$this->name}" ] ) ) {
			return '';
		}
		return sprintf(
			'<tr class="wpte-checkout__booking-summary-%s">
				<td>%s%s</td>
				<td><strong>%s %s</strong></td>
			</tr>',
			'coupon' === $this->name ? 'discount' : ( $this->type === 'fee' ? 'tax' : ( $this->type === 'deductible' ? 'discount' : $this->name ) ),
			$this->label,
			! empty( $this->description )
				? sprintf(
				'<span id="%s-tooltip" class="wpte-checkout__tooltip" data-content="%s">
						<svg><use xlink:href="#help"></use></svg>
					</span>',
				$this->name,
				$this->description
			)
				: '',
			'coupon' === $this->name ? '-' : '+',
			wptravelengine_the_price( $this->cart->get_totals()[ "total_{$this->name}" ], false )
		);
	}

}
