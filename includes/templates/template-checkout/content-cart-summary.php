<?php
/**
 * Content cart summary template.
 *
 * @since 6.3.0
 */

global $wte_cart;
/**
 * @var array $cart_line_items
 * @var float $deposit_amount
 * @var float $due_amount
 * @var bool $is_partial_payment
 * @var bool $show_title
 * @var bool $show_coupon_form
 */
?>
	<div class="wpte-checkout__booking-summary">
		<?php
		if ( $show_coupon_form ) {
			wptravelengine_get_template( 'template-checkout/content-coupon-form.php' );
		}
		?>
		<?php if ( $show_title ) : ?>
			<h5 class="wpte-checkout__booking-summary-title"><?php esc_html_e( "Package", 'wp-travel-engine' ); ?></h5>
		<?php endif; ?>
		<div class="wpte-checkout__table-wrap">
			<table class="wpte-checkout__booking-summary-table">
				<?php
				do_action( 'wptravelengine_cart_before_line_items', $cart_line_items );
				foreach ( $cart_line_items as $key => $lines ) {
					do_action( "wptravelengine_cart_before_{$key}_line_items", $cart_line_items );
					if ( 'line_items' === $key ) {
						foreach ( $lines as $line ) {
							foreach ( $line as $_key => $row ) {
								do_action( "wptravelengine_cart_before_{$_key}_line_items", $cart_line_items );
								echo wp_kses( is_array( $row ) ? implode( '', $row ) : $row, array_merge(
									wp_kses_allowed_html( 'post' ),
									array( 'svg' => array() ),
									array( 'use' => array( 'xlink:href' => array() ) )
								) );
								do_action( "wptravelengine_cart_after_{$_key}_line_items", $cart_line_items );
							}
						}
						continue;
					}
					echo wp_kses( is_array( $lines ) ? implode( '', $lines ) : $lines, array_merge(
						wp_kses_allowed_html( 'post' ),
						array( 'svg' => array() ),
						array( 'use' => array( 'xlink:href' => array() ) )
					) );
					do_action( "wptravelengine_cart_after_{$key}_line_items", $cart_line_items );
				}
				do_action( 'wptravelengine_cart_after_line_items', $cart_line_items );
				?>
				<!-- Partial Payment -->
				<?php if ( $is_partial_payment ) : ?>
					<?php if ( 'due' === $wte_cart->get_payment_type() ): ?>
						<!-- Deposited -->
						<tr class="wpte-checkout__booking-summary-deposit">
							<td><strong><?php echo __( "Deposited:", 'wp-travel-engine' ); ?></strong></td>
							<td><strong>- <?php wptravelengine_the_price( $deposit_amount ); ?></strong></td>
						</tr>
					<?php else: ?>
						<!-- Deposit -->
						<tr class="wpte-checkout__booking-summary-deposit">
							<td><strong><?php echo __( "Deposit Today:", 'wp-travel-engine' ); ?></strong></td>
							<td><strong>- <?php wptravelengine_the_price( $deposit_amount ); ?></strong></td>
						</tr>
					<?php endif; ?>
					<!-- Amount Due -->
					<tr>
						<td><strong><?php echo __( "Amount Due:", 'wp-travel-engine' ); ?></strong></td>
						<td><strong><?php wptravelengine_the_price( $due_amount ); ?></strong></td>
					</tr>
				<?php endif; ?>
			</table>
		</div>
	</div>
<?php
wptravelengine_get_template( 'template-checkout/content-sprite-svg.php' );
