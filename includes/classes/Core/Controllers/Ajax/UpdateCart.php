<?php
/**
 * Update Trip Cart Controller.
 *
 * @package WPTravelEngine/Core/Controllers
 * @since 6.0.0
 */

namespace WPTravelEngine\Core\Controllers\Ajax;

use WPTravelEngine\Abstracts\AjaxController;

/**
 * Handles update trip cart through ajax requests.
 */
class UpdateCart extends AjaxController {

	const NONCE_KEY    = 'nonce';
	const NONCE_ACTION = 'update_cart_action_nonce';
	const ACTION       = 'wte_update_cart';

	/**
	 * Process Request.
	 * Callback function for update to cart ajax.
	 *
	 * @since    1.0.0
	 */
	public function process_request() {
		$request        = $this->request->get_params();
		$result['type'] = 'success';

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $request['data2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			parse_str( wte_clean( wp_unslash( $request['data2'] ) ), $values ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$cost = '';
			foreach ( $values['trips'] as $value ) {
				$option = get_post_meta( $value, 'wp_travel_engine_setting', true );
				$cost   = $option['trip_price'];
				$cost  += $cost;
			}
			$travelers = '';
			foreach ( $values['travelers'] as $value ) {
				$travelers  = $value;
				$travelers += $travelers;
			}
			$len = count( $values['trips'] );

			for ( $i = 0; $i < $len; $i++ ) {
				$option = get_post_meta( $values['trips'][ $i ], 'wp_travel_engine_setting', true );
				$cost   = $option['trip_price'];
				$tc     = $tc + ( $cost * $values['travelers'][ $i ] );
			}
			$post = max( array_keys( $values['trips'] ) );
			$pid  = get_post( $values['trips'][ $post ] );
			$slug = $pid->post_title;
			$arr  = array(
				'place_order' => array(
					'travelers' => esc_attr( $travelers ),
					'trip-cost' => esc_attr( $tc ),
					'trip-id'   => esc_attr( end( $values['trips'] ) ),
					'tname'     => esc_attr( $slug ),
					'trip-date' => esc_attr( end( $values['trip-date'] ) ),
				),
			);
		} else {
			header( 'Location: ' . $_SERVER[ 'HTTP_REFERER' ] ); // phpcs:ignore
		}

		die();
	}
}
