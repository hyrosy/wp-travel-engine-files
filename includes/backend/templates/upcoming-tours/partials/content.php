<?php
/**
 * Upcoming Tours Content.
 *
 * @package wp-travel-engine
 * @since 6.4.3
 *
 * @var array $trips
 * @var int $count
 * @var bool $show_more_btn
 * @var bool $show_less_btn
 */
?>
<div class="wpte-upcoming-tours-list" data-items-count="<?php echo esc_attr( $count ); ?>">
	<?php if ( count( $trips ) === 0 ) : ?>
		<h2><?php esc_html_e( 'No upcoming tours found', 'wp-travel-engine' ); ?></h2>
		<?php
		else :
			foreach ( $trips as $key => $trip ) :
				?>
			<!-- Trip card component for upcoming tours -->
			<div class="wpte-trip-card">
				<div class="wpte-trip-date-info">
					<div class="wpte-trip-date">
						<span class="wpte-month"><?php echo esc_html( $trip['datetime']['month'] ); ?></span>
						<span class="wpte-day"><?php echo esc_html( $trip['datetime']['day'] ); ?></span>
					</div>
					<div class="wpte-trip-time-travelers">
						<?php if ( ! empty( $trip['datetime']['time'] ) ) : ?>
							<div class="wpte-trip-time">
								<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M7.99998 4.50001V8.50001L10.6666 9.83334M14.6666 8.50001C14.6666 12.1819 11.6819 15.1667 7.99998 15.1667C4.31808 15.1667 1.33331 12.1819 1.33331 8.50001C1.33331 4.81811 4.31808 1.83334 7.99998 1.83334C11.6819 1.83334 14.6666 4.81811 14.6666 8.50001Z" stroke="#859094" stroke-width="1.336" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
								<span><?php echo esc_html( $trip['datetime']['time'] ); ?></span>
							</div>
						<?php endif; ?>
							<div class="wpte-trip-travelers">
								<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M2 13.8333C3.55719 12.1817 5.67134 11.1667 8 11.1667C10.3287 11.1667 12.4428 12.1817 14 13.8333M11 5.5C11 7.15685 9.65685 8.5 8 8.5C6.34315 8.5 5 7.15685 5 5.5C5 3.84315 6.34315 2.5 8 2.5C9.65685 2.5 11 3.84315 11 5.5Z" stroke="#859094" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
								<span><?php echo esc_html( $trip['travellers'] . ' ' . __( 'Travellers', 'wp-travel-engine' ) ); ?></span>
							</div>
					</div>
				</div>
				<div class="wpte-trip-content">
					<div class="wpte-trip-content-wrapper">
						<div class="wpte-trip-img">
							<?php if ( ! empty( $trip['image'] ) ) : ?>
								<img src="<?php echo esc_url( $trip['image'] ); ?>" alt="">
							<?php endif; ?>
						</div>
						<div class="wpte-trip-details">
							<a href="<?php echo esc_url( $trip['permalink'] ); ?>" target="_blank" class="wpte-trip-title"><?php echo esc_html( $trip['title'] ); ?></a>
						</div>
					</div>
					<div class="wpte-trip-actions">
						<button class="wpte-button wpte-outlined wpte-btn-view-details" data-id="<?php echo esc_attr( $key ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wte_upcoming_tours_details' ) ); ?>"><?php esc_html_e( 'View Details', 'wp-travel-engine' ); ?></button>
					</div>
				</div>
			</div>
				<?php
		endforeach;
			if ( $show_more_btn || $show_less_btn ) :
				?>
			<div class="wpte-load-more-btn-wrapper">
				<?php if ( $show_more_btn ) : ?>
					<button class="wpte-button wpte-load-more-btn"><?php esc_html_e( 'Load More', 'wp-travel-engine' ); ?></button>
					<?php
					endif;
				if ( $show_less_btn ) :
					?>
					<button class="wpte-button wpte-load-less-btn" style="display: flex;"><?php esc_html_e( 'Show Less', 'wp-travel-engine' ); ?></button>
				<?php endif; ?>
			</div>
				<?php
		endif;
	endif;
		?>
</div>
