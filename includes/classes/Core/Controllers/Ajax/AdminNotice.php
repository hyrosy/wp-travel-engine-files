<?php
/**
 * Admin notice controller.
 *
 * @package WPTravelEngine/Core/Controllers
 * @since 6.5.5
 */

namespace WPTravelEngine\Core\Controllers\Ajax;

use WP_Error;
use WPTravelEngine\Abstracts\AjaxController;

/**
 * Handles admin notice ajax request.
 */
class AdminNotice extends AjaxController {

	const NONCE_KEY = 'nonce';
	const NONCE_ACTION = '_wptravelengine_notice_dismiss';
	const ACTION = 'wptravelengine_notice_dismiss';

	/**
	 * Process Request.
	 */
	protected function process_request() {
        $last_updated = $this->request->get_param( 'last_updated' );
		if ( $last_updated ) {
			update_option( 'wptravelengine_notice_dismissed_at', $last_updated );
		}
	}

}
