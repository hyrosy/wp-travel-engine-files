<?php
/**
 * Remove Trip Cart Controller.
 *
 * @package WPTravelEngine/Core/Controllers
 * @since 6.0.0
 */

namespace WPTravelEngine\Core\Controllers\Ajax;

use WPTravelEngine\Abstracts\AjaxController;

/**
 * Handles remove trip cart through ajax requests.
 */
class RemoveCart extends AjaxController {

	const NONCE_KEY    = 'nonce';
	const NONCE_ACTION = 'wte-remove-nonce';
	const ACTION       = 'wte_remove_order';

	/**
	 * Process Request.
	 * Callback function for update to cart ajax.
	 *
	 * @since    1.0.0
	 */
	public function process_request() {
		$request = $this->request->get_params();

		// phpcs:disable
		if ( isset( $request[ 'trip_id' ] ) && isset( $_SESSION[ 'cart_item' ] ) ) {
			unset( $_SESSION[ 'cart_item' ][ $request[ 'trip_id' ] ] );
			$result[ 'type' ] = 'success';
		} else {
			$result[ 'type' ] = 'error';
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			echo wp_json_encode( $result ); // phpcs:ignore
		} else {
			header( 'Location: ' . $_SERVER[ 'HTTP_REFERER' ] );
		}

		die();
		// phpcs:enable
	}
}
