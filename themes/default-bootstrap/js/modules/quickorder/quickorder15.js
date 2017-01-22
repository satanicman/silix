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
		'scrolling'   : 'no',
		'type'				: 'ajax',

		ajax : {
		    type	: "GET",
		},
		'beforeLoad'	:	function() {
			$('#fancybox-outer').addClass('quip');
		},
		'afterClose'	:	function() {
			$('#fancybox-outer').removeClass('quip');
		},
		'afterLoad'	:	function() {
			$('body').on('click', '#submitOrder', function(){
			$.fancybox.showLoading();

		var email = $('#email').val();
		var phone = $('#phone_mobile').val();
		var firstname = $('#firstname').val();
		var lastname = $('#lastname').val();
		var address = $('#address').val();
		var comment = $('#comment').val();
		var payment = $('#payment').val();


		$.ajax({
			type: 'POST',
			url: baseDir + 'modules/quickorder/ajax.php',
			async: true,
			cache: false,
			dataType : "json",
			data: 'submitQorder=true' + '&email=' + email + '&phone=' + phone + '&firstname=' + firstname + '&lastname=' + lastname + '&address=' + address  + '&comment=' + comment + '&token=' + static_token + '&payment=' + payment,
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

						$.fancybox.update();
						$.fancybox.hideLoading();
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
						//$('#qform #success').show();
					});

					//$.fancybox.update();
					$.fancybox.hideLoading();
					$.fancybox.close();
					$.fancybox.open([
							{
								type: 'inline',
								autoScale: true,
								minHeight: 30,
								content: "<p class='alert alert-success' style='margin: 0;'>Ваш заказ оформлен, мы свяжемся с Вами в ближайшее время</p>",
								'beforeClose' : function() {
									window.location = 'http://' + window.location.hostname;
								}
							}],
						{
							padding: 0
					});
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {alert("TECHNICAL ERROR: unable create order \n\nDetails:\nError: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);}
		});	

		
	});
	}});
	return false;
	
});

});