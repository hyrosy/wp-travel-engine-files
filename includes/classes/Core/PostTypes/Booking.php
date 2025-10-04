<?php
/**
 * Post Type Booking.
 *
 * @package WPTravelEngine/Core/PostTypes
 * @since 6.0.0
 */

namespace WPTravelEngine\Core\PostTypes;

use WP_Exception;
use WPTravelEngine\Abstracts\PostType;
use WPTravelEngine\Builders\FormFields\BillingEditFormFields;
use WPTravelEngine\Builders\FormFields\EmergencyEditFormFields;
use WPTravelEngine\Builders\FormFields\PaymentEditFormFields;
use WPTravelEngine\Builders\FormFields\TravellerEditFormFields;
use WPTravelEngine\Builders\FormFields\OrderTripEditFormFields;
use WPTravelEngine\Core\Cart\Adjustments\CouponAdjustment;
use WPTravelEngine\Core\Cart\Adjustments\TaxAdjustment;
use WPTravelEngine\Core\Cart\Items\PricingCategory;
use WPTravelEngine\Core\Cart\Items\ExtraService;
use WPTravelEngine\Core\Models\Post\Booking as BookingModel;
use WPTravelEngine\Core\Models\Post\Payment;
use WPTravelEngine\Helpers\BookedItem;
use WPTravelEngine\Helpers\CartInfoParser;
use WPTravelEngine\Helpers\Functions;
use WPTravelEngine\Utilities\ArrayUtility;
use WPTravelEngine\Core\Models\Post\Customer;
use WPTravelEngine\Validator\Validator;
use DateTime;
use WPTravelEngine\Core\Booking\BookingProcess;
use WPTravelEngine\Core\Models\Post\TripPackageIterator;
use WPTravelEngine\Core\Models\Post\TripPackage;
use WPTravelEngine\Core\Models\Post\Trip;
use WPTravelEngine\Abstracts\CartItem;

/**
 * Class Trip
 * This class represents a trip to the WP Travel Engine plugin.
 *
 * @since 6.0.0
 */
class Booking extends PostType {

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	protected string $post_type = 'booking';


	/**
	 * Constructor.
	 *
	 * @since 6.4.0
	 */
	public function __construct() {
		add_action( "add_meta_boxes_{$this->post_type}", array( $this, 'meta_box_booking' ) );
		add_action( 'wp_insert_post', array( $this, 'save' ), 10, 3 );
		add_action( 'restrict_manage_posts', array( $this, 'add_filter_options' ) );
		add_action( 'parse_query', array( $this, 'filter_bookings' ) );
		add_filter( 'disable_months_dropdown', array( $this, 'remove_date_filter' ) );
		add_action( 'admin_init', array( $this, 'export_bookings' ) );
		add_action( 'admin_head', array( $this, 'add_booking_export_button' ) );
		add_filter( 'wptravelengine_booking_line_item_group_title', array(
			$this,
			'add_booking_line_item_title',
		), 10, 2 );
		add_filter( 'wptravelengine_booking_line_items', array( $this, 'add_booking_line_items' ), 10, 2 );
	}

	/**
	 * Add booking line item title.
	 *
	 * @param string $title Title.
	 * @param array $item Item.
	 *
	 * @return string
	 * @since 6.4.0
	 */
	public function add_booking_line_item_title( string $title, array $item ): string {
		if ( 'pricing_category' === $title ) {
			$title = __( 'Price Category', 'wp-travel-engine' );
		}
		if ( 'extra_service' === $title ) {
			$title = wptravelengine_settings()->get( 'extra_service_title' ) ?? __( 'Extra Services', 'wp-travel-engine' );
		}

		return $title;
	}

	/**
	 * Add booking line items.
	 *
	 * @param array $line_items Line items.
	 * @param BookedItem $item Item.
	 *
	 * @return array
	 * @since 6.4.0
	 */
	public function add_booking_line_items( array $line_items, BookedItem $item ): array {
		$line_items[ 'pricing_category' ] ??= array();
		if ( wptravelengine_is_addon_active( 'extra-services' ) ) {
			$line_items[ 'extra_service' ] ??= array();
		}

		return $line_items;
	}

	/**
	 * Retrieve the labels for the Booking post type.
	 *
	 * Returns an array containing the labels used for the Booking post type, including
	 * names for various elements such as the post type itself, singular and plural names,
	 * menu labels, and more.
	 *
	 * @return array An array containing the labels for the Booking post type.
	 */
	public function get_labels(): array {
		return array(
			'name'               => _x( 'Bookings', 'post type general name', 'wp-travel-engine' ),
			'singular_name'      => _x( 'Booking', 'post type singular name', 'wp-travel-engine' ),
			'menu_name'          => _x( 'WP Travel Engine', 'admin menu', 'wp-travel-engine' ),
			'name_admin_bar'     => _x( 'Booking', 'add new on admin bar', 'wp-travel-engine' ),
			'add_new'            => _x( 'Add New', 'Booking', 'wp-travel-engine' ),
			'add_new_item'       => esc_html__( 'Add New Booking', 'wp-travel-engine' ),
			'new_item'           => esc_html__( 'New Booking', 'wp-travel-engine' ),
			'edit_item'          => esc_html__( 'Edit Booking', 'wp-travel-engine' ),
			'view_item'          => esc_html__( 'View Booking', 'wp-travel-engine' ),
			'all_items'          => esc_html__( 'Bookings', 'wp-travel-engine' ),
			'search_items'       => esc_html__( 'Search Bookings', 'wp-travel-engine' ),
			'parent_item_colon'  => esc_html__( 'Parent Bookings:', 'wp-travel-engine' ),
			'not_found'          => esc_html__( 'No Bookings found.', 'wp-travel-engine' ),
			'not_found_in_trash' => esc_html__( 'No Bookings found in Trash.', 'wp-travel-engine' ),
		);
	}

	/**
	 * Retrieve the post type name.
	 *
	 * Returns the name of the post type.
	 *
	 * @return string The name of the post type.
	 */
	public function get_post_type(): string {
		return $this->post_type;
	}

	/**
	 * Retrieve the icon for the Booking post type.
	 *
	 * Returns the icon for the Booking post type.
	 *
	 * @return string The icon for the Booking post type.
	 */
	public function get_icon(): string {
		return 'data:image/svg+xml;base64,' . base64_encode( '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_60_548)"><path d="M22.8963 12.1856C23.1956 11.7415 22.7501 11.3673 22.7501 11.3673C22.7501 11.3673 22.2301 11.1051 21.9322 11.5491C21.633 11.9932 20.8789 13.1159 20.8789 13.1159L17.8029 13.1871L17.287 13.954L19.8988 14.572L18.7272 15.9741C19.0916 16.1151 19.4014 16.3747 19.7525 16.5486L20.863 15.2085L22.4442 17.359L22.9602 16.5921L21.8418 13.7524C21.8431 13.7524 22.5984 12.6297 22.8963 12.1856Z" fill="white"></path><path d="M11.9222 11.5544C12.8513 11.5544 13.6045 10.8081 13.6045 9.88745C13.6045 8.96683 12.8513 8.22052 11.9222 8.22052C10.9931 8.22052 10.2399 8.96683 10.2399 9.88745C10.2399 10.8081 10.9931 11.5544 11.9222 11.5544Z" fill="white"></path><path d="M21.2379 13.4954C20.9587 13.3215 20.589 13.4045 20.4134 13.6825C18.7032 16.3733 16.9172 17.8439 15.2482 17.9335C13.1351 18.0495 11.744 16.011 10.5299 14.6498C9.8862 13.9276 9.30105 13.1568 8.79038 12.3371C8.3861 11.6901 7.93927 10.9166 7.93927 10.1339C7.93794 7.95699 9.72528 6.18596 11.9222 6.18596C14.1178 6.18596 15.9052 7.95699 15.9052 10.1339C15.9052 11.4371 14.3226 13.5244 12.9635 15.0477C12.7494 15.2875 12.7733 15.6525 13.0114 15.87C13.0154 15.8726 13.018 15.8766 13.022 15.8792C13.2641 16.1006 13.6444 16.0795 13.8625 15.8357C15.2668 14.2716 17.1034 11.8904 17.1034 10.1326C17.1021 7.30208 14.7788 5 11.9222 5C9.06567 5 6.74106 7.30208 6.74106 10.1339C6.74106 11.7876 8.36749 13.9935 9.73326 15.555L9.72927 15.5511C10.091 15.8897 10.4022 16.2996 10.744 16.6593C11.4076 17.3551 12.0858 18.0969 12.9382 18.5634C12.9396 18.5647 12.9422 18.5647 12.9475 18.5687C13.5181 18.877 14.2375 19.1235 15.0807 19.1235C15.1511 19.1235 15.223 19.1221 15.2961 19.1182C17.4039 19.0141 19.4666 17.3972 21.4255 14.3137C21.6023 14.037 21.5172 13.6707 21.2379 13.4954Z" fill="white"></path><path d="M10.6349 17.7979C10.4607 17.6345 10.2054 17.5937 9.98463 17.6859C9.58567 17.852 9.11889 17.9626 8.59625 17.9337C6.92727 17.844 5.14126 16.3735 3.4377 13.6919L2.11049 11.5137C1.94027 11.233 1.57189 11.1434 1.28996 11.312C1.0067 11.482 0.914938 11.8457 1.08649 12.1264L2.41902 14.3138C4.37791 17.3973 6.44054 19.0142 8.54838 19.1183C8.62152 19.1222 8.69333 19.1236 8.76381 19.1236C9.40082 19.1236 9.96867 18.9826 10.4541 18.7796C10.8544 18.6123 10.9528 18.0957 10.6376 17.7992L10.6349 17.7979Z" fill="white"></path></g></svg>' ); // phpcs:ignore WordPress.WP.EnsuredPHPCS.Base64Encode.FileWithoutSafety
	}

	/**
	 * Retrieve the arguments for the Booking post type.
	 *
	 * Returns an array containing the arguments used to register the Booing post type.
	 *
	 * @return array An array containing the arguments for the Booking post type.
	 */
	public function get_args(): array {

		return array(
			'labels'             => $this->get_labels(),
			'description'        => esc_html__( 'Description.', 'wp-travel-engine' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'menu_icon'          => $this->get_icon(),
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'booking' ),
			'capability_type'    => 'post',
			'capabilities'       => $this->get_capabilities(),
			'map_meta_cap'       => true, // Set to `false`, if users are not allowed to edit/delete existing posts
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 31,
			'supports'           => array( '' ),
		);
	}

	/**
	 * Get capabilities.
	 *
	 * @return array
	 * @since 6.4.0
	 */
	public function get_capabilities(): array {
		// TODO: Add capabilities for the booking post type specifically once we define particular capabilities for the booking post type.
		return array(
			'edit_post'          => 'edit_trip',
			'read_post'          => 'read_trip',
			'delete_post'        => 'delete_trip',
			'edit_posts'         => 'edit_trips',
			'edit_others_posts'  => 'edit_others_trips',
			'publish_posts'      => 'publish_trips',
			'read_private_posts' => 'read_private_trips',
		);
	}

	/**
	 * Add filter options.
	 *
	 * @param string $post_type Post type.
	 *
	 * @since 5.7.4 - Booking Export button added.
	 * @modified_since 6.3.5 - Trip Name filter and Booking Status filter added.
	 */
	public function add_filter_options( $post_type ) {
		$current_screen = get_current_screen();
		if ( 'booking' !== $post_type && 'edit-booking' !== $current_screen->id ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		// Booking status and Trip Name filter options.
		$trips            = wp_travel_engine_get_trips_array();
		$status           = wp_travel_engine_get_booking_status();
		$booking_selected = isset( $_REQUEST[ 'booking_status' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'booking_status' ] ) ) : 'all';
		$trip_selected    = isset( $_REQUEST[ 'trip_id' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'trip_id' ] ) ) : 'all';

		$mappings = array(
			'trip_id'        => array(
				'data'     => $trips,
				'label'    => __( 'Trip Name', 'wp-travel-engine' ),
				'selected' => $trip_selected,
			),
			'booking_status' => array(
				'data'     => $status,
				'label'    => __( 'Booking Status', 'wp-travel-engine' ),
				'selected' => $booking_selected,
			),
		);
		foreach ( $mappings as $id => $data ) { ?>
			<select id="<?php echo esc_attr( $id ); ?>_filter" name="<?php echo esc_attr( $id ); ?>">
				<option value="all"> <?php echo esc_html( $data[ 'label' ] ); ?> </option>
				<?php
				foreach ( $data[ 'data' ] as $key => $value ) :
					$display = 'booking_status' === $id ? $value[ 'text' ] : $value;
					?>
					<option value="<?php echo esc_html( $key ); ?>" <?php selected( $data[ 'selected' ], $key ); ?>>
						<?php echo esc_html( $display ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Remove date filter given by WordPress
	 *
	 * @return bool
	 * @since 6.3.5
	 *
	 */
	public function remove_date_filter() {
		return isset( $_GET[ 'post_type' ] ) && 'booking' === $_GET[ 'post_type' ] ? true : false;
	}

	/**
	 * Export bookings.
	 *
	 * @since 5.7.4
	 */
	public function export_bookings() {
		require_once plugin_dir_path( WP_TRAVEL_ENGINE_FILE_PATH ) . '/admin/class-wp-travel-engine-booking-export.php';
		$booking_export = new \WP_Travel_Engine_Booking_Export();
		$booking_export->init();
	}

	/**
	 * Add Booking export button.
	 *
	 * @since 5.7.4
	 * @modified_since 6.3.5 - Added the booking export button to the booking page.
	 */
	public function add_booking_export_button() {
		global $post_type;

		$current_screen = get_current_screen();

		if ( 'edit-booking' !== $current_screen->id ) {
			return;
		}

		if ( isset( $_GET[ 'post_type' ] ) && 'booking' === $_GET[ 'post_type' ] && 'booking' == $post_type ) {
			// Remove admin notices.
			remove_all_actions( 'admin_notices' );

			$trips = wp_travel_engine_get_trips_array() ?? array();
			$trips = array( 'all' => __( 'Select Trip', 'wp-travel-engine' ) ) + $trips;

			$status = wp_travel_engine_get_booking_status() ?? array();
			$status = array_merge(
				array(
					'all' => array(
						'color' => '',
						'text'  => 'Select Booking Status',
					),
				),
				$status
			);

			$trip_selected   = isset( $_REQUEST[ 'trip_id' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'trip_id' ] ) ) : 'all';
			$status_selected = isset( $_REQUEST[ 'booking_status' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'booking_status' ] ) ) : 'all';

			?>
			<form id="wpte-booking-export-form" class="wpte-export-form" method="post">
				<?php wp_nonce_field( 'booking_export_nonce_action', 'booking_export_nonce' ); ?>
				<input type="text" data-fpconfig='{"mode":"range","showMonths":"2"}' id="wte-flatpickr__date-range"
					   class="wte-flatpickr">
				<button id="wpte-booking-export-open-modal" type="button" class="button button-primary">
					<?php esc_html_e( 'Export Bookings', 'wp-travel-engine' ); ?>
				</button>
				<div class="wpte-booking-export-modal-overlay">
					<div class="wpte-booking-export-modal">
						<div class="wpte-booking-export-modal-header">
							<h2><?php esc_html_e( 'Export Bookings', 'wp-travel-engine' ); ?></h2>
							<button type="button" class="wpte-booking-modal-close">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
									 xmlns="http://www.w3.org/2000/svg">
									<path d="M18 6L6 18M6 6L18 18" stroke="#F04438" stroke-width="2"
										  stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</button>
						</div>
						<div class="wpte-booking-export-modal-body">
							<div class="wpte-field">
								<label
									for="wpte-booking-export-date"><?php esc_html_e( 'Date', 'wp-travel-engine' ); ?></label>
								<input style="max-width: 320px;" id="wpte-booking-export-date" type="text"
									   name="wte_booking_range" data-fpconfig='{"mode":"range","showMonths":"2"}'
									   name="wte-flatpickr__date"
									   value="<?php echo esc_attr( isset( $_POST[ 'wte_booking_range' ] ) ? $_POST[ 'wte_booking_range' ] : '' ); ?>"
									   class="wte-flatpickr">
							</div>
							<div class="wpte-field">
								<label
									for="wpte-booking-export-trip"><?php esc_html_e( 'Trip', 'wp-travel-engine' ); ?></label>
								<select name="wptravelengine_trip_id" id="wpte-booking-export-trip">
									<?php foreach ( $trips as $key => $value ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"
												name="wptravelengine_trip_id" <?php selected( $trip_selected, $key ); ?>><?php echo esc_html( $value ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="wpte-field">
								<label
									for="wpte-booking-export-status"><?php esc_html_e( 'Booking Status', 'wp-travel-engine' ); ?></label>
								<select style="max-width: 320px;" name="wptravelengine_booking_status"
										id="wpte-booking-export-status">
									<?php foreach ( $status as $key => $value ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"
												name="wptravelengine_booking_status" <?php selected( $status_selected, $key ); ?>><?php echo esc_html( $value[ 'text' ] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="wpte-booking-export-modal-footer">
							<input type="submit" name="booking_export_submit" class="wpte-booking-export-submit button"
								   value="<?php esc_html_e( 'Export', 'wp-travel-engine' ); ?>">
						</div>
					</div>
				</div>
			</form>
			<?php
		}
		?>
		<?php
	}

	/**
	 * @return void
	 * @since 6.4.0
	 */
	public function save( $post_id, $post, $update = false ) {

		// Verify nonce.
		if ( $this->post_type !== $post->post_type || ! isset( $_POST[ 'wptravelengine_new_booking_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'wptravelengine_new_booking_nonce' ], 'wptravelengine_new_booking' ) ) {
			return;
		}

		$request = Functions::create_request( 'POST' );

		$booking = new BookingModel( $post_id );

		$form_validator = new Validator();

		if ( isset( $post->ID ) && $post->post_status == 'draft' ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'publish',
				)
			);

			clean_post_cache( $post_id );
			$post->post_status = 'publish';
		}

		$booking->set_meta( '_user_edited', 'yes' );

		global $wte_cart;

		// Get the booking data from the request
		$trip_info = $request->get_param( 'order_trip' );
		if ( ! is_array( $trip_info ) || ! isset( $trip_info['id'] ) ) {
			return;
		}
		$trip_id = $trip_info['id'];
		$cart_info = $booking->get_cart_info();

		foreach ( $cart_info['items'][0]['line_items'] as $key => $line_item ) {

			if ( 'pricing_category' === $key || empty( $line_item ) ) {
				continue;
			} else if ( 'extra_service' === $key ) {
				$subtotal_reservations = array_map(
					function ( $extra_service ) {
						return array(
							'id'       => $extra_service['id'] ?? 'es_' . uniqid(),
							'quantity' => (int) ( $extra_service['quantity'] ?? 1 ),
						);
					},
					$line_item
				);
				$key = 'extraServices';
			} else {
				$subtotal_reservations = array_map(
					function ( $acc_item ) use ( $key ) {
						return array(
							'id'       => null,
							'manual'   => true,
							'label'    => $acc_item['label'] ?? 'Manual ' . $key,
							'quantity' => (int) ( $acc_item['quantity'] ?? 1 ),
							'price'    => (float) ( $acc_item['price'] ?? 0 ),
							'total'    => (float) ( $acc_item['total'] ?? 0 ),
						);
					},
					$line_item
				);
			}

			$cart_info['items'][0]['subtotal_reservations'][$key] = $subtotal_reservations;
			$cart_info['subtotal_reservations'][$key]             = $subtotal_reservations;

		}

		$package_name = sanitize_text_field( $request->get_param( 'order_trip' )['package_name'] ?? '' );
		
		if( $trip_id ) {
			$price_key = $trip_id;
			if ( ! get_post( $trip_id ) ) {
				return;
			}
			$trip = new Trip( $trip_id );
			$trip_packages = new TripPackageIterator( $trip );
			
			foreach ( $trip_packages as $trip_package ) {
				/** @var TripPackage $trip_package */
				if ( isset( $trip_package->post->post_title ) && 
					$trip_package->post->post_title === $package_name ) {
					$price_key = $trip_package->post->ID ?? $trip_id;
					break;
				}
			}
		}

		$line_items = $request->get_param( 'line_items' );
		$pax = array();
		if ( is_array( $line_items ) && isset( $line_items['pricing_category']['quantity'] ) ) {
			$pax = $line_items['pricing_category']['quantity'];
		}

		$cart_items = array(
			'trip_id'               => $trip_id,
			'trip_date'             => date('Y-m-d', strtotime($trip_info['start_date'])),
			'trip_time'             => date('Y-m-d\TH:i', strtotime($trip_info['start_date'])),
			'price_key'             => $price_key ?? 0,
			'pax'                   => $pax ?? array(),
			'pax_cost'              => array(),
			'trip_price'            => 0,
			'multi_pricing_used'    => false,
			'trip_extras'           => array(),
			'package_name'          => $trip_info['package_name'] ?? '',
			'subtotal_reservations' => $cart_info['subtotal_reservations'] ?? array(),
			'line_items'            => $cart_info['items'][0]['line_items'] ?? array(),
			'travelers_count'       => $trip_info['number_of_travelers'] ?? 0,
			'trip_end_date'         => date('Y-m-d\TH:i', strtotime($trip_info['end_date'])),
			'trip_end_time'         => date('Y-m-d\TH:i', strtotime($trip_info['end_date'])),
		);

		// Add trip time range if end date exists.
		if ( isset( $trip_info['end_date'] ) ) {
			$cart_items['trip_time_range'] = array(
				date('Y-m-d\TH:i', strtotime( $trip_info['start_date'] ?? '' ) ),
				date('Y-m-d\TH:i', strtotime( $trip_info['end_date'] ?? '' ) ),
			);
		}

		// Clear existing cart items and set up new cart
		$wte_cart->clear();

		// Create cart item and add to cart in one operation
		$item = new \WPTravelEngine\Core\Cart\Item( $wte_cart, $cart_items );
		$wte_cart->setItems( array( $trip_id => $item ) );
		$wte_cart->add( $item );
		$wte_cart->set_booking_ref( $post_id );

		// Process booking with optimized flow/
		$booking_process = new BookingProcess( $request, $wte_cart );

		// Only process order items if cart has items and post exists.
		if ( ! empty( $wte_cart->getItems( true ) ) && ! empty( $post_id ) && get_post( $post_id ) ) {
			$booking_post = BookingModel::make( $post_id );
			$booking_post->set_order_items( $wte_cart->getItems( true ) );
			$booking_process->set_order_items( $booking_post );
		}

		// Process customer with optimized logic.
		$customer_email = sanitize_email( $request->get_param( 'billing' )['email'] ?? '' );
		
		// Only process customer if email is not empty.
		if ( ! empty( $customer_email ) && is_email( $customer_email ) ) {
			$customer_id = Customer::is_exists( $customer_email );
		
			if ( ! $customer_id ) {
				$customer_id = Customer::create_post(
					array(
						'post_status' => 'publish',
						'post_type'   => 'customer',
						'post_title'  => $customer_email,
					)
				);
			} else {
				$customer_model = new Customer( $customer_id );
				$customer_model->update_customer_bookings( $post_id );
				$customer_model->save();
				$customer_model->update_customer_meta( $post_id );
		
				// Update user meta if user exists.
				$billing_info = get_post_meta( $post_id, 'wptravelengine_billing_details', true );
				$user         = get_user_by( 'email', $billing_info['email'] ?? $customer_email );
		
				if ( $user instanceof \WP_User ) {
					update_user_meta( $user->ID, 'wp_travel_engine_user_bookings', array( $post_id ) );
				}
		
				$customer_model->update_customer_meta( $post_id );
			}
		}

		$order_items = $booking->get_order_items();

		$cart_info = $booking->get_cart_info();
		if ( $update && $booking->get_meta( '_initial_cart_info' ) === '' ) {
			$booking->set_meta( '_initial_cart_info', wp_json_encode( $cart_info ) );
		}

		if ( $update && $booking->get_meta( '_initial_order_items' ) === '' ) {
			$booking->set_meta( '_initial_order_items', wp_json_encode( $order_items ) );
		}

		// Set travellers.
		if ( $travellers = $request->get_param( 'travellers' ) ) {
			$data = array();
			foreach ( array_keys( $travellers ) as $entity ) {
				foreach ( $travellers[ $entity ] as $index => $value ) {
					$data[ $index ][ $entity ] = $value;
				}
			}

			// Sanitize traveler data.
			$sanitized_data = array_map(
				function ( $traveler ) use ( $form_validator ) {
					$sanitized_traveler = array();

					foreach ( $traveler as $field => $value ) {
						if ( is_array( $value ) ) {
							$sanitized_traveler[ $field ] = $value;
							continue;
						}
						switch ( $field ) {
							case 'email':
								$sanitized_traveler[ $field ] = sanitize_email( $value );
								break;

							case 'phone':
								$sanitized_traveler[ $field ] = $form_validator->sanitize_phone( $value );
								break;

							case 'country':
								$sanitized_traveler[ $field ] = $form_validator->sanitize_country( $value );
								break;

							default:
								$sanitized_traveler[ $field ] = sanitize_text_field( $value );
						}
					}

					return $sanitized_traveler;
				},
				$data
			);

			$booking->set_traveller_details( $sanitized_data );
		}

		// Set Emergency Contacts.
		if ( $emergency_contacts = $request->get_param( 'emergency_contacts' ) ) {
			$data = array();
			foreach ( array_keys( $emergency_contacts ) as $entity ) {
				foreach ( $emergency_contacts[ $entity ] as $index => $value ) {
					$data[ $index ][ $entity ] = $value;
				}
			}

			// Sanitize emergency contact data.
			$sanitized_data = array_map(
				function ( $emergency_contact ) use ( $form_validator ) {
					$sanitized_emergency_contact = array();

					foreach ( $emergency_contact as $field => $value ) {
						if ( is_array( $value ) ) {
							$sanitized_emergency_contact[ $field ] = $value;
							continue;
						}
						switch ( $field ) {
							case 'email':
								$sanitized_emergency_contact[ $field ] = sanitize_email( $value );
								break;

							case 'phone':
								$sanitized_emergency_contact[ $field ] = $form_validator->sanitize_phone( $value );
								break;

							case 'country':
								$sanitized_emergency_contact[ $field ] = $form_validator->sanitize_country( $value );
								break;

							default:
								$sanitized_emergency_contact[ $field ] = sanitize_text_field( $value );
						}
					}

					return $sanitized_emergency_contact;
				},
				$data
			);

			$booking->set_emergency_contact_details( $sanitized_data );
		}

		if ( $billing_details = $request->get_param( 'billing' ) ) {
			$billing_email = $billing_details[ 'email' ] ?? '';
			
			// Only process customer if email is not empty
			if ( ! empty( $billing_email ) ) {
				$customer_id    = Customer::is_exists( $billing_email );
				$customer_model = $customer_id
					? new Customer( $customer_id )
					: Customer::create_post(
						array(
							'post_status' => 'publish',
							'post_type'   => 'customer',
							'post_title'  => $billing_email,
						)
					);

				$customer_model->maybe_register_as_user();
				do_action( 'wptravelengine_after_customer_created', $customer_model->ID );

				$customer_model->update_customer_bookings( $post_id );
				$customer_model->save();
			}
			$booking->set_meta( 'wptravelengine_billing_details', $billing_details );

			// Sanitize billing details
			$sanitized_billing = array();
			foreach ( $billing_details as $field => $value ) {
				if ( is_array( $value ) ) {
					$sanitized_billing[ $field ] = array_map( 'sanitize_text_field', $value );
					continue;
				}
				if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
					$sanitized_billing[ $field ] = basename( $value );
					continue;
				}
				switch ( $field ) {
					case 'email':
						$sanitized_billing[ $field ] = sanitize_email( $value );
						break;

					case 'country':
						$sanitized_billing[ $field ] = $form_validator->sanitize_country( $value );
						break;

					case 'phone':
						$sanitized_billing[ $field ] = $form_validator->sanitize_phone( $value );
						break;
					default:
						$sanitized_billing[ $field ] = sanitize_text_field( $value );
				}
			}
			$booking->set_meta( 'wptravelengine_billing_details', $sanitized_billing );
			$booking->set_meta( 'billing_info', $sanitized_billing );
		}

		if ( is_numeric( $paid_amount = $request->get_param( 'paid_amount' ) ) ) {
			$booking->set_meta( 'paid_amount', (float) $paid_amount );
		}

		if ( is_numeric( $due_amount = $request->get_param( 'due_amount' ) ) ) {
			$booking->set_meta( 'due_amount', (float) $due_amount );
		}

		// Set Additional Note.
		if ( $additional_note = $request->get_param( 'additional_details' ) ) {
			$booking->set_additional_details( sanitize_text_field( $additional_note ) );
		}

		// Set Admin Notes.
		if ( $admin_notes = $request->get_param( 'admin_notes' ) ) {
			$booking->set_notes( sanitize_text_field( $admin_notes ) );
		}

		if ( $trip_info = $request->get_param( 'order_trip' ) ) {
			// Sanitize trip info
			$sanitized_trip_info = array(
				'id'                  => absint( $trip_info[ 'id' ] ),
				'start_date'          => sanitize_text_field( $trip_info[ 'start_date' ] ),
				'end_date'            => sanitize_text_field( $trip_info[ 'end_date' ] ),
				'trip_code'           => sanitize_text_field( $trip_info[ 'trip_code' ] ),
				'number_of_travelers' => absint( $trip_info[ 'number_of_travelers' ] ),
				'package_name'        => sanitize_text_field( $trip_info[ 'package_name' ] ),
			);

			// Validate dates

			$start_date = DateTime::createFromFormat( 'Y-m-d H:i', $sanitized_trip_info[ 'start_date' ] );
			$end_date   = DateTime::createFromFormat( 'Y-m-d H:i', $sanitized_trip_info[ 'end_date' ] );

			if ( ! $start_date ) {
				// Handle invalid date format
				$sanitized_trip_info['start_date'] = current_time( 'Y-m-d\TH:i' );
			} else {
				// Convert to ISO 8601 format with T separator
				$sanitized_trip_info['start_date'] = $start_date->format( 'Y-m-d\TH:i' );
			}
			
			if ( ! $end_date ) {
				// Handle invalid date format
				$sanitized_trip_info['end_date'] = current_time( 'Y-m-d\TH:i' );
			} else {
				// Convert to ISO 8601 format with T separator
				$sanitized_trip_info['end_date'] = $end_date->format( 'Y-m-d\TH:i' );
			}

			$cart_info                                      = $booking->get_cart_info() ?? array();
			$cart_info[ 'items' ][ 0 ][ 'trip_id' ]         = $sanitized_trip_info[ 'id' ];
			$cart_info[ 'items' ][ 0 ][ 'trip_date' ]       = $sanitized_trip_info[ 'start_date' ];
			$cart_info[ 'items' ][ 0 ][ 'trip_time' ]       = $sanitized_trip_info[ 'start_date' ];
			$cart_info[ 'items' ][ 0 ][ 'end_date' ]        = $sanitized_trip_info[ 'end_date' ];
			$cart_info[ 'items' ][ 0 ][ 'travelers_count' ] = $sanitized_trip_info[ 'number_of_travelers' ];
			$cart_info[ 'items' ][ 0 ][ 'trip_package' ]    = $sanitized_trip_info[ 'package_name' ];
		}

		// Remove discounts if they are deleted from the request.
		if ( ! $request->get_param( 'discounts' ) ) {
			unset( $cart_info[ 'totals' ][ 'total_discount' ] );
			unset( $cart_info[ 'deductible_items' ] );
		}

		if ( $deductible_items = $request->get_param( 'discounts' ) ) {
			$items  = ArrayUtility::normalize( $deductible_items, 'label' );
			$_items = array();
			foreach ( $items as $index => $item ) {
				$percentage = '';
				if ( preg_match( '/(\d+)%/', $item[ 'label' ], $matches ) ) {
					$percentage = $matches[ 1 ];
				} else {
					$percentage = '';
				}
				$_items[]                                           = wp_parse_args(
					$item,
					array(
						'name'                     => 'discount' . $index, // coupon
						'order'                    => $index,
						'label'                    => $item[ 'label' ], // Discount
						'description'              => '',
						'adjustment_type'          => 'percentage',
						'apply_to_actual_subtotal' => false,
						'percentage'               => $percentage,
						'value'                    => $item[ 'value' ],
						'_class_name'              => CouponAdjustment::class,
						'type'                     => 'deductible',
					)
				);
				$cart_info[ 'totals' ][ 'total_discount' . $index ] = $item[ 'value' ];
			}
			$cart_info[ 'deductible_items' ] = $_items;
		}

		// Remove fees if they are deleted from the request.
		if ( ! $request->get_param( 'fees' ) ) {
			unset( $cart_info[ 'tax_amount' ] );
			unset( $cart_info[ 'totals' ][ 'total_fee' ] );
			unset( $cart_info[ 'fees' ] );
		}

		if ( $fees = $request->get_param( 'fees' ) ) {
			$items  = ArrayUtility::normalize( $fees, 'label' );
			$_items = array();
			foreach ( $items as $index => $item ) {
				$percentage = '';
				if ( preg_match( '/(\d+)%/', $item[ 'label' ], $matches ) ) {
					$percentage = $matches[ 1 ];
				} else {
					$percentage = '';
				}
				$_items[]                                      = wp_parse_args(
					$item,
					array(
						'name'                     => 'fee' . $index, // fees
						'order'                    => $index,
						'label'                    => $item[ 'label' ], // Discount
						'description'              => '',
						'adjustment_type'          => 'percentage',
						'apply_to_actual_subtotal' => false,
						'percentage'               => $percentage,
						'value'                    => $item[ 'value' ],
						'_class_name'              => TaxAdjustment::class,
						'type'                     => 'fee',
					)
				);
				$cart_info[ 'totals' ][ 'total_fee' . $index ] = $item[ 'value' ];
			}
			$cart_info[ 'fees' ] = $_items;
		}

		if ( $line_items = $request->get_param( 'line_items' ) ) {
			foreach ( $line_items as $key => $item ) {
				if ( empty( $item ) && isset( $cart_info[ 'items' ][ 0 ][ 'line_items' ][ $key ] ) ) {
					unset( $cart_info[ 'items' ][ 0 ][ 'line_items' ][ $key ] );
				}

				$items  = ArrayUtility::normalize( $item, 'label' );
				$_items = array();
				foreach ( $items as $_item ) {

					$_class_name = apply_filters( 'wptravelengine_custom_line_item_class', $key == 'pricing_category' ? PricingCategory::class : ( $key == 'extra_service' ? ExtraService::class : false ), $key );

					if ( ! is_subclass_of( $_class_name, CartItem::class ) ) {
						continue;
					}
					
					$_items[] = wp_parse_args(
						$_item,
						array(
							'label'       => $_item['label'],
							'quantity'    => $_item['quantity'],
							'price'       => $_item['price'],
							'total'       => $_item['total'],
							'_class_name' => $_class_name,
						)
					);
				}

				$cart_info[ 'items' ][ 0 ][ 'line_items' ][ $key ] = $_items;
			}
		}

		if ( is_numeric( $total = $request->get_param( 'total' ) ) ) {
			$cart_info[ 'total' ]             = (float) $total;
			$cart_info[ 'totals' ][ 'total' ] = (float) $total;
		}

		if ( is_numeric( $subtotal = $request->get_param( 'subtotal' ) ) ) {
			$cart_info[ 'subtotal' ]             = (float) $subtotal;
			$cart_info[ 'totals' ][ 'subtotal' ] = (float) $subtotal;
		}

		if ( is_numeric( $due_amount = $request->get_param( 'due_amount' ) ) ) {
			$booking->set_total_due_amount( (float) $due_amount );
			$cart_info[ 'totals' ][ 'due_total' ] = (float) $due_amount;
		}

		if ( is_numeric( $paid_amount = $request->get_param( 'paid_amount' ) ) ) {
			$cart_info[ 'totals' ][ 'partial_total' ] = (float) $paid_amount;
			$booking->set_total_paid_amount( (float) $paid_amount );
		}

		$booking->set_cart_info( $cart_info );

		// Set Payments.
		if ( $payments = $request->get_param( 'payments' ) ) {
			$items = ArrayUtility::normalize( $payments, 'gateway' );

			$_payments = array();
			foreach ( $items as $payment_data ) {
				try {
					$payment_model = new Payment( (int) $payment_data[ 'id' ] );
				} catch ( \Exception $e ) {
					$payment_model = Payment::create_post(
						array(
							'post_type'   => 'wte-payments',
							'post_status' => 'publish',
							'post_title'  => 'Payment',
						)
					);
				}

				if ( $status = $payment_data[ 'status' ] ?? null ) {
					$payment_model->set_status( sanitize_text_field( $status ) );
				}

				if ( $gateway = $payment_data[ 'gateway' ] ) {
					$payment_model->set_meta( 'payment_gateway', sanitize_text_field( $gateway ) );
					$booking->set_meta( 'wp_travel_engine_booking_payment_gateway', sanitize_text_field( $gateway ) );
				}

				if ( is_numeric( $paid_amount = $payment_data[ 'amount' ] ?? null ) ) {
					$payment_model->set_meta(
						'payment_amount',
						array(
							'value'    => (float) $paid_amount,
							'currency' => sanitize_text_field( $payment_data[ 'currency' ] ?? '' ),
						)
					);
				}

				if ( is_numeric( $due_amount = $request->get_param( 'due_amount' ) ) ) {
					$payment_model->set_meta(
						'payable',
						array(
							'currency' => sanitize_text_field( $payment_data[ 'currency' ] ?? '' ),
							'amount'   => (float) $due_amount,
						)
					);
				}

				if ( $transaction_id = $payment_data[ 'transaction_id' ] ?? null ) {
					$payment_model->set_transaction_id( sanitize_text_field( $transaction_id ) );
				}

				if ( $transaction_date = $payment_data[ 'transaction_date' ] ?? null ) {
					$payment_model->set_transaction_date( sanitize_text_field( $transaction_date ) );
				}

				if ( $gateway_response = $payment_data[ 'gateway_response' ] ?? null ) {
					$payment_model->set_meta( 'gateway_response', sanitize_text_field( $gateway_response ) );
				}

				if ( $payment_model->get_meta( 'booking_id' ) == null ) {
					$payment_model->set_meta( 'booking_id', $booking->ID );
				}

				$payment_model->save();
				$_payments[] = $payment_model->get_id();
			}
			$booking->set_meta( 'payments', $_payments );
		}

		$booking->save();

		if ( ! $update ) {
			do_action( 'wptravelengine.booking.created', $booking->get_data(), $booking );
		} else {
			do_action( 'wptravelengine.booking.updated', $booking->get_data(), $booking );
		}
	}

	/**
	 * @return void
	 * @since 6.4.0
	 */
	public function meta_box_booking() {
		add_meta_box(
			'booking_details_id',
			__( 'Booking Details', 'wp-travel-engine' ),
			array( $this, 'meta_box_booking_callback' ),
			'booking',
			'normal',
			'high'
		);
	}

	/**
	 * @return void
	 * @since 6.4.0
	 */
	public function meta_box_booking_callback() {
		global $post;
		global $current_screen;
		wp_enqueue_script( 'wptravelengine-booking-edit' );

		$action = '';

		if ( 'booking' === $current_screen->id && $current_screen->action == 'add' ) {
			$action = 'create';
		} else if ( ( $_GET[ 'wptravelengine_action' ] ?? '' ) === 'edit' ) {
			$action = 'update';
		}

		$booking = new BookingModel( $post );

		switch ( $action ) {
			case 'update':
			case 'create':
				$this->create( $booking );
				break;
			default:
				$this->view( $booking );
				break;
		}
	}

	/**
	 * Prepares template arguments for Booking Page.
	 *
	 * @param BookingModel $booking
	 * @param string $mode
	 *
	 * @return array
	 * @since 6.4.0
	 */
	protected function get_template_args( BookingModel $booking, string $mode = 'view' ): array {
		$package_name = $booking->get_order_items()[0]['package_name'] ?? '';

		$cart_info = $booking->get_cart_info() ?? array();

		$items = $cart_info['items'] ?? array();

		$items[0] = array_merge(
			$items[0] ?? array(),
			array(
				'package_name' => isset( $package_name ) && $package_name !== ''
					? $package_name
					: ( isset( $items[0]['package_name']) ? $items[0]['package_name'] : '' )
			)
		);
		

		$cart_info['items'] = $items;
		$cart_info          = new CartInfoParser( $cart_info );

		$order_trip = $cart_info->get_item();

		$package_name = $items[0]['trip_package'] ?? $items[0]['package_name'] ?? $order_trip->get_package_name();

		$mode = 'view' === $mode ? 'readonly' : 'edit';

		return array(
			'booking'                        => $booking,
			'cart_info'                      => $cart_info,
			'template_mode'                  => $mode,
			'order_trip_form_fields'         => new OrderTripEditFormFields(
				array(
					'id'                  => $order_trip->get_trip_id(),
					'booked_date'         => $booking->post->post_date ?? '',
					'start_date'          => $order_trip->get_trip_date(),
					'end_date'            => $order_trip->get_end_date(),
					'trip_code'           => $order_trip->get_trip_code(),
					'number_of_travelers' => $order_trip->travelers_count(),
					'package_id'          => $order_trip->get_trip_package_id(),
					'package_name'        => $package_name,
				),
				$mode
			),
			'travellers_form_fields'         => array_map(
				function ( array $traveller, $index ) use ( $mode, $booking ) {
					$traveller[ 'index' ] = $index;

					return new TravellerEditFormFields( $traveller, $mode, $booking );
				},
				$booking->get_travelers(),
				array_keys( $booking->get_travelers() )
			),
			'emergency_contacts_form_fields' => array_map(
				function ( array $emergency_contact, $index ) use ( $mode, $booking ) {
					$emergency_contact[ 'index' ] = $index;

					return new EmergencyEditFormFields( $emergency_contact, $mode, $booking );
				},
				$booking->get_emergency_contacts(),
				array_keys( $booking->get_emergency_contacts() )
			),
			'billing_edit_form_fields'       => new BillingEditFormFields( $booking->get_billing_info(), $mode ),
			'payments_edit_form_fields'      => array_map(
				function ( $payment ) use ( $mode ) {
					$gateway_response = $payment->get_gateway_response();
					$response         = '';
					if ( ! empty( $gateway_response ) ) :
						if ( is_array( $gateway_response ) || is_object( $gateway_response ) ) {
							$response = wp_json_encode( $gateway_response, JSON_PRETTY_PRINT );
						} else {
							$response = $gateway_response;
						}
					endif;

					$payable = $payment->get_meta( 'payable' );

					return new PaymentEditFormFields(
						apply_filters(
							'wptravelengine_payment_edit_form_fields',
							array(
								'id'               => $payment->get_id(),
								'status'           => $payment->get_payment_status(),
								'gateway'          => $payment->get_payment_gateway(),
								'amount'           => $payment->get_amount(),
								'currency'         => $payable[ 'currency' ] ?? 'USD',
								'transaction_id'   => $payment->get_transaction_id(),
								'gateway_response' => $response,
							),
							$payment
						),
						$mode
					);
				},
				$booking->get_payments()
			),
			'pricing_arguments'              => array(
				'currency_code' => $cart_info->get_currency(),
			),
		);
	}

	/**
	 *
	 *
	 * @return void
	 * @since 6.4.0
	 */
	protected function create( BookingModel $booking ) {
		wptravelengine_get_admin_template( 'booking/create.php', $this->get_template_args( $booking, 'edit' ) );
	}

	/**
	 * @param BookingModel $booking
	 *
	 * @return void
	 * @since 6.4.0
	 */
	protected function view( BookingModel $booking ) {
		wptravelengine_get_admin_template( 'booking/index.php', $this->get_template_args( $booking ) );
	}

	/**
	 * Return query after filtering bookings.
	 *
	 * @param object $query Query.
	 *
	 * @modified_since 6.4.0 Modified the query for the selected trip name and date range filter option.
	 *
	 * @return object $query
	 */
	public function filter_bookings( $query ) {
		// Modify the query only if it is admin and main query.
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $query;
		}
		$current_screen = get_current_screen();
		$trip_id        = 'all';
		$booking_status = 'all';
		if ( isset( $_REQUEST[ 'trip_id' ] ) ) {
			$trip_id = sanitize_text_field( wp_unslash( $_REQUEST[ 'trip_id' ] ) );
		}
		if ( isset( $_REQUEST[ 'booking_status' ] ) ) {
			$booking_status = sanitize_text_field( wp_unslash( $_REQUEST[ 'booking_status' ] ) );
		}
		$date_range = isset( $_REQUEST[ 'wte_booking_range' ] ) ? sanitize_text_field( $_REQUEST[ 'wte_booking_range' ] ) : '';
		$dates      = explode( ' to ', $date_range );

		// Store the dates in separate variables.
		$start_date = isset( $dates[ 0 ] ) ? $dates[ 0 ] : '';
		$end_date   = isset( $dates[ 1 ] ) ? $dates[ 1 ] : '';

		// Modify the query for the targeted screen and filter option.
		if ( ( 'edit-booking' !== $current_screen->id ) || ( 'all' === $booking_status && 'all' === $trip_id && empty( $date_range ) ) ) {
			return $query;
		}
		$filter_ids = wptravelengine_get_booking_ids( (int) $trip_id );
		$filter_ids = empty( $filter_ids ) ? array( 0 ) : $filter_ids;

		// Add query for selected booking status.
		if ( 'all' !== $booking_status ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => 'wp_travel_engine_booking_status',
						'compare' => '=',
						'value'   => $booking_status,
						'type'    => 'string',
					),
				)
			);
		}

		// Add query for selected trip ids.
		if ( 'all' !== $trip_id ) {
			$query->set(
				'post__in',
				$filter_ids
			);
		}
		// Add query for selected date range.
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$query->set(
				'date_query',
				array(
					array(
						'after'     => $start_date,
						'before'    => $end_date,
						'inclusive' => true,
					),
				)
			);
		} else if ( ! empty( $start_date ) ) {
			$get_specific_date = explode( '-', $start_date );
			$query->set(
				'date_query',
				array(
					array(
						'year'  => $get_specific_date[ 0 ],
						'month' => $get_specific_date[ 1 ],
						'day'   => $get_specific_date[ 2 ],
					),
				)
			);
		}

		return $query;
	}
}
