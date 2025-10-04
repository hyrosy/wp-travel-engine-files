<?php
/**
 * Booking Details Metabox Content.
 */

$date_picker_config = array(
	'enableTime' => true,
	'dateFormat' => 'Y-m-d H:i',
);
?>
<div id="wptravelengine-booking-details">
	<?php wptravelengine_get_admin_template( 'booking/partials/header.php' ); ?>
	<div class="wpte-form-container">
		<?php wptravelengine_get_admin_template( 'booking/partials/tab-title.php' ); ?>
		<div class="wpte-booking-details-layout">

			<!-- .wpte-booking-fields-area -->
			<div class="wpte-booking-fields-area">
				<?php wptravelengine_get_admin_template( 'booking/partials/booking-info.php' ); ?>
				<div class="wpte-booking-collapsible-content">
					<?php
					wptravelengine_get_admin_template( 'booking/partials/traveller-info.php' );
					wptravelengine_get_admin_template( 'booking/partials/emergency-contact.php' );
					wptravelengine_get_admin_template( 'booking/partials/payment-details.php' );
					wptravelengine_get_admin_template( 'booking/partials/billing-details.php' );
					wptravelengine_get_admin_template( 'booking/partials/additional-field.php' );
					wptravelengine_get_admin_template( 'booking/partials/admin-notes.php' );
					?>
				</div>
				<!-- end booking-detail-fields -->
			</div>

			<div class="wpte-booking-summary-area">
				<?php wptravelengine_get_admin_template( 'booking/partials/edit/booking-summary.php' ); ?>
				<?php wptravelengine_get_admin_template( 'booking/partials/edit/booking-status.php' ); ?>
			</div>
			<!-- Create nonce for booking creation -->
			<input type="hidden" name="wptravelengine_new_booking_nonce" value="<?php echo wp_create_nonce( 'wptravelengine_new_booking' ); ?>">
		</div>
	</div>
</div>
