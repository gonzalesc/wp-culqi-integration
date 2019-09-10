var fullculqi_isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
Culqi.publicKey = fullculqi.public_key;

Culqi.settings({
	title: fullculqi.commerce,
	currency: fullculqi.currency,
	description: fullculqi.description,
	amount: fullculqi.total
});


args_options = {};

// If is enable the installments option
if( fullculqi.installments == 'yes' ) {
	args_options.installments = true;
}

// if is set the logo url
if( fullculqi.url_logo.length > 0 ) {
	args_options.style = { logo : fullculqi.url_logo };	
}

if( Object.keys(args_options).length > 0 ) {
	Culqi.options(args_options);
}

function culqi() {
	
	if(Culqi.error) {
		//console.log(Culqi.error);
		jQuery('#fullculqi_notify').html('<p style="color:#e54848; font-weight:bold">'+ Culqi.error.user_message + '</p>');
	
	} else {
		
		jQuery(document).ajaxStart(function(){
			jQuery('#fullculqi_notify').empty();
			
			jQuery('#receipt_page_fullculqi').waitMe({
					effect		: 'pulse',
					text 		: fullculqi.loading_text,
					bg			: 'rgba(255,255,255,0.7)',
					color		: '#000000',
					maxSize		: '',
					waitTime	: -1,
					textPos		: 'vertical',
					fontSize	: '',
					source		: '',
					onClose : function() {},
				});
		});

		jQuery(document).ajaxComplete(function(){
			jQuery('#receipt_page_fullculqi').waitMe('hide');
		});

		jQuery.ajax({
			url 		: fullculqi.url_ajax,
			type 		: 'POST',
			dataType	: 'json',
			data 		: {
							action			: 'fullculqi',
							token_id		: Culqi.token.id,
							order_id 		: fullculqi.order_id,
							installments	: Culqi.token.metadata.installments,
							wpnonce			: fullculqi.wpnonce
						},
			
			success: function(data) {
				
				if(data.status === 'error') {
					jQuery('#fullculqi_notify').html('<p style="color:#e54848; font-weight:bold">'+ fullculqi.msg_fail + '</p>');
				
				} else {
					
					//jQuery('#fullculqi_notify').empty();
					//jQuery('#fullculqi_notify').append("<h1 style='text-align: center;'>Pago Exitoso</h1>" +
					//"<p style='color:#46e6aa; font-weight:bold'>Pago realizado exitosamente</p>" +
					//"<br><button id='home'>Seguir comprando</button>");

					jQuery('#fullculqi_notify').trigger('fullculqi.success_notify', [fullculqi]);

					location.href = fullculqi.url_success;
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log(jqXHR);
				console.log(textStatus);
				console.log(errorThrown);

				jQuery('#fullculqi_notify').trigger('fullculqi.error_notify', [fullculqi]);
				
				jQuery('#fullculqi_notify').empty();
				jQuery('#fullculqi_notify').html(fullculqi.msg_error);
			}
		});
	}
};

if( !fullculqi_isSafari && fullculqi.time_modal > 0 ) {
	setTimeout(function() {
		jQuery('#fullculqi_button').trigger('click');
	}, fullculqi.time_modal);
}

jQuery(document).ready(function() {
	jQuery('#fullculqi_button').on('click', function (e) {
		Culqi.open();
		jQuery('#culqi_notify').empty();
		e.preventDefault();
	});
});