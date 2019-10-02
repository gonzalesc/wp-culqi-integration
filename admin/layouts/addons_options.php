<div class="wrap fullculqi_addons_wrap">
	<div class="fullculqi_addons_title">
		<h1><?php _e('Culqi Addons','letsgo'); ?></h1>
	</div>
	<div class="fullculqi_addons_all">
		<div class="fullculqi_addons_container">
			<div class="fullculqi_addons_item">
				<div class="fullculqi_addons_header">
					<img src="<?php echo FULLCULQI_PLUGIN_URL; ?>admin/assets/images/letsgo_1.png" alt="Fullculqi One Click" />
				</div>
				<div class="fullculqi_addons_body">
					<img src="<?php echo FULLCULQI_PLUGIN_URL; ?>admin/assets/images/icon_woo.png" alt="wordpress" />
					<h2><?php _e('Culqi One Click Payments', 'letsgo'); ?></h2>
					<p><?php _e('Your buyers will can do their purchase with a single click in the checkout page', 'letsgo'); ?></p>
				</div>
				<div class="fullculqi_addons_footer">
					<?php if( is_plugin_active('wp-culqi-integration-creditcard/index.php') ) : ?>
						<a href="http://bit.ly/2n5Nncw" target="_blank" class="button">
							<img src="<?php echo admin_url('images/yes.png'); ?>" alt="check" style="vertical-align: middle" />
							<?php _e('Installed','letsgo'); ?>
						</a>
					<?php else : ?>
						<a href="http://bit.ly/2n5Nncw" target="_blank" class="button"><?php _e('Download','letsgo'); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="fullculqi_addons_container">
			<div class="fullculqi_addons_item">
				<div class="fullculqi_addons_header">
					<img src="<?php echo FULLCULQI_PLUGIN_URL; ?>admin/assets/images/letsgo_2.png" alt="Woocommerce Culqi Subscriptions" />
				</div>
				<div class="fullculqi_addons_body">
					<img src="<?php echo FULLCULQI_PLUGIN_URL; ?>admin/assets/images/icon_woo.png" alt="wordpress" />
					<h2><?php _e('Culqi Subscriptions', 'letsgo'); ?></h2>
					<p><?php _e('Your ecommerce will can sell products or services using Culqi recurring payment.', 'letsgo'); ?></p>
				</div>
				<div class="fullculqi_addons_footer">
					<?php if( is_plugin_active('wp-culqi-integration-subscription/index.php') ) : ?>
						<a href="http://bit.ly/2nfunrW" target="_blank" class="button">
							<img src="<?php echo admin_url('images/yes.png'); ?>" alt="check" style="vertical-align: middle" />
							<?php _e('Installed','letsgo'); ?>
						</a>
					<?php else : ?>
						<a href="http://bit.ly/2nfunrW" target="_blank" class="button"><?php _e('Download','letsgo'); ?></a>
					<?php endif;?>
				</div>
			</div>
		</div>

		<div class="fullculqi_addons_container">
			<div class="fullculqi_addons_item">
				<div class="fullculqi_addons_header">
					<img src="<?php echo FULLCULQI_PLUGIN_URL; ?>admin/assets/images/letsgo_3.png" alt="Wordpress Culqi Payment Buttons" />
				</div>
				<div class="fullculqi_addons_body">
					<img src="<?php echo FULLCULQI_PLUGIN_URL; ?>admin/assets/images/icon_wp.png" alt="wordpress" />
					<h2><?php _e('Culqi Payment Buttons', 'letsgo'); ?></h2>
					<p><?php _e('You can use Culqi payments buttons in everywhere of you website without have a ecommerce installed.', 'letsgo'); ?></p>
				</div>
				<div class="fullculqi_addons_footer">
					<?php if( is_plugin_active('wp-culqi-integration-button/index.php') ) : ?>
						<a href="http://bit.ly/2oMUffe" target="_blank" class="button">
							<img src="<?php echo admin_url('images/yes.png'); ?>" alt="check" style="vertical-align: middle" />
							<?php _e('Installed','letsgo'); ?>
						</a>
					<?php else : ?>
						<a href="http://bit.ly/2oMUffe" target="_blank" class="button">
							<?php _e('Download','letsgo'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

</div>