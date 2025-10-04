<?php
/**
 * @var WPTravelEngine\Builders\FormFields\PrivacyPolicyFields $privacy_policy_fields
 * @var bool $show_title
 * @var array $payment_methods
 * @since 6.3.0
 */
?>
<!-- Payment Form -->
<div class="wpte-checkout__box">
	<?php if ( $show_title ) : ?>
		<h3 class="wpte-checkout__box-title"><?= __( 'Payment', 'wp-travel-engine' ); ?></h3>
	<?php endif; ?>
	<div class="wpte-checkout__box-content" data-checkout-payment-methods>
		<?php if ( count( $payment_methods ) ) : ?>
			<div class="wpte-checkout__ssl-message">
				<?= __( 'This is a secure and SSL encrypted payment. Your credit card details are safe!', 'wp-travel-engine' ); ?>
			</div>
		<?php endif; ?>
		<div data-checkout-payment-modes>
			<?php do_action( 'wptravelengine_checkout_payment_modes' ); ?>
		</div>
		<?php
		do_action( 'wptravelengine_checkout_payment_methods' );
		?>
		<div class="wpte-checkout__term-condition">
			<?php $privacy_policy_fields->render(); ?>
		</div>
		<div class="wpte-checkout__form-submit" data-checkout-form-submit>
			<?php do_action( 'wptravelengine_checkout_form_submit_button' ); ?>
		</div>
	</div>

</div>
