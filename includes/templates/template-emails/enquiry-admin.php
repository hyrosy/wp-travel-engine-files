<?php
/**
 *
 * Customer Enquiry Submission template.
 *
 * @since 6.5.0
 */
?>
<table style="width:100%;">
	<tr>
		<td colspan="2" style="text-align: center;font-size: 24px;line-height: 1.5;font-weight: bold;">
			<?php echo esc_html__( 'New Enquiry', 'wp-travel-engine' ); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="text-align: center;padding: 16px 0 8px;">
			<strong><?php echo esc_html__( 'Enquiry Details', 'wp-travel-engine' ); ?></strong>
		</td>
	</tr>
	<?php foreach ( $args as $key => $data ) :
			$data        = is_array( $data ) ? implode( ', ', $data ) : $data;
			$field_label = wp_travel_engine_get_enquiry_field_label_by_name( $key );
			?>
		<tr>
			<td style="color: #566267;"><?php echo esc_html( $field_label ); ?></td>
			<td style="width: 50%;text-align: right;"><strong><?php
			if ( in_array( $key, array( 'package_name', 'enquiry_message' ) ) ) {
				echo wp_kses(
					$data,
					array(
						'a' => array( 'href' => array() ),
						'b' => array(),
					)
					);
			} else {
				echo esc_html( $data );
			}
			?></strong></td>
		</tr>
	<?php endforeach; ?>
</table>
