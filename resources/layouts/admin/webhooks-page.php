<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Webhooks History', 'fullculqi' ); ?></h1>

	<p>
		<?php esc_html_e('You will be able to see the last 50 notifications from Culqi. ','fullculqi'); ?>
		<a href="" target="_blank">
			<?php esc_html_e( 'What is a webhook? How can I use it?', 'fullculqi' ); ?>
		</a>
	</p>

	<p>
		<b><?php printf( esc_html__( 'Webhook : %s', 'fullculqi' ), $webhook_url ); ?></b>
	</p>

	<br />

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'fullculqi' ); ?></th>
				<th><?php esc_html_e( 'Name', 'fullculqi' ); ?></th>
				<th><?php esc_html_e( 'Date', 'fullculqi' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if( empty( $webhook_list ) ) : ?>

				<tr><td colspan="3">
					<?php esc_html_e( 'There are no events', 'fullculqi' ); ?>
				</td></tr>

			<?php else : ?>
					
				<?php foreach( $webhook_list as $webhook ) : ?>
					<tr>
						<td><?php echo $webhook['event_id']; ?></td>
						<td><?php echo $webhook['event_name']; ?></td>
						<td><?php echo $webhook['creation_date']; ?></td>
					</tr>
				<?php endforeach; ?>
					
			<?php endif; ?>
		</tbody>
	</table>
</div>