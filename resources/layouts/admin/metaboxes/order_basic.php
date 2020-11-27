<div class="culqi_orders_box">
	<h2 class="culqi_orders_h2">
		<?php printf( esc_html__( 'Culqi ID : %s','fullculqi'), $id ); ?>
	</h2>
	<p class="culqi_orders_subh2">
		<?php printf( esc_html__( 'Created via FullCulqi on %s.', 'fullculqi' ), $creation ); ?>
	</p>

	<div class="culqi_data_column_container">
		<div class="culqi_data_column">
			<h3 class="culqi_orders_h3">
				<?php esc_html_e( 'Order Data', 'fullculqi' ); ?>
			</h3>
			<ul>
				<li>
					<b><?php esc_html_e( 'Creation Date', 'fullculqi' ); ?> : </b>
					<?php echo $creation; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Expiration Date', 'fullculqi' ); ?> : </b>
					<?php echo $expiration; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Last Status Date', 'fullculqi' ); ?> : </b>
					<?php echo $status_date; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Currency', 'fullculqi' ); ?> : </b>
					<?php echo $currency; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Amount', 'fullculqi' ); ?> : </b>
					<?php echo $amount; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'CIP Code', 'fullculqi' ); ?> : </b>
					<?php echo $cip; ?>
				</li>
			</ul>
			<?php
				printf(
					'<mark class="culqi_status_2 %s"><span>%s</span></mark>',
					$status, $statuses[$status]
				);
			?>
		</div>
		<div class="culqi_data_column">
			<h3 class="culqi_orders_h3">
				<?php esc_html_e( 'Customer', 'fullculqi' ); ?>
			</h3>
			<ul>
				<li>
					<b><?php esc_html_e( 'First Name', 'fullculqi' ); ?> : </b>
					<?php echo $first_name; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Last Name', 'fullculqi' ); ?> : </b>
					<?php echo $last_name; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'City', 'fullculqi' ); ?> : </b>
					<?php echo $city; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Country', 'fullculqi' ); ?> : </b>
					<?php echo $country; ?>
				</li>
				<li>
					<b><?php esc_html_e( 'Phone', 'fullculqi' ); ?> : </b>
					<?php echo $phone; ?>
				</li>
			</ul>
		</div>
	</div>
	<div class="clear"></div>
</div>