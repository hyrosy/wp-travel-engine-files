<?php
/**
 * Trip Results Template.
 */
use WPTravelEngine\Modules\TripSearch;

TripSearch::enqueue_assets();
get_header();

if ( 'builder' === get_post_meta( get_the_ID(), '_elementor_edit_mode', true ) ) :
	?>
		<div class="wp-travel-engine-archive-outer-wrap collapsible-filter-panel">
			<?php if ( 'travel-agency' !== get_option( 'template', '' ) ) : ?>
				<div class="page-header">
					<?php echo apply_filters( 'wte-trip-search-page-title', sprintf( '<h1 class="page-title">%1$s</h1>', get_the_title() ) ); ?>
					<div class="page-content">
						<?php
						the_content();
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php 
else :
    do_action( 'wp_travel_engine_trip_archive_wrap' );
endif;

get_footer();

return;

// TODO: Remove this once above is stable
$active_theme = get_option( 'template', '' );

?>
	<div class="wp-travel-engine-archive-outer-wrap collapsible-filter-panel">
		<?php if ( 'travel-agency' !== $active_theme ) : ?>
			<div class="page-header">
				<?php echo apply_filters( 'wte-trip-search-page-title', sprintf( '<h1 class="page-title">%1$s</h1>', get_the_title() ) ); ?>
				<div class="page-content">
					<?php
					the_content();
					?>
				</div>
			</div>
		<?php endif; ?>
		<?php
			/**
			 * wp_travel_engine_archive_sidebar hook
			 *
			 * @hooked wte_advanced_search_archive_sidebar
			 */
			do_action( 'wp_travel_engine_archive_sidebar' );
		?>
		<div class="wp-travel-engine-archive-repeater-wrap">
			<?php
				// $view_mode = wp_travel_engine_get_archive_view_mode();
				// $orderby   = isset( $_GET['wte_orderby'] ) && ! empty( $_GET['wte_orderby'] ) ? $_GET['wte_orderby'] : '';
				\Wp_Travel_Engine_Archive_Hooks::archive_filters_sub_options();
			?>
				<div class="wte-category-outer-wrap">
					<?php
					$j          = 1;
					$view_mode  = wp_travel_engine_get_archive_view_mode();
					$show_sidebar = wptravelengine_toggled( get_option( 'wptravelengine_show_trip_search_sidebar', 'yes' ) );
					$classes    = apply_filters( 'wte_advanced_search_trip_results_grid_classes', $show_sidebar ? 'wte-col-2 category-grid' : 'wte-col-3 category-grid' );
					$view_class = 'grid' === $view_mode ? $classes : 'category-list';

					echo '<div class="category-main-wrap ' . esc_attr( $view_class ) . '">';
					$query          = new \WP_Query( TripSearch::get_query_args() );

					if( ! $query->have_posts() ){
						echo apply_filters( 'no_result_found_message', __( 'No results found!', 'wp-travel-engine' ) );
					}

					$user_wishlists = wptravelengine_user_wishlists();
					$template_name	= wptravelengine_get_template_by_view_mode( $view_mode );

					while ( $query->have_posts() ) {
						$query->the_post();
						$details                   = wte_get_trip_details( get_the_ID() );
						$details['j']              = $j;
						$details['user_wishlists'] = $user_wishlists;

						wptravelengine_get_template( $template_name, $details );
						$j++;
					}
						wp_reset_postdata();
					echo '</div>';

					$total_pages = $query->max_num_pages;
					$big         = 999999999; // need an unlikely integer

					if ( $total_pages > 1 ) {
						$current_page = max( 1, get_query_var( 'paged' ) );
						echo '<div class="trip-pagination pagination">';
						echo '<div class="nav-links">';
						echo paginate_links(
							array(
								'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
								'format'             => '?paged=%#%',
								'current'            => $current_page,
								'total'              => $total_pages,
								'prev_text'          => __( 'Previous', 'wp-travel-engine' ),
								'next_text'          => __( 'Next', 'wp-travel-engine' ),
								'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'wp-travel-engine' ) . ' </span>',
							)
						); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '</div>';
						echo '</div>';
					}
					?>
				</div>
				<div id="loader" style="display: none">
					<div class="table">
						<div class="table-grid">
							<div class="table-cell">
								<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
							</div>
						</div>
					</div>
				</div>
				<?php
				$nonce = wp_create_nonce( 'wte_show_ajax_result' );
				?>
				<input type="hidden" name="search-nonce" id="search-nonce" value="<?php echo esc_attr( $nonce ); ?>">
		</div>
	</div>
<?php
get_footer();
