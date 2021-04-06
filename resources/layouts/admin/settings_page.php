<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Culqi Full Integration Settings', 'fullculqi' ); ?>
	</h1>
	<form method="post" action="options.php">
	<?php
		// This prints out all hidden setting fields
		settings_fields( 'fullculqi_group' );
		do_settings_sections( 'fullculqi_page' );

		do_action( 'fullculqi/settings/section' );

		submit_button(); 
	?>
	</form>
</div>