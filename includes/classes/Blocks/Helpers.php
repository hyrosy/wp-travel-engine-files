<?php
/**
 * Helper functions.
 *
 * @since 5.8.3
 */

namespace WPTravelEngine\Blocks;

class Helpers {
	private static $instance = null;

	// Constructor
	public function __construct() {
		add_action( 'init', array( $this, 'ajax_handler' ) );
	}

	public function ajax_handler() {
		// FSD ajax call for pagination.
		add_action( 'wp_ajax_handle_fsd_loader', array( $this, 'handle_fsd_pagination' ) );
		add_action( 'wp_ajax_nopriv_handle_fsd_loader', array( $this, 'handle_fsd_pagination' ) );

		// FSD ajax call for date selector.
		add_action( 'wp_ajax_handle_date_select', array( $this, 'handle_date_select' ) );
		add_action( 'wp_ajax_nopriv_handle_date_select', array( $this, 'handle_date_select' ) );
	}

	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new Helpers();
		}
		return self::$instance;
	}

	public static function generate_random_string(): string {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return sprintf( '%05s', substr( str_shuffle( str_repeat( $characters, ceil( 5 / strlen( $characters ) ) ) ), 1, 5 ) );
	}

	/**
	 * Generates a unique ID string with a prefix.
	 *
	 * @param string $prefix The prefix to append to the generated unique ID.
	 *
	 * @return string The generated unique ID.
	 */
	public static function unique_id( string $prefix = '', $sections = 3 ): string {
		return $prefix . implode( '-', array_map( array( __CLASS__, 'generate_random_string' ), range( 1, $sections ) ) );
	}

	/**
	 * FSD Date Format.
	 *
	 * @param array  $sorted_fsd
	 * @param string $date_format
	 * @return array
	 */
	public static function fsd_date_format( $sorted_fsd, $date_format, $custom_date_format ) {
		$keys       = 0;
		$today      = gmdate( 'Y-m-d' );
		$dates_data = array();
		$availability_options = class_exists( '\WTE_Fixed_Starting_Dates_Functions' ) ? \WTE_Fixed_Starting_Dates_Functions::availability() : [];
		foreach ( $sorted_fsd as $key => $fsd ) {
			if ( strtotime( $today ) <= strtotime( $fsd['start_date'] ) ) {
				$content_id = isset( $fsd['content_id'] ) ? $fsd['content_id'] : '';
				$startDate  = new \DateTime( $fsd['start_date'] );
				$endDate    = new \DateTime( $fsd['end_date'] );
				if ( $date_format == 'custom' ) {
					$start_date = $startDate->format( $custom_date_format );
					$end_date   = $endDate->format( $custom_date_format );
				} else {
					$start_date = $startDate->format( $date_format );
					$end_date   = $endDate->format( $date_format );
				}
				$availability            = isset( $fsd['availability'] ) ? $fsd['availability'] : 'guaranteed';
				$space                   = isset( $fsd['seats_left'] ) ? $fsd['seats_left'] : '';
				$price                   = isset( $fsd['fsd_cost'] ) ? $fsd['fsd_cost'] : '';
				$availability_label      = isset( $availability_options[ $availability ] ) ? $availability_options[ $availability ] : __( 'Guaranteed', 'wp-travel-engine' );
				$dates_data[ ( $keys ) ] = array(
					'content_id'   => $content_id,
					'start_date'   => $start_date,
					'end_date'     => $end_date,
					'availability' => $availability_label,
					'price'        => $price,
					'space'        => $space,
				);
			}
			++$keys;
		}
		return $dates_data;
	}

	/**
	 * FSD Date Content.
	 *
	 * @param string $trip_id
	 * @param string $loader_type
	 * @param string $current_page
	 * @param string $pagination_num
	 * @param string $selected_date
	 * @param string $date_format
	 * @return array
	 */
	public static function fsd_date_content( $trip_id, $loader_type, $current_page, $pagination_num, $selected_date, $date_format, $date_column, $availability_column, $price_column, $space_column, $start_date, $end_date, $book_btn, $custom_date_format ) {
		if ( ! defined( 'WTE_FIXED_DEPARTURE_VERSION' ) ) {
			$sorted_fsd = array();
		} else {
			$sorted_fsd = call_user_func(
				array( new \WTE_Fixed_Starting_Dates_Shortcodes(), 'generate_fsds' ),
				$trip_id,
				array(
					'year'  => '',
					'month' => '',
				)
			);
		}
		$monthNumber  = date( 'n', strtotime( $selected_date ) );
		$filtered_fsd = array();
		if ( isset( $selected_date ) && $selected_date !== 'Choose a date…' ) {
			foreach ( $sorted_fsd as $fsd ) {
				$startDate = new \DateTime( $fsd['start_date'] );
				if ( $startDate->format( 'm' ) == $monthNumber ) {
					$filtered_fsd[] = $fsd;
				}
			}
			$sorted_fsd = $filtered_fsd;
		}
		$dates_data = self::fsd_date_format( $sorted_fsd, $date_format, $custom_date_format );
		if ( $loader_type === 'more' ) {
			$dates_data = array_slice( $dates_data, $current_page * $pagination_num );
		}
		$html = '';

		foreach ( $dates_data as $key => $date ) {
			if ( $pagination_num != '' && $key == $pagination_num ) {
				break;
			}
			$html .= '<tr style="display: table-row;">';

			if ( ! empty( $date_column ) ) {
				$html .= '<td data-label="TRIP DATES" class="accordion-sdate" data-id=' . $date['content_id'] . '>';
				if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
					$html .= '<svg fill="currentColor" data-prefix="far" data-icon="calendar" xmlns="http://www.w3.org/2000/svg" class="svg-inline--fa" viewBox="0 0 448 512" height="24" width="24"><path d="M152 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24V64zM48 448C48 456.8 55.16 464 64 464H384C392.8 464 400 456.8 400 448V192H48V448z"></path></svg>';
				}
				if ( ! empty( $start_date ) ) {
					$html .= '<span class="start-date" data-id=' . $date['start_date'] . '>' . $date['start_date'] . '</span>';
				}
				if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
					$html .= ' – ';
				}
				if ( ! empty( $end_date ) ) {
					$html .= '<span class="end-date" data-id=' . $date['end_date'] . '>' . $date['end_date'] . '</span>';
				}
				$html .= '</td>';
			}
			if ( ! empty( $availability_column ) ) {
				$html .= '<td data-label="AVAILABILITY" class="accordion-availability" data-id=' . $date['availability'] . '>
					<span class="Guaranteed">' . $date['availability'] . '</span>
					</td>';
			}
			if ( ! empty( $price_column ) ) {
				$html .= '<td data-label="PRICE" class="accordion-cost">
						<span class="currency-code">
							<svg fill="currentColor" data-prefix="fas" data-icon="tag" xmlns="http://www.w3.org/2000/svg" class="svg-inline--fa" viewBox="0 0 448 512" height="24" width="24"><path d="M48 32H197.5C214.5 32 230.7 38.74 242.7 50.75L418.7 226.7C443.7 251.7 443.7 292.3 418.7 317.3L285.3 450.7C260.3 475.7 219.7 475.7 194.7 450.7L18.75 274.7C6.743 262.7 0 246.5 0 229.5V80C0 53.49 21.49 32 48 32L48 32zM112 176C129.7 176 144 161.7 144 144C144 126.3 129.7 112 112 112C94.33 112 80 126.3 80 144C80 161.7 94.33 176 112 176z"></path></svg>                                                    </span>
							<strong class="trip-cost-holder">' . wte_get_formated_price( $date['price'] ) . '</strong>
					</td>';
			}
			if ( ! empty( $space_column ) ) {
				$html .= '<td data-label="SPACE LEFT" class="accordion-seats" data-id=' . $date['content_id'] . '>
						<div class="seats-available">
							<svg fill="currentColor" data-prefix="fas" data-icon="user" xmlns="http://www.w3.org/2000/svg" class="svg-inline--fa" viewBox="0 0 448 512" height="24" width="24"><path d="M224 256c70.7 0 128-57.31 128-128s-57.3-128-128-128C153.3 0 96 57.31 96 128S153.3 256 224 256zM274.7 304H173.3C77.61 304 0 381.6 0 477.3c0 19.14 15.52 34.67 34.66 34.67h378.7C432.5 512 448 496.5 448 477.3C448 381.6 370.4 304 274.7 304z"></path></svg>
							<span class="seats">' . $date['space'] . ' Available</span>
						</div>
					</td>';
			}

			$html .= '<td data-label="" data-cost=' . $date['price'] . ' class="accordion-book" data-id=' . $date['content_id'] . '>
						<button data-info=' . $date['content_id'] . ' class="book-btn wte-fsd-list-booknow-btn btn-loading">' . $book_btn . '                                                        </button>
					</td>
				</tr>';

		}
		return array( $html, count( $sorted_fsd ) );
	}

	/**
	 * Extracts common parameters from the POST request and calls the appropriate method.
	 *
	 * @param string $loader_type The type of loader ('more' or 'date_select').
	 * @return void
	 */
	public function handle_fsd_request( $loader_type ) {
		check_ajax_referer( 'handle_fsd_nonce', 'nonce', true );
		global $post;

		$trip_id             = isset( $_POST['trip_id'] ) ? intval( $_POST['trip_id'] ) : $post->ID;
		$selected_date       = isset( $_POST['selectedDate'] ) ? $_POST['selectedDate'] : '';
		$loader_type         = isset( $_POST['loader_type'] ) ? $_POST['loader_type'] : '';
		$pagination_num      = isset( $_POST['pagination_num'] ) ? intval( $_POST['pagination_num'] ) : '';
		$current_page        = isset( $_POST['current_page'] ) ? intval( $_POST['current_page'] ) : 1;
		$date_format         = isset( $_POST['dateFormat'] ) ? $_POST['dateFormat'] : '';
		$custom_date_format  = isset( $_POST['customDateFormat'] ) ? $_POST['customDateFormat'] : '';
		$date_column         = isset( $_POST['dateColumn'] ) ? $_POST['dateColumn'] : '';
		$availability_column = isset( $_POST['availabilityColumn'] ) ? $_POST['availabilityColumn'] : '';
		$price_column        = isset( $_POST['priceColumn'] ) ? $_POST['priceColumn'] : '';
		$space_column        = isset( $_POST['spaceColumn'] ) ? $_POST['spaceColumn'] : '';
		$start_date          = isset( $_POST['startDate'] ) ? $_POST['startDate'] : '';
		$end_date            = isset( $_POST['endDate'] ) ? $_POST['endDate'] : '';
		$book_btn            = isset( $_POST['bookButton'] ) ? $_POST['bookButton'] : '';

		$response = self::fsd_date_content(
			$trip_id,
			$loader_type,
			$current_page,
			$pagination_num,
			$selected_date,
			$date_format,
			$date_column,
			$availability_column,
			$price_column,
			$space_column,
			$start_date,
			$end_date,
			$book_btn,
			$custom_date_format
		);

		if ( $loader_type == 'date_select' ) {
			echo json_encode( $response[0] );
		} else {
			echo json_encode( $response );
		}

		exit;
	}

	/**
	 * FSD Date Selector.
	 *
	 * @return void
	 */
	public function handle_date_select() {
		$helpers = self::getInstance();
		$helpers->handle_fsd_request( 'date_select' );
	}

	/**
	 * FSD Pagination.
	 *
	 * @return void
	 */
	public function handle_fsd_pagination() {
		$helpers = self::getInstance();
		$helpers->handle_fsd_request( 'more' );
	}
}
