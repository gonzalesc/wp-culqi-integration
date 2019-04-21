const urlParams = new URLSearchParams(window.location.search);
const sync = urlParams.get('synchronize');

if( sync == 'payments' ) {
	fullculqi_sync('payments');
}


jQuery('.fullculqi_sync_button').click(function(e) {
	e.preventDefault();

	var action = jQuery(this).data('action');
	fullculqi_sync(action);
});


function fullculqi_sync(action = null) {

	jQuery('.fullculqi_sync_button').attr('disabled', 'disabled');

	var box_function = 'fullculqi_get_' + action;
	var box_loading = 'fullculqi_sync_' + action + '_loading';
	var box_records = 'fullculqi_sync_' + action + '_records';

	var last_records = jQuery('#' + box_records).val();

	jQuery('#' + box_loading).html('<img src="' + fullculqi.url_loading + '" /> ' + fullculqi.text_loading);

	return jQuery.ajax({
			url : fullculqi.url_ajax,
			dataType: 'json',
			type: 'POST',
			data: { action: box_function, records: last_records, wpnonce : fullculqi.nonce },
			success: function (response) {

				if( response.status == 'ok' )
					jQuery('#' + box_loading).html('<img src="' + fullculqi.url_success + '" /> ' + fullculqi.text_success);
				else
					jQuery('#' + box_loading).html('<img src="' + fullculqi.url_failure + '" /> ' + response.msg);

				jQuery('.fullculqi_sync_button').removeAttr('disabled');
		}
	});
}