<?php

/**
 * Upcoming Tours Header.
 *
 * @since 6.4.3
 *
 * @var array $dates
 */
?>
<header class="wpte-upcoming-tours-header">
	<h1 class="wpte-upcoming-tours-header-title"><?php _e( 'Upcoming Tours', 'wp-travel-engine' ); ?></h1>
	<div class="wpte-dates-filter" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wte_filter_upcoming_tours' ) ); ?>">
		<div class="wpte-filter-options">
			<button class="wpte-filter-btn active" data-date="<?php echo esc_attr( 'all' ); ?>"><?php _e( 'All', 'wp-travel-engine' ); ?></button>
			<button class="wpte-filter-btn" data-date="<?php echo esc_attr( wp_json_encode( $dates['today'] ) ); ?>"><?php _e( 'Today', 'wp-travel-engine' ); ?></button>
			<button class="wpte-filter-btn" data-date="<?php echo esc_attr( wp_json_encode( $dates['this_week'] ) ); ?>"><?php _e( 'This Week', 'wp-travel-engine' ); ?></button>
			<button class="wpte-filter-btn" data-date="<?php echo esc_attr( wp_json_encode( $dates['next_15_days'] ) ); ?>"><?php _e( 'Next 15 Days', 'wp-travel-engine' ); ?></button>
			<button class="wpte-filter-btn" data-date="<?php echo esc_attr( wp_json_encode( $dates['this_month'] ) ); ?>"><?php _e( 'This Month', 'wp-travel-engine' ); ?></button>
		</div>
		<div class="wpte-date-range">
			<span class="wpte-date-icon">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M17.5 8.33333H2.5M13.3333 1.66667V5M6.66667 1.66667V5M6.5 18.3333H13.5C14.9001 18.3333 15.6002 18.3333 16.135 18.0609C16.6054 17.8212 16.9878 17.4387 17.2275 16.9683C17.5 16.4335 17.5 15.7335 17.5 14.3333V7.33333C17.5 5.9332 17.5 5.23314 17.2275 4.69836C16.9878 4.22795 16.6054 3.8455 16.135 3.60582C15.6002 3.33333 14.9001 3.33333 13.5 3.33333H6.5C5.09987 3.33333 4.3998 3.33333 3.86502 3.60582C3.39462 3.8455 3.01217 4.22795 2.77248 4.69836C2.5 5.23314 2.5 5.9332 2.5 7.33333V14.3333C2.5 15.7335 2.5 16.4335 2.77248 16.9683C3.01217 17.4387 3.39462 17.8212 3.86502 18.0609C4.3998 18.3333 5.09987 18.3333 6.5 18.3333Z" stroke="#859094" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</span>
			<input id="wpte-custom-filter-date" class="wte-flatpickr" placeholder="Select Date Range">
		</div>
	</div>
</header>