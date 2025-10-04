<?php
/**
 * Booking Tab.
 *
 * @package wp-travel-engine/includes/templates/account/tab-content/
 */
wp_enqueue_script( "jquery-fancy-box" );
$bookings = $args[ 'bookings' ];

global $wp, $wte_cart;
$settings                      = wptravelengine_settings()->get();
$wp_travel_engine_dashboard_id = isset( $settings[ 'pages' ][ 'wp_travel_engine_dashboard_page' ] ) ? esc_attr( $settings[ 'pages' ][ 'wp_travel_engine_dashboard_page' ] ) : wp_travel_engine_get_page_id( 'my-account' );
?>
	<div class="wpte-lrf-block-wrap">
		<div class="wpte-lrf-block">
			<?php
			if ( ! empty( $bookings ) && isset( $_GET[ 'action' ] ) && wte_clean( wp_unslash( $_GET[ 'action' ] ) ) == 'partial-payment' ) : // phpcs:ignore
				$booking = isset( $_GET[ 'booking_id' ] ) && ! empty( $_GET[ 'booking_id' ] ) ? sanitize_text_field( intval( $_GET[ 'booking_id' ] ) ) : ''; // phpcs:ignore
				wte_get_template(
					'account/remaining-payment.php',
					array(
						'booking' => $booking,
					)
				);
			elseif ( ! empty( $bookings ) && isset( $_GET[ 'action' ] ) && wte_clean( wp_unslash( $_GET[ 'action' ] ) ) == 'booking-details' ) : // phpcs:ignore
				$booking = isset( $_GET[ 'booking_id' ] ) && ! empty( $_GET[ 'booking_id' ] ) ? sanitize_text_field( intval( $_GET[ 'booking_id' ] ) ) : ''; // phpcs:ignore
				wte_get_template(
					'account/booking-details.php',
					array(
						'booking' => $booking,
					)
				);
			elseif ( ! empty( $bookings ) && ! isset( $_GET[ 'action' ] ) ) :
				?>
				<div class="wpte-bookings-tabmenu">
					<?php

					foreach ( $bookings_dashboard_menus as $key => $menu ) :
						?>
						<?php
						if ( $menu[ 'menu_class' ] == 'wpte-active-bookings' ) {
							$booking_menu_active_class = 'active';
						} else {
							$booking_menu_active_class = '';
						}
						?>
						<a class="wpte-booking-menu-tab <?php echo esc_attr( $menu[ 'menu_class' ] ); ?> <?php echo esc_attr( $booking_menu_active_class ); ?>"
						   href="Javascript:void(0);"><?php echo esc_html( $menu[ 'menu_title' ] ); ?></a>
					<?php endforeach; ?>
				</div>
				<div class="wpte-booking-tab-main">
					<?php foreach ( $bookings_dashboard_menus as $key => $menu ) : ?>
						<?php
						if ( $menu[ 'menu_class' ] == 'wpte-active-bookings' ) {
							$booking_menu_active_class = 'active';
						} else {
							$booking_menu_active_class = '';
						}
						?>
						<div
							class="wpte-booking-tab-content wpte-<?php echo esc_attr( $key ); ?>-bookings-content <?php echo esc_attr( $menu[ 'menu_class' ] ); ?> <?php echo esc_attr( $booking_menu_active_class ); ?>">
							<?php
							if ( ! empty( $menu[ 'menu_content_cb' ] ) ) {
								$args[ 'bookings_glance' ]    = $bookings_glance;
								$args[ 'biling_glance_data' ] = $biling_glance_data;
								$args[ 'bookings' ]           = $bookings;
								call_user_func( $menu[ 'menu_content_cb' ], $args );
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php
			else :
				esc_html_e( 'You haven\'t booked any trip yet.', 'wp-travel-engine' );
			endif;
			?>
		</div>
	</div>
<?php
