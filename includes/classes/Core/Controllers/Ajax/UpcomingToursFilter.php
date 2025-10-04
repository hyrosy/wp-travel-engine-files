<?php
/**
 * Upcoming tours filter controller.
 *
 * @since 6.4.1
 */
namespace WPTravelEngine\Core\Controllers\Ajax;

use WPTravelEngine\Abstracts\AjaxController;
use WPTravelEngine\Pages\Admin\UpcomingTours;

class UpcomingToursFilter extends AjaxController {

	const NONCE_KEY    = 'nonce';
	const NONCE_ACTION = 'wte_filter_upcoming_tours';
	const ACTION       = 'wte_filter_upcoming_tours';


	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Process request
	 */
	protected function process_request() {
		$post = $this->request->get_body_params();
		$html = UpcomingTours::get_upcoming_tours_html(
			array(
				'date'   => $post['date'],
				'count'  => $post['count'],
			)
		);
		wp_send_json_success( array( 'html' => $html ) );
	}
}
