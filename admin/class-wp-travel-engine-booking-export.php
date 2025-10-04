<?php
/**
 * WP Travel Engine - Export Booking Data
 *
 * @package WP_Travel_Engine
 */

/**
 * WP Travel Engine Booking Export
 *
 * @since 5.7.4
 */
class WP_Travel_Engine_Booking_Export {

	/**
	 * Initialize export procedure.
	 *
	 * @return void
	 */
	public function init() {
		// Early return if not exporting or invalid nonce.
		if ( ! isset( $_REQUEST['booking_export_submit'] ) ||
			! wp_verify_nonce( $_REQUEST['booking_export_nonce'], 'booking_export_nonce_action' ) ) {
			return;
		}

		// Extract and validate date range.
		$date_range                    = sanitize_text_field( $_REQUEST['wte_booking_range'] ?? '' );
		$dates                         = array_pad( explode( ' to ', $date_range ), 2, '' );
		list( $start_date, $end_date ) = $dates;
		// Get filters.
		$booking_status = sanitize_text_field( $_REQUEST['wptravelengine_booking_status'] ?? 'all' );
		$trip_id        = sanitize_text_field( $_REQUEST['wptravelengine_trip_id'] ?? 'all' );
		$filter_ids     = $trip_id == 'all' ? array() : wptravelengine_get_booking_ids( (int) $trip_id );

		// Process export.
		$queries_data = self::export_query( $start_date, $end_date, $booking_status, $filter_ids );
		self::data_export( $queries_data );
		exit;
	}

	/**
	 * Query to retrieve data based on start date and end date.
	 *
	 * @param string $start_date Start Date.
	 * @param string $end_date End Date.
	 * @param string $booking_status Booking Status.
	 * @param array  $filter_ids Booking IDs.
	 *
	 * @since 5.7.4
	 *
	 * @modified_since 6.3.5 - Trip Name and booking status filter added.
	 */
	public function export_query( $start_date, $end_date, $booking_status, $filter_ids ) {
		global $wpdb;

		$meta_keys = array(
			'wp_travel_engine_booking_status',
			'order_trips',
			'billing_info',
			'paid_amount',
			'wp_travel_engine_booking_payment_gateway',
			'_wte_wc_order_id',
			'payments',
			'due_amount'
		);

		$post_status = array( 'publish', 'draft' );

		$sql = "
		    SELECT
		        p.ID AS BookingID,
		        (
		         SELECT pm1.meta_value
		         FROM $wpdb->postmeta pm1
		         WHERE pm1.post_id = p.ID AND pm1.meta_key = %s
		         LIMIT 1
		        ) AS BookingStatus,

		        (
		         SELECT pm2.meta_value
		         FROM $wpdb->postmeta pm2
		         WHERE pm2.post_id = p.ID AND pm2.meta_key = %s
		         LIMIT 1
		        ) AS placeorder,

		        (
		         SELECT pm3.meta_value
		         FROM $wpdb->postmeta pm3
		         WHERE pm3.post_id = p.ID AND pm3.meta_key = %s
		         LIMIT 1
		        ) AS billinginfo,

		        SUM(pm.meta_value) AS TotalCost,
		        SUM(
		            CASE
		             WHEN pm.meta_key = %s THEN pm.meta_value
		             ELSE 0
		            END
		        ) AS TotalPaid,

		        (
		         SELECT pm4.meta_value
		         FROM $wpdb->postmeta pm4
		         WHERE pm4.post_id = p.ID AND pm4.meta_key = %s
		         LIMIT 1
		        ) AS PaymentGateway,

		        (
		         SELECT pm5.meta_value
		         FROM $wpdb->postmeta pm5
		         WHERE pm5.post_id = p.ID AND pm5.meta_key = %s
		         LIMIT 1
		        ) AS wc_id,

		        p.post_date AS BookingDate,

				(
					SELECT pm6.meta_value
					FROM $wpdb->postmeta pm6
					WHERE pm6.post_id = p.ID AND pm6.meta_key = %s
					LIMIT 1
				) AS payments

		    FROM
		        $wpdb->postmeta pm
		    INNER JOIN
		        $wpdb->posts p ON pm.post_id = p.ID

		    WHERE
		        pm.meta_key IN ('" . implode( "', '", $meta_keys ) . "')
		    AND
		        p.post_type = %s
		    AND
		        p.post_status IN ('" . implode( "', '", $post_status ) . "')
		";

		array_pop( $meta_keys );
		$meta_keys[] = 'booking';

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			// Date range: both start and end dates are provided.
			$sql        .= ' AND DATE(p.post_date) >= %s AND DATE(p.post_date) <= %s';
			$meta_keys[] = $start_date;
			$meta_keys[] = $end_date;
		} elseif ( ! empty( $start_date ) ) {
			// Date range: only one date is provided.
			$sql        .= ' AND DATE(p.post_date) = %s';
			$meta_keys[] = $start_date;
		}

		if ( ( 'all' !== $booking_status ) ) {
			$sql        .= " AND 
						EXISTS (
							SELECT 1
							FROM $wpdb->postmeta pm_status
							WHERE pm_status.post_id = p.ID
							AND pm_status.meta_key = 'wp_travel_engine_booking_status'
							AND pm_status.meta_value = %s
						)";
			$meta_keys[] = $booking_status;
		}

		if ( ! empty( $filter_ids ) ) {
			$sql .= ' AND p.ID IN (' . implode( ', ', $filter_ids ) . ')';
		}

		$sql .= ' GROUP BY BookingID, BookingDate, BookingStatus ORDER BY BookingID DESC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $meta_keys ) );
	}

	/**
	 * Importing data to csv format..
	 *
	 * @param array $queries_data Queries Data.
	 *
	 * @since 5.7.4
	 */
	public function data_export( $queries_data ) {
		if ( !is_array( $queries_data ) ) {
			return;
		}
	
		$file = fopen( 'php://output', 'w' );
		if ( !$file ) {
			return;
		}
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="wptravelengine-booking-export.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$file = fopen( 'php://output', 'w' );

		// Get all possible traveler fields and max counts
		$traveler_fields        = array();
		$emergency_fields       = array();
		$max_travelers          = 0;
		$max_emergency_contacts = 0;
		$billing_fields         = array();

		foreach ( $queries_data as $data ) {
			$billing_details = get_post_meta( $data->BookingID, 'wptravelengine_billing_details', true );
			if ( ! empty( $billing_details ) ) {
				$billing_data = maybe_unserialize( $billing_details );
				if ( is_array( $billing_data ) ) {
					foreach ( $billing_data as $field => $value ) {
						if ( ! in_array( $field, $billing_fields ) ) {
							$billing_fields[] = $field;
						}
					}
				}
			}

			$traveler_details = get_post_meta( $data->BookingID, 'wptravelengine_travelers_details', true );
			if ( ! empty( $traveler_details ) ) {
				$travelers = maybe_unserialize( $traveler_details );
				if ( is_array( $travelers ) ) {
					$max_travelers  = max( $max_travelers, count( $travelers ) );
					$first_traveler = reset( $travelers );
					if ( is_array( $first_traveler ) ) {
						foreach ( $first_traveler as $field => $value ) {
							if ( ! in_array( $field, $traveler_fields ) ) {
								$traveler_fields[] = $field;
							}
						}
					}
				}
			}

			// Get max emergency contacts and fields
			$emergency_details = get_post_meta( $data->BookingID, 'wptravelengine_emergency_details', true );
			if ( ! empty( $emergency_details ) ) {
				$emergency_data = maybe_unserialize( $emergency_details );
				if ( is_array( $emergency_data ) ) {
					if ( isset( $emergency_data['fname'] ) || isset( $emergency_data['first_name'] ) ) {
						$max_emergency_contacts = max( $max_emergency_contacts, 1 );
						foreach ( $emergency_data as $field => $value ) {
							if ( ! in_array( $field, $emergency_fields ) ) {
								$emergency_fields[] = $field;
							}
						}
					} else {
						$max_emergency_contacts = max( $max_emergency_contacts, count( $emergency_data ) );
						$first_contact          = reset( $emergency_data );
						if ( is_array( $first_contact ) ) {
							foreach ( $first_contact as $field => $value ) {
								if ( ! in_array( $field, $emergency_fields ) ) {
									$emergency_fields[] = $field;
								}
							}
						}
					}
				}
			}
		}

		// Base headers
		$header = array(
			__( 'Booking ID', 'wp-travel-engine' ),
			__( 'Booking Status', 'wp-travel-engine' ),
			__( 'Trip Name', 'wp-travel-engine' ),
			__( 'Total Cost', 'wp-travel-engine' ),
			__( 'Total Paid', 'wp-travel-engine' ),
			__( 'Payment Gateway', 'wp-travel-engine' ),
			__( 'No. of Travellers', 'wp-travel-engine' ),
			__( 'Booking Date', 'wp-travel-engine' ),
			__( 'Trip Date', 'wp-travel-engine' ),
		);

		// Add billing headers after the basic headers.
		foreach ( $billing_fields as $field ) {
			$field_label = ucwords( str_replace( '_', ' ', $field ) );
			$header[]    = sprintf( __( 'Billing - %s', 'wp-travel-engine' ), $field_label );
		}

		// Add headers for each traveler.
		for ( $i = 1; $i <= $max_travelers; $i++ ) {
			foreach ( $traveler_fields as $field ) {
				$field_label = ucwords( str_replace( '_', ' ', $field ) );
				$header[]    = sprintf( __( 'Traveler %1$d - %2$s', 'wp-travel-engine' ), $i, $field_label );
			}
		}

		// Add headers for each emergency contact.
		for ( $i = 1; $i <= $max_emergency_contacts; $i++ ) {
			foreach ( $emergency_fields as $field ) {
				$field_label = ucwords( str_replace( '_', ' ', $field ) );
				$header[]    = sprintf( __( 'Emergency Contact %1$d - %2$s', 'wp-travel-engine' ), $i, $field_label );
			}
		}

		// Only add these basic fields if billing details are not present.
		$has_billing_data = false;
		foreach ( $queries_data as $data ) {
			$billing_details = get_post_meta( $data->BookingID, 'wptravelengine_billing_details', true );
			if ( ! empty( $billing_details ) ) {
				$has_billing_data = true;
				break;
			}
		}

		if ( ! $has_billing_data ) {
			$basic_fields = array(
				__( 'First Name', 'wp-travel-engine' ),
				__( 'Last Name', 'wp-travel-engine' ),
				__( 'Email', 'wp-travel-engine' ),
				__( 'Address', 'wp-travel-engine' ),
			);
			$header       = array_merge( $header, $basic_fields );
		}

		// Add additional notes header
		$header[] = __( 'Additional Notes', 'wp-travel-engine' );

		// Write headers
		fputcsv( $file, $header );

		// Process each booking
		foreach ( $queries_data as $data ) {
			$tripname         = '';
			$traveler         = '';
			$tripstartingdate = '';
			$paymentgateway   = '';
			$firstname        = '';
			$lastname         = '';
			$email            = '';
			$address          = '';
			$booking_date     = ( new DateTime( $data->BookingDate ) );

			// Process place order data.
			if ( isset( $data->placeorder ) ) {
				$unserializedOrderData = unserialize( $data->placeorder );
				$unserializedOrderData = (object) array_shift($unserializedOrderData);
				$tripname              = isset( $unserializedOrderData->title ) ? $unserializedOrderData->title : '';
				$traveler              = isset( $unserializedOrderData->pax ) ? count($unserializedOrderData->pax) : '';
				$tripstartingdate      = isset( $unserializedOrderData->datetime ) ? $unserializedOrderData->datetime : '';
			}

			// Process billing info.
			if ( isset( $data->billinginfo ) ) {
				$unserializedBillingData = unserialize( $data->billinginfo );
				$firstname               = isset( $unserializedBillingData['fname'] ) ? $unserializedBillingData['fname'] : '';
				$lastname                = isset( $unserializedBillingData['lname'] ) ? $unserializedBillingData['lname'] : '';
				$email                   = isset( $unserializedBillingData['email'] ) ? $unserializedBillingData['email'] : '';
				$address                 = isset( $unserializedBillingData['address'] ) ? $unserializedBillingData['address'] : '';
			}

			// Process payment gateway.
			if ( isset( $data->PaymentGateway ) ) {
				$paymentgateway = $data->PaymentGateway != 'N/A' ? $data->PaymentGateway : '';
			}
			
			// Handle payment gateway retrieval
			if ( empty($data->PaymentGateway) && ! empty( $data->payments ) ) {
				$payment_ids = maybe_unserialize( $data->payments );
				if (is_array($payment_ids) && !empty($payment_ids)) {
					$latest_payment_id = end($payment_ids); // Get the last payment ID
					$payment_gateway = get_post_meta($latest_payment_id, 'payment_gateway', true);
					$paymentgateway = $payment_gateway ?: '';
				} else {
					$paymentgateway = ''; // Default value if no valid payment IDs
				}
			}
			
			if ( isset( $data->wc_id ) && $data->wc_id != '(NULL)' ) {
				$paymentgateway = 'woocommerce';
			}

			// Prepare base row data.
			$row_data = array(
				$data->BookingID,
				$data->BookingStatus,
				$tripname,
				$data->TotalCost,
				$data->TotalPaid,
				$paymentgateway,
				$traveler,
				$booking_date->format( 'Y-m-d' ),
				$tripstartingdate,
			);

			// Add billing data before traveler details.
			$billing_details = get_post_meta( $data->BookingID, 'wptravelengine_billing_details', true );
			if ( empty( $billing_details ) ) {
				$row_data = array_merge(
					$row_data,
					array(
						$firstname,
						$lastname,
						$email,
						$address,
					)
				);
			}
			$billing_data = ! empty( $billing_details ) ? maybe_unserialize( $billing_details ) : array();

			foreach ( $billing_fields as $field ) {
				if ( isset( $billing_data[ $field ] ) ) {
					$value = $billing_data[ $field ];
					if ( is_array( $value ) && ! empty( $value ) ) {
						// Handle array values and ensure they're all strings
						$clean_values = array_map(
							function ( $item ) {
								return is_array( $item ) ? implode( ', ', $item ) : strval( $item );
							},
							$value
						);
						$row_data[]   = implode( ', ', $clean_values );
					} else {
						$row_data[] = $value;
					}
				} else {
					$row_data[] = ''; // Empty string for missing fields
				}
			}

			// Add traveler details.
			$traveler_details = get_post_meta( $data->BookingID, 'wptravelengine_travelers_details', true );
			$travelers        = ! empty( $traveler_details ) ? maybe_unserialize( $traveler_details ) : array();

			for ( $i = 0; $i < $max_travelers; $i++ ) {
				$current_traveler = isset( $travelers[ $i ] ) ? $travelers[ $i ] : array();

				// Process each field for the current traveler.
				foreach ( $traveler_fields as $field ) {
					if ( isset( $current_traveler[ $field ] ) ) {
						$value = $current_traveler[ $field ];
						if ( is_array( $value ) && ! empty( $value ) ) {
							// Handle array values and ensure they're all strings.
							$clean_values = array_map(
								function ( $item ) {
									return is_array( $item ) ? implode( ', ', $item ) : strval( $item );
								},
								$value
							);
							$row_data[]   = implode( ', ', $clean_values );
						} else {
							$row_data[] = $value;
						}
					} else {
						$row_data[] = '';
					}
				}
			}

			// Add emergency contact details.
			$emergency_details  = get_post_meta( $data->BookingID, 'wptravelengine_emergency_details', true );
			$emergency_contacts = array();

			if ( ! empty( $emergency_details ) ) {
				$emergency_data = maybe_unserialize( $emergency_details );
				if ( is_array( $emergency_data ) ) {
					if ( isset( $emergency_data['fname'] ) || isset( $emergency_data['first_name'] ) ) {
						$emergency_contacts = array( $emergency_data );
					} else {
						$emergency_contacts = $emergency_data;
					}
				}
			}

			// Add data for each possible emergency contact.
			for ( $i = 0; $i < $max_emergency_contacts; $i++ ) {
				$current_contact = isset( $emergency_contacts[ $i ] ) ? $emergency_contacts[ $i ] : array();
				foreach ( $emergency_fields as $field ) {
					if ( isset( $current_contact[ $field ] ) ) {
						$value = $current_contact[ $field ];
						if ( is_array( $value ) && ! empty( $value ) ) {
							// Handle array values and ensure they're all strings.
							$clean_values = array_map(
								function ( $item ) {
									return is_array( $item ) ? implode( ', ', $item ) : strval( $item );
								},
								$value
							);
							$row_data[]   = implode( ', ', $clean_values );
						} else {
							$row_data[] = $value;
						}
					} else {
						$row_data[] = ''; // Empty string for missing fields.
					}
				}
			}

			// Get and add additional note.
			$additional_note = get_post_meta( $data->BookingID, 'wptravelengine_additional_note', true );
			$row_data[]      = $additional_note ? $additional_note : '';

			// Write the row to CSV.
			fputcsv( $file, $row_data );
		}

		fclose( $file );
		exit;
	}
}
