$(document).ready(function() {
	var regx = new RegExp('blocklayered-ajax.php'),
		type = $('#moreProduct_type').val();

	if(type == 2) {
		$(window).scroll(function() {
			var last = !!parseInt($('#moreProduct_last').val());
			if(!last) {
				scroll($(this));
			}
		});
	} else {
		$(document).on('submit', '#moreProductsForm', function (event) {
			event.preventDefault();
			var last = !!parseInt($('#moreProduct_last').val());
			if(!last) {
				$('#moreProduct_ajax_loader').show();
				moreProducts();
			}
		});
	}
});

function maceRequest() {
	var result = {},
		id_category = $('#moreProduct_id_category').val(),
		search_query = $('#moreProduct_search_query').val();
	if(typeof id_category != 'undefined')
		result.id_category = id_category;
	else if(typeof search_query != 'undefined')
		result.search_query = search_query;
	else return false;

	result.orderBy = $('#moreProduct_orderby').val();
	result.orderWay = $('#moreProduct_orderway').val();
	result.n = parseInt($('#moreProduct_n').val());
	result.p = parseInt($('#moreProduct_p').val());
	result.selected_filters = window.location.href.split('#')[1];
	return result;
}

function moreProducts() {
	var list = $('.product_list');
	var data = maceRequest();
	data.getProducts = 1;
	$.ajax({
			url: baseDir + 'modules/moreproducts/moreproducts-ajax.php' + '?rand=' + new Date().getTime(),
			type: 'GET',
			dataType: 'json',
			headers: { "cache-control": "no-cache" },
			async: true,
			cache: false,
			data: data,
			success: function(result) {
				if(!result.errors) {
					$('#center_column').html(result.result);
					$('#moreProduct_n').val(result.n);
					$('#moreProduct_last').val(result.last);
				} else {
					console.log(result.errors);
				}
				$('#moreProduct_ajax_loader').hide();
			},
			error: function () {
				$('#moreProduct_ajax_loader').hide();
			}
		});
}

function scroll(element) {
	var list = $('.product_list'),
		listTop = list.offset().top,
		listHeight = list.outerHeight(true),
		window = element.scrollTop(),
		windowHeight = element.innerHeight();
	if(window + windowHeight >= listTop + listHeight) {
		$('#moreProduct_ajax_loader').show();
		moreProducts();
	}
}