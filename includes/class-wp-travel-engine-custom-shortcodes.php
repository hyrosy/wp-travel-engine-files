<?php

use WPTravelEngine\Modules\TripSearch;

/**
 * Class for trip custom shortcodes.
 */
class WP_Travel_Engine_Custom_Shortcodes {

	public function __construct() {

//		add_shortcode( 'wte_trip', array( $this, 'wte_trip_shortcodes_callback' ) );
		add_shortcode( 'wte_trip_map', array( __CLASS__, 'wte_show_trip_map_shortcodes_callback' ) );
		add_shortcode( 'wte_trip_tax', array( $this, 'wte_trip_tax_shortcodes_callback' ) );
		add_shortcode( 'wte_video_gallery', array( $this, 'wte_video_gallery_output_callback' ) );
		add_shortcode( 'WP_TRAVEL_ENGINE_WISHLIST', array( $this, 'wishlist_shortcode' ) );

		add_action( 'wte_trip_content_action', array( $this, 'wte_trip_content' ) );
		add_filter( 'body_class', array( $this, 'wte_custom_shortcode_class' ) );

		/**
		 * Checkout Shortcodes.
		 *
		 * @since 2.2.6
		 * Shortcodes for new checkout process.
		 */
		$shortcodes = array(
			'wp_travel_engine_cart'     => __CLASS__ . '::cart',
			'wp_travel_engine_checkout' => __CLASS__ . '::checkout',
			// 'wp_travel_engine_dashboard' => __CLASS__ . '::user_account',
		);

		$shortcode = apply_filters( 'wp_travel_engine_cart_shortcodes', $shortcodes );

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
		
		add_action( 'wp', function() {
			global $post;

			if ( ! $post instanceof \WP_Post || ! is_singular() ) {
				return;
			}

			if ( has_shortcode( $post->post_content, 'wte_trip' ) || has_shortcode( $post->post_content, 'wte_trip_tax' ) ) {
				TripSearch::enqueue_assets();
			}

			if ( has_shortcode( $post->post_content, 'WP_TRAVEL_ENGINE_WISHLIST' ) ) {
				TripSearch::enqueue_scripts();
				\WPTravelEngine\Assets::instance()->enqueue_style( 'trip-wishlist' )->enqueue_script( 'trip-wishlist' );
			}

		} );
	}

	/*
	** @since 5.5.7
	*/
	public function wishlist_shortcode( $attr ) {
		$attr = shortcode_atts(
			array(),
			$attr,
			'wte_wishlist'
		);

		ob_start();
		$current_user_wishlist = wptravelengine_user_wishlists();
		?>
		<div>
			<div class="wte-category-outer-wrap wte-user-wishlists">
				<?php
				if ( empty( $current_user_wishlist ) || ! is_array( $current_user_wishlist ) ) {
					$button_txt = __( 'Explore Trips', 'wp-travel-engine' );
					$site_url      = get_site_url();
					$trip_page_url = $site_url . '/trip';
					?>
					<div class="wpte_empty-items-box" style="text-align: center;">
						<div class="wpte_empty-items-img"></div>
						<h3 class="wpte_empty-items-title"><?php esc_html_e( 'Ohhh... Your Wishlist is Empty', 'wp-travel-engine' ); ?></h3>
						<p><?php esc_html_e( "But it doesn't have to be.", 'wp-travel-engine' ); ?></p>
						<?php printf( '<a href="%s" aria-label="Got back to all trips" class="wpte-button">%s</a>', esc_url( $trip_page_url ), esc_attr( $button_txt ), 'wp-travel-engine' ); ?>
					</div>
					<?php
				} else {
					?>
					<p class="wte-wishlist-message" data-wptravelengine-wishlist-empty-notice></p>
					<div class="wpte_empty-items-box" data-wptravelengine-wishlist-empty>
					</div>
					<div class="wte-category-outer-wrap" data-wptravelengine-wishlist-list>
						<?php
						$args = array(
							'post_type'   => WP_TRAVEL_ENGINE_POST_TYPE,
							'post_status' => 'publish',
							'post__in'    => $current_user_wishlist,
							'orderby'     => 'post__in',
							'paged'       => get_query_var( 'paged' ),
						);
						?>
						<div class="wte-user-wishlist-toolbar">
							<span class="wte-user-wishlist-count" data-wptravelengine-wishlist-count>
								<?php printf( wp_kses_post( _n( '<strong>%d</strong> item in wishlist', '<strong>%d</strong> items in wishlist', count( $current_user_wishlist ), 'wp-travel-engine' ) ), count( $current_user_wishlist ) ); ?>
							</span>
							<button aria-label="Remove All Items from Wishlist"
									class="wishlist-toggle wte-wishlist-remove-all" data-wptravelengine-wishlist-remove data-product="all">
								<?php esc_html_e( 'Remove All', 'wp-travel-engine' ); ?>
							</button>
						</div>
						<?php
						$query = new WP_Query( $args );
						if ( $query->have_posts() ) :
							?>
							<div class="category-main-wrap wte-col-3 category-grid" data-wptravelengine-wishlists>
								<?php
								$user_wishlists = wptravelengine_user_wishlists();
								while ( $query->have_posts() ) :
									$query->the_post();
									$details                   = wte_get_trip_details( get_the_ID() );
									$details['user_wishlists'] = $user_wishlists;
									wptravelengine_get_template( 'content-grid.php', $details );
								endwhile;
								wp_reset_postdata();
								?>
							</div>
						<?php
						endif;
						?>
					</div>
					<?php
				}
				?>
			</div>
			<div class="trip-pagination wishlist" data-current-page="<?php echo esc_attr( get_query_var( 'paged' ) ?: 1 ); ?>" data-max-page="<?php echo esc_attr( $query->max_num_pages ?? 0 ); ?>" data-wptravelengine-wishlist-pagination>
				<?php
				if ( isset( $query ) ) {
					$GLOBALS[ 'wp_query' ] = $query;
					the_posts_pagination(
						array(
							'prev_text'          => esc_html__( 'Previous', 'wp-travel-engine' ),
							'next_text'          => esc_html__( 'Next', 'wp-travel-engine' ),
							'before_page_number' => '<span class="meta-nav screen-reader-text">' . esc_html__( 'Page', 'wp-travel-engine' ) . ' </span>',
						)
					);
					wp_reset_query();
				}
				?>
			</div>
		</div>
		<?php
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Video gallery shortcode output
	 */
	public function wte_video_gallery_output_callback( $atts ) {
		ob_start();
		global $post;
		$post_id = is_object( $post ) && isset( $post->ID ) ? $post->ID : false;

		$atts = shortcode_atts(
			array(
				'title'   => false,
				'trip_id' => $post_id,
				'type'    => 'popup',
				'label'   => esc_html__( 'Video Gallery', 'wp-travel-engine' ),
			),
			$atts,
			'wte_video_gallery'
		);

		// Bail if no trip ID found.
		if ( ! $atts[ 'trip_id' ] ) {
			esc_html_e( 'No Trip ID supplied. Gallery Unavailable.', 'wp-travel-engine' );
			$output = ob_get_clean();

			return $output;
		}

		$video_gallery = get_post_meta( $atts[ 'trip_id' ], 'wpte_vid_gallery', true );
		if ( ! empty( $video_gallery ) ) {
			if ( 'popup' === $atts[ 'type' ] ) {
				wte_get_template(
					'single-trip/gallery-video-popup.php',
					array(
						'label'   => $atts[ 'label' ],
						'title'   => $atts[ 'title' ],
						'trip_id' => $atts[ 'trip_id' ],
						'gallery' => $video_gallery,
					)
				);
			} else if ( 'slider' === $atts[ 'type' ] ) {
				wte_get_template(
					'single-trip/gallery-video-slider.php',
					array(
						'label'   => $atts[ 'label' ],
						'title'   => $atts[ 'title' ],
						'trip_id' => $atts[ 'trip_id' ],
						'gallery' => $video_gallery,
					)
				);
			}
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Cart page shortcode.
	 *
	 * @return string
	 */
	public static function cart() {
		return self::shortcode_wrapper( array( 'WTE_Cart', 'output' ) );
	}

	/**
	 * Checkout page shortcode.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function checkout( $atts ) {
		return false;
	}

	/**
	 * Add user Account shortcode.
	 *
	 * @return string
	 */
	public static function user_account() {
		return self::shortcode_wrapper( array( 'Wp_Travel_Engine_User_Account', 'output' ) );
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array $atts Attributes. Default to empty array.
	 * @param array $wrapper Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'wp-travel',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper[ 'before' ] ) ? '<div class="' . esc_attr( $wrapper[ 'class' ] ) . '">' : wp_kses_post( $wrapper[ 'before' ] );
		call_user_func( $function, $atts );
		echo empty( $wrapper[ 'after' ] ) ? '</div>' : wp_kses_post( $wrapper[ 'after' ] );

		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}

	function wte_custom_shortcode_class( $classes ) {
		global $post;
		if ( is_object( $post ) ) {
			if ( has_shortcode( $post->post_content, 'wte_trip_tax' ) || has_shortcode( $post->post_content, 'wte_trip' ) ) {
				$classes[] = 'archive';
			}
		}

		return $classes;
	}

	// function to display trips
	public static function wte_show_trip_map_shortcodes_callback( $attr ) {
		$attr = shortcode_atts(
			array(
				'id'   => '',
				'show' => 'both',
			),
			$attr,
			'wte_trip_map'
		);

		if ( empty( $attr[ 'id' ] ) ) {
			global $post;
			if ( $post && WP_TRAVEL_ENGINE_POST_TYPE === get_post_type( $post->ID ) ) {
				$attr[ 'id' ] = $post->ID;
			}
		}

		if ( empty( $attr[ 'id' ] ) ) {
			return '';
		}

		$wp_travel_engine_setting = get_post_meta( $attr[ 'id' ], 'wp_travel_engine_setting', true );

		$global_settings = wptravelengine_settings();
		$do_lazy_loading = wptravelengine_toggled( $global_settings->get( 'enable_lazy_loading', 'no' ) );
		$map_lazy_loading = wptravelengine_toggled( $global_settings->get( 'enable_map_lazy_loading', 'no' ) );

		ob_start();

		if ( in_array( $attr[ 'show' ], array(
				'both',
				'iframe',
				'iframe|image',
			) ) && ! empty( $wp_travel_engine_setting[ 'map' ][ 'iframe' ] ) ) {
			?>
				<div class="trip-map iframe">
					<?php if ( ! is_singular( WP_TRAVEL_ENGINE_POST_TYPE ) || ! $do_lazy_loading || ! $map_lazy_loading ) :
						echo wptravelengine_esc_iframe( $wp_travel_engine_setting[ 'map' ][ 'iframe' ] );
					endif;
					?>
				</div>
			<?php
			if ( 'iframe|image' === $attr[ 'show' ] ) {
				$attr[ 'show' ] = 'none';
			}
		}

		if ( in_array( $attr[ 'show' ], array(
				'both',
				'image',
				'iframe|image',
			) ) && ! empty( $wp_travel_engine_setting[ 'map' ][ 'image_url' ] ) ) {
			$img_id = $wp_travel_engine_setting[ 'map' ][ 'image_url' ];
			$src    = wp_get_attachment_image_src( $img_id, 'full' );
			?>
			<div class="trip-map image">
				<img
					alt="<?php echo esc_attr( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ?? get_the_title( $img_id ) ); ?>"
					width="910" height="490" src="<?php echo esc_url( $src[ 0 ] ); ?>">
			</div>
			<?php
		}

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	// function to generate shortcode
	function wte_trip_shortcodes_callback( $attr ) {
		$attr = shortcode_atts(
			array(
				'ids'         => '',
				'layout'      => 'grid',
				'postsnumber' => get_option( 'posts_per_page' ),
			),
			$attr,
			'wte_trip'
		);

		if ( ! in_array( $attr[ 'layout' ], array( 'grid', 'list' ) ) ) {
			return '<h1>' . sprintf( __( 'Layout not found: %s', 'wp-travel-engine' ), $attr[ 'layout' ] ) . '</h1>';
		}

		if ( ! empty( $attr[ 'ids' ] ) ) {
			$ids           = array();
			$ids           = explode( ',', $attr[ 'ids' ] );
			$attr[ 'ids' ] = $ids;
		}

		ob_start();

		do_action( 'wte_trip_content_action', $attr );

		$output = ob_get_contents();
		ob_end_clean();

		if ( $output != '' ) {
			return $output;
		}
	}

	// function to generate shortcode
	function wte_trip_tax_shortcodes_callback( $attr ) {
		$attr = shortcode_atts(
			array(
				'activities'  => '',
				'destination' => '',
				'trip_types'  => '',	
				'layout'      => 'grid',
				'postsnumber' => get_option( 'posts_per_page' ),
			),
			$attr,
			'wte_trip_tax'
		);

		if ( ! in_array( $attr[ 'layout' ], array( 'grid', 'list' ) ) ) {
			return '<h1>' . sprintf( __( 'Layout not found: %s', 'wp-travel-engine' ), $attr[ 'layout' ] ) . '</h1>';
		}

		if ( ! empty( $attr[ 'activities' ] ) ) {
			$activities           = array();
			$activities           = explode( ',', $attr[ 'activities' ] );
			$attr[ 'activities' ] = $activities;
		}

		if ( ! empty( $attr[ 'destination' ] ) ) {
			$destination           = array();
			$destination           = explode( ',', $attr[ 'destination' ] );
			$attr[ 'destination' ] = $destination;
		}

		if ( ! empty( $attr[ 'trip_types' ] ) ) {
			$trip_types           = array();
			$trip_types           = explode( ',', $attr[ 'trip_types' ] );
			$attr[ 'trip_types' ] = $trip_types;
		}

		ob_start();

		do_action( 'wte_trip_content_action', $attr );

		$output = ob_get_contents();
		ob_end_clean();

		if ( $output != '' ) {
			return $output;
		}
	}

	function wte_trip_content( $atts ) {
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		$args = array(
			'post_type'      => 'trip',
			'post_status'    => 'publish',
			'posts_per_page' => $atts[ 'postsnumber' ] ?? get_option( 'posts_per_page', 10 ),
		);

		$args['offset'] = ( $paged - 1 ) * $args[ 'posts_per_page' ];

		if ( ! empty( $atts[ 'ids' ] ) ) {
			$args[ 'post__in' ] = $atts[ 'ids' ];
			$args[ 'orderby' ]  = 'post__in';
		}

		if ( ! empty( $atts[ 'activities' ] ) || ! empty( $atts[ 'destination' ] ) || ! empty( $atts[ 'trip_types' ] ) ) {
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

			$args[ 'wpse_search_or_tax_query' ] = true;
			$args[ 'paged' ]                    = $paged;

			$tax_query = array( 'relation' => 'OR' );
			if ( ! empty( $atts[ 'activities' ] ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'activities',
					'field'            => 'term_id',
					'terms'            => $atts[ 'activities' ],
					'include_children' => false,
				);
			}
			if ( ! empty( $atts[ 'destination' ] ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'destination',
					'field'            => 'term_id',
					'terms'            => $atts[ 'destination' ],
					'include_children' => false,
				);
			}
			if ( ! empty( $atts[ 'trip_types' ] ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'trip_types',
					'field'            => 'term_id',
					'terms'            => $atts[ 'trip_types' ],
					'include_children' => false,
				);
			}

			if ( ! empty( $tax_query ) ) {
				$args[ 'tax_query' ] = $tax_query;
			}
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :
			?>
			<div class="wp-travel-engine-archive-repeater-wrap">
				<div class="wte-category-outer-wrap">
					<?php $view_class = 'grid' === $atts[ 'layout' ] ? 'wte-col-3 category-grid' : 'category-list'; ?>
					<div class="category-main-wrap <?php echo esc_attr( $view_class ); ?>">
						<?php
						$user_wishlists = wptravelengine_user_wishlists();
						$template_name	= wptravelengine_get_template_by_view_mode( $atts[ 'layout' ] );

			while ( $query->have_posts() ) :
				$query->the_post();
				$details                   = wte_get_trip_details( get_the_ID() );
				$details['user_wishlists'] = $user_wishlists;
				wptravelengine_get_template( $template_name, $details );
			endwhile;
		wp_reset_postdata();
			echo '</div>';
			?>
			</div>
		<?php
		endif;
	}
}

new WP_Travel_Engine_Custom_Shortcodes();
