<?php
/**
 * Class Coupons.
 *
 * This class handles overall functionality of coupons.
 *
 * @since 5.8.3
 */

namespace WPTravelEngine\Core;

/**
 * WP Travel Engine Coupons Class.
 */
class Coupons {

	/**
	 * Checks available coupons in specific trip.
	 *
	 * @return boolean
	 */
	public static function is_coupon_available() {
		global $wpdb, $wte_cart;

		// Generate a unique cache key based on the trip ID.
		$trip_id   = $wte_cart->get_cart_trip_ids()[ 0 ] ?? '';
		$cache_key = 'wte_coupon_available_' . $trip_id;

		// Try to get the cached result.
		$cached_result = wp_cache_get( $cache_key, 'wptravelengine' );

		// If the result is cached, return it.
		if ( false !== $cached_result ) {
			return $cached_result === 'yes';
		}

		$keys = [
			'wp_travel_engine_coupon_metas',
			'publish',
			'wte-coupon',
			'wp_travel_engine_coupon_usage_count',
			'',
			'',
			$wte_cart->get_cart_trip_ids()[ 0 ] ?? '',
		];

		$sql = "SELECT EXISTS (
                    SELECT 1
                    FROM $wpdb->postmeta pm1
                    JOIN $wpdb->posts po ON pm1.post_id = po.id
                    WHERE pm1.meta_key = %s
                    AND po.post_status = %s
                    AND po.post_type = %s
                    AND (
                        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"coupon_limit_number\";s:', -1), '\"', 2), '\"', -1) > (
                            SELECT COALESCE (
                                (
                                    SELECT pm2.meta_value
                                    FROM $wpdb->postmeta pm2
                                    WHERE pm2.meta_key = %s
                                    AND pm2.post_id = pm1.post_id
                                ),
                                0
                            )
                        )
                        OR SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"coupon_limit_number\";s:', -1), '\"', 2), '\"', -1) = %s
                    )
                    AND (
                        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"coupon_expiry_date\";s:', -1), '\"', 2), '\"', -1) >= CURDATE()
                        OR SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"coupon_expiry_date\";s:', -1), '\"', 2), '\"', -1) = %s
                    )
                    AND SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"coupon_start_date\";s:', -1), '\"', 2), '\"', -1) <= CURDATE()
                    AND (
                        SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"restriction\";a:', -1), ':', 1) = 1
                        OR LOCATE(CONCAT('\"', %s, '\"'), SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(pm1.meta_value, ':\"restricted_trips\";a:', -1), '{', -1), '}', 1)) > 0
                    )
                ) AS data_exists";

		$result = ( $wpdb->get_results( $wpdb->prepare( $sql, $keys ) )[ 0 ]->data_exists == 1 );

		// Cache the result for future use.
		wp_cache_set( $cache_key, $result ? 'yes' : 'no', 'wptravelengine' );

		return $result;
	}
}
