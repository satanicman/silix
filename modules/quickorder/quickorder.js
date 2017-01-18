$(document).ready(function()
{
	$('#quickorder').click(function(){
	$.fancybox({
		'transitionIn'		: 'zoomIn',
		'transitionOut'		: 'zoomOut',
		'titleShow'     	: false,
		'showCloseButton'	: true,
		'centerOnScroll'	: true,
		'href'				: baseDir + 'modules/quickorder/ajax.php',
		'padding'			: 0,
        'autoScale'     	: false,

		ajax : {
		    type	: "GET",
		},
		'onStart'	:	function() {
			$('#fancybox-outer').addClass('quip');
		},
		'onClosed'	:	function() {
			$('#fancybox-outer').removeClass('quip');
		},
		'onComplete'	:	function() {
			$('#qform #submitOrder').click(function(){
			$.fancybox.showActivity();

		var email = $('#email').val();
		var phone = $('#phone_mobile').val();
		var firstname = $('#firstname').val();
		var lastname = $('#lastname').val();
		var address = $('#address').val();
		var comment = $('#comment').val();

		$.ajax({
			type: 'POST',
			url: baseDir + 'modules/quickorder/ajax.php',
			async: true,
			cache: false,
			dataType : "json",
			data: 'submitQorder=true' + '&email=' + email + '&phone=' + phone + '&firstname=' + firstname + '&lastname=' + lastname + '&address=' + address  + '&comment=' + comment + '&token=' + static_token,
			success: function(jsonData)
			{
				if (jsonData.hasError)
				{
					var errors = '<b>'+'Ошибки: ' + '</b><ol>';
					for(error in jsonData.errors)
						if(error != 'indexOf')
							errors += '<li>'+jsonData.errors[error]+'</li>';						
						errors += '</ol>';
						$('#errors').html(errors).slideDown('slow');
						$.fancybox.resize();
						$.fancybox.hideActivity();
				}
				else
				{
					$('.ajax_cart_quantity, .ajax_cart_product_txt_s, .ajax_cart_product_txt, .ajax_cart_total').each(function(){
						$(this).hide();
					});
					$('#cart_block dl.products').remove();
					$('.ajax_cart_no_product').show('slow');

					$('#qform #wrap').hide();
					$('#qform #errors').slideUp('slow', function(){
						$('#qform #errors').hide();
						$('#qform .submit').hide(); 
						$('#qform #success').show();
					});

					$.fancybox.hideActivity();
					$.fancybox.resize();
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {alert("TECHNICAL ERROR: unable create order \n\nDetails:\nError: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);}
		});	
	});
	}});
	return false;
});
});