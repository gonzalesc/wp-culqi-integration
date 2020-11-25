<div class="culqi_customers_box">
	<h2 class="culqi_customers_h2">
		<?php esc_html_e( 'Cards', 'fullculqi' ); ?>
	</h2>

	<div class="culqi_data_column_container">

		<?php if( ! empty( $cards ) ) : ?>
			<table class="widefat">
				<thead>
					<tr>
						<td><?php esc_html_e( 'ID', 'fullculqi' ); ?></td>
						<td><?php esc_html_e( 'Bank', 'fullculqi' ); ?></td>
						<td><?php esc_html_e( 'Country', 'fullculqi' ); ?></td>
						<td><?php esc_html_e( 'Type', 'fullculqi' ); ?></td>
						<td><?php esc_html_e( 'Brand', 'fullculqi' ); ?></td>
						<td><?php esc_html_e( 'Number', 'fullculqi' ); ?></td>

						<?php if( ! empty( $actions ) ) : ?>
							<td><?php esc_html_e( 'Actions', 'fullculqi' ); ?></td>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
				<?php foreach( $cards as $card ) : ?>
					<tr>
						<td><?php echo $card['culqi_card_id']; ?></td>
						<td><?php echo $card['culqi_card_bank']; ?></td>
						<td><?php echo $card['culqi_card_country']; ?></td>
						<td><?php echo $card['culqi_card_type']; ?></td>
						<td><?php echo $card['culqi_card_brand']; ?></td>
						<td><?php echo $card['culqi_card_number']; ?></td>

						<?php if( ! empty( $actions ) ) : ?>
						<td>
							<?php foreach( $actions as $action ) : ?>
								<a href="<?php echo $action['url']; ?>" class="button button-secondary customer_card_action" id="<?php echo $action['id']; ?>" data-id="<?php echo $card['culqi_card_id']; ?>">
								<?php echo $action['name']; ?>		
								</a>
							<?php endforeach; ?>
						</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		
		<?php else: ?>
			
			<p><?php esc_html_e( 'There is no cards', 'fullculqi' ); ?></p>

		<?php endif; ?>

	</div>
</div>