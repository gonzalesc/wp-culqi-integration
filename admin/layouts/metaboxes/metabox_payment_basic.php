<?php
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<div class="culqi_payments_box">
	<h2 class="culqi_payments_h2">Culqi ID : <?php echo $id; ?></h2>
	<p class="culqi_payments_subh2"><?php printf(__('Payment via FullCulqi. Paid on %s. Customer IP: %s','letsgo'), $creation, $ip); ?></p>

	<div class="culqi_data_column_container">
		<div class="culqi_data_column">
			<h3 class="culqi_payments_h3"><?php _e('Payment Data','letsgo'); ?></h3>
			<ul>
				<li><b><?php _e('Currency', 'letsgo'); ?> :</b> <?php echo $currency; ?></li>
				<li><b><?php _e('Amount', 'letsgo'); ?> :</b> <?php echo $amount; ?></li>
				<li><b><?php _e('Refund', 'letsgo'); ?> :</b> <?php echo $refunded; ?></li>
				<li><b><?php _e('Card Brand', 'letsgo'); ?> :</b> <?php echo $card_brand; ?></li>
				<li><b><?php _e('Card Type', 'letsgo'); ?> :</b> <?php echo $card_type; ?></li>
				<li><b><?php _e('Card Number', 'letsgo'); ?> :</b> <?php echo $card_number; ?></li>
			</ul>
		</div>
		<div class="culqi_data_column">
			<h3 class="culqi_payments_h3"><?php _e('Customer','letsgo'); ?></h3>
			<ul>
				<li><b><?php _e('First Name', 'letsgo'); ?> :</b> <?php echo $first_name; ?></li>
				<li><b><?php _e('Last Name', 'letsgo'); ?> :</b> <?php echo $last_name; ?></li>
				<li><b><?php _e('City', 'letsgo'); ?> :</b> <?php echo $city; ?></li>
				<li><b><?php _e('Country', 'letsgo'); ?> :</b> <?php echo $country; ?></li>
				<li><b><?php _e('Phone', 'letsgo'); ?> :</b> <?php echo $phone; ?></li>
			</ul>
		</div>
	</div>
	<div class="clear"></div>
</div>