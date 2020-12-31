$(document).on('click', "#more-inbound", function(e){
	e.preventDefault();
	counter = $(this).data('index') + 1;
	$('.m-select2').select2('destroy');
	var cloned = 
	'<tr>'+
		'<td>'+
			'<select class="form-control m-input m-input--square m-select2 d-block product_selection other-product" name="product_id['+counter+']" data-index="'+counter+'">'+
				'<option value="">-- Select Product --</option>'+
			'</select>'+
		'</td>'+
		'<td class="inbound_color"></td>'+
		'<td class="inbound_variance">'+
			
		'</td>'+
	'</tr>';
	$("#inbound_form").find('tbody tr:last').after(cloned);
	var client = $('#client_id option:selected').val();
	var model = $('select[name="product_id['+counter+']"]');
	var baseurl = document.getElementById('baseurl').value;
	$.getJSON(baseurl+"/inbound/get_product/"+client, 
    function(data) {
        $.each(data, function(index, element) {
            model.append("<option value='"+element.id+"' data-producttype='"+element.product_type_id+"' data-color='"+((element.color != '')?element.color:"White")+"'>" + element.name + "</option>");
        });
    });
	$("select[name*='product_id'].other-product").change(function(){
		var index = $(this).data("index"),
			color = $(this).select2().find(":selected").data("color");
		$(this).closest('tr').children('td.inbound_variance').empty();
		$(this).closest('tr').children('td.inbound_color').html(color);
		var anchor = $(this).closest('tr').children('td.inbound_variance');
		var producttype = $(this).select2().find(":selected").data("producttype");
		var index = 0;
		$.getJSON(baseurl+"/inbound/get_variance"+"/"+producttype,
	    function(data) {
	        $.each(data, function(index, element) {
	            anchor.append(
	            	'<div class="col-auto">'+
            	        '<div class="input-group m-input-group">'+
            	            '<div class="input-group-prepend">'+
            	                '<span class="input-group-text">'+element.name+'</span>'+
            	            '</div>'+
            	            '<input type="number" min="0" class="form-control" placeholder="0" name="stated_qty['+counter+']['+index+']">'+
            	            '<input type="hidden" value="'+element.name+'" name="product_type_size_name['+counter+']['+index+']">'+
            	            '<input type="hidden" value="'+element.id+'" name="product_type_size_id['+counter+']['+index+']">'+
            	            '<input type="hidden" value="'+color+'" name="product_color['+counter+']['+index+']">'+
            	        '</div>'+
    				'</div>'
    			);
    			index++;
	        });
	    });
	});
	$('.m-select2').select2();
});
