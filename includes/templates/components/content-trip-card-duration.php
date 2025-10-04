<?php

/**
 * Trip Card - Duration Component.
 *
 * @since 5.5.4
 */

$trip_duration_unit   ??= 'days';
$trip_duration        ??= 0;
$trip_duration_nights ??= 0;
$set_duration_type    ??= 'both';
$is_block_layout      ??= false;
$is_featured_widget   ??= false;
$is_booking_detail    ??= false;

global $post;

$duration_label = wptravelengine_get_trip_duration_arr( $trip_instance ?? $post, $set_duration_type );

if ( empty( $duration_label ) ) {
	return;
}

if ( $is_block_layout ) {
?>
	<span class="wpte-trip-meta wpte-trip-duration">
		<i>
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12 6V12L16 14M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</i>
		<span>
			<?php echo esc_html(implode(' - ', $duration_label)); ?>
		</span>
	</span>
<?php
} elseif ( $is_featured_widget ) {
?>
	<span class="category-trip-dur">
		<i>
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12 6V12L16 14M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</i>
		<?php echo esc_html(implode(' - ', $duration_label)); ?>
	</span>
<?php
} elseif ( $is_booking_detail ) {
	echo esc_html( implode( ' - ', $duration_label ) );
} else {
?>
	<span class="category-trip-dur">
		<i>
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12 6V12L16 14M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</i>
		<span>
			<?php echo esc_html(implode(' - ', $duration_label)); ?>
		</span>
	</span>
<?php
}
