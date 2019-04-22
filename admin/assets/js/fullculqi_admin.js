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

	var last_records = jQuery('#' + box_records).length ? jQuery('#' + box_records).val() : 100;

	jQuery('#' + box_loading).html('<img src="' + fullculqi.url_loading + '" /> ' + fullculqi.sync_loading);

	return jQuery.ajax({
			url : fullculqi.url_ajax,
			dataType: 'json',
			type: 'POST',
			data: { action: box_function, records: last_records, wpnonce : fullculqi.nonce },
			success: function (response) {

				if( response.status == 'ok' )
					jQuery('#' + box_loading).html('<img src="' + fullculqi.url_success + '" /> ' + fullculqi.sync_success);
				else
					jQuery('#' + box_loading).html('<img src="' + fullculqi.url_failure + '" /> ' + response.msg);

				jQuery('.fullculqi_sync_button').removeAttr('disabled');
		}
	});
}


jQuery('.fullculqi_delete_all').click(function(e) {
	e.preventDefault();

	 if (confirm(fullculqi.text_confirm)) {

		jQuery('.fullculqi_delete_all').attr('disabled', 'disabled');

		jQuery('#fullculqi_delete_all_loading').html('<ul class="fullculqi_list"></ul>');

		fullculqi_delete_each(0);
	}
});


function fullculqi_delete_each(i) {

	var cpt = fullculqi.delete_cpts[i];

	jQuery.when(fullculqi_delete_item(cpt)).done(function(a1) {
		i++;
		if (i in fullculqi.delete_cpts)
			fullculqi_delete_each(i);
		else {
			jQuery('.fullculqi_delete_all').removeAttr('disabled');

			if( fullculqi.is_welcome )
				location.reload();
		}
	});
}

function fullculqi_delete_item(cpt) {
	jQuery('ul.fullculqi_list').append('<li class="fullculqi_item_' + cpt + '"></li>');
	jQuery('li.fullculqi_item_' + cpt).html('<img src="' + fullculqi.url_loading + '" style="width: auto;" /> ' + fullculqi.delete_loading.replace('%s',cpt));

	return jQuery.ajax({
			url : fullculqi.url_ajax,
			dataType: 'json',
			type: 'POST',
			data: { action: 'fullculqi_delete_all', cpt: cpt, wpnonce : fullculqi.nonce },
			success: function (response) {

				if( response.status == 'ok' )
					jQuery('li.fullculqi_item_' + cpt).html('<img src="' + fullculqi.url_success + '" style="width: auto;" /> ' + fullculqi.delete_success.replace('%s',cpt));
				else
					jQuery('li.fullculqi_item_' + cpt).html('<img src="' + fullculqi.url_failure + '" style="width: auto;" /> ' + response.msg);
			}
		});
}



	