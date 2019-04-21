<?php
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<div class="wrap about-wrap full-width-layout">
	<div style="float: right;">
		<img src="<?php echo FULLCULQI_PLUGIN_URL . 'admin/assets/images/culqi_logo.png'; ?>" alt="FullCulqi Logo" />
	</div>
	<h1><?php _e('FullCulqi Integration','letsgo'); ?></h1>

	<p class="about-text"><?php _e('Thanks for use the last version!', 'letsgo') ?></p>

	<hr />

	<div class="about-wrap-content">

		<div class="feature-section one-col is-wide wp-clearfix">
			<div class="col">
				<h2><?php _e('This will just take a minute!','letsgo'); ?></h2>
				<p class="about-description"><?php _e('To continue with this integration, you need provide the public and secret key.','letsgo'); ?></p>
			</div>
		</div>

		<div class="feature-section one-col is-wide wp-clearfix one-col">
			

			<div class="col">
				<div class="alignleft" style="margin-right: 20px;">
					<img src="<?php echo FULLCULQI_PLUGIN_URL . 'admin/assets/images/welcome.png'; ?>" alt="FullCulqi Logo" style="width:100%;" />
				</div>

				<br /><br /><br />
				<form action="" method="POST">
					<table>
						<tr><td>
							<label for="commerce"><b><?php _e('Commerce Name','letsgo'); ?> : </b></label>
						</td><td>
							<input type="text" id="commerce" name="fullculqi_options[commerce]" value="" />
						</td></tr>
						<tr><td>
							<label for="public_key"><b><?php _e('Public Key','letsgo'); ?> : </b></label>
						</td><td>
							<input type="text" id="public_key" name="fullculqi_options[public_key]" value="" />
						</td></tr>
						<tr><td>
							<label for="secret_key"><b><?php _e('Secret Key','letsgo'); ?> : </b></label>
						</td><td>
							<input type="text" id="secret_key" name="fullculqi_options[secret_key]" value="" />
						</td></tr>
						<tr><td>
							<label for="woo_payment"><b><?php _e('Activate payment to Woocommerce','letsgo'); ?> : </b></label>
						</td><td>
							<input type="checkbox" name="fullculqi_options[woo_payment]" value="yes" />
						</td></tr>
						<tr><td colspan="2">
							<input type="submit" class="button button-primary button-hero" value="<?php _e('Synchronize with Culqi','letsgo'); ?>" />
						</td></tr>
					</table>
					<?php wp_nonce_field( 'fullculqi_wpnonce', 'fullculqi_install' ); ?>
				</form>

				<a href="<?php echo admin_url('admin.php?page=fullculqi_settings'); ?>"><?php _e('Not now','letsgo'); ?></a>
			</div>
		</div>

	</div>
</div>