@extends('layouts.base',[
    'page' => 'Inbound'
])

@section('content')
<!--begin::Portlet-->
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					New Inbound
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('inbound') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('inbound/create') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">
			@include('notif')
			<input type="hidden" id="baseurl" name="baseurl" value="{{URL::to('/')}}" />
			<div id="client_form" class="m-form__section m-form__section--first">
				@if(Auth::user()->roles == 'client')
					<input type="hidden" name="client_id" value="{{ Auth::user()->client_id }}"/>
					<input type="hidden" name="status" value="REGISTER"/>
				@else
					<div class="form-group m-form__group row">
						<div class="col-sm-6">
							<label for="client">Client:</label>
							<select id="client_id" class="form-control m-input m-input--square m-select2" name="client_id">
								<option value="">-- Select client --</option>
								@foreach(\App\Client::all() as $client)
									<option value="{{ $client->id }}" @if($client->id == old('client_id') || ($client_id != null && $client->id == $client_id)){{'selected'}}@endif>{{ $client->name }}</option>
								@endforeach
							</select>
							<span class="m-form__help">Please select from listed clients</span>
						</div>
						<div class="col-sm-6">
							<label for="status">Status:</label>
							<select class="form-control m-select2" name="status">
								<option value="REGISTER">Register</option>
								<option value="RETURN">Return</option>
								<option value="EVENT">Event</option>
							</select>
						</div>
					</div>
				@endif
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="arrival_date">Arrival Date:</label>
						<input type='text' class="form-control" value="{{ old('arrival_date') ?: date('Y-m-d H:i') }}" placeholder="Pick arrival date" name="arrival_date" id='arrival_date' autocomplete="off"/>
						<span class="m-form__help">Please select arrival date of package</span>
					</div>
					<div class="col-sm-6">
						<label for="notes">Notes:</label>
						<input type="text" class="form-control m-input" placeholder="Extra information" name="notes" value="{{ old('notes') }}">
						<span class="m-form__help">Extra information for this inbound batch</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-4">
						<label for="courier">Courier:</label>
						<input type="text" class="form-control m-input" name="courier" value="{{ old('courier') }}" />
						<span class="m-form__help">The courier agent who sent the package</span>
					</div>
					<div class="col-sm-4">
						<label for="sender_name">Sender Name:</label>
						<input type="text" class="form-control m-input" name="sender_name" value="{{ old('sender_name') }}">
						<span class="m-form__help">Person who send the package</span>
					</div>
					<div class="col-sm-4">
						<label for="shipping_cost">Courier Cost:</label>
						<input type="number" class="form-control m-input" name="shipping_cost" value="{{ old('shipping_cost') }}" />
						<span class="m-form__help">Cost of the delivery</span>
					</div>
				</div>
				<table id="inbound_form" class="table table-bordered" @if(Auth::user()->roles != 'client'){{'hidden'}}@endif>
					<thead>
						<tr>
							<th width="35%">Product</th>
							<th width="30%">Color</th>
							<th width="35%">Size</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<select class="form-control m-input m-input--square m-select2 d-block product_selection first-product" name="product_id[0]" data-index="0">
									<option value="">-- Select Product --</option>
								</select>
							</td>
							<td class="inbound_color"></td>
							<td class="inbound_variance"></td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="3">
								<a id="more-inbound" href="javascript:;" class="btn btn-primary pull-right" data-index="0">Add Product</a>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Inbound</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<!-- <script src="{{ asset('js/vendors/bootstrap-datetimepicker/bootstrap-datetimepicker.js') }}" type="text/javascript"></script> -->
<script type="text/javascript">

	$(".m-select2").select2({ width: '100%' }); 

	$('#client_id').change(function(){
		if($('#client_id option:selected').val()!== null){
			$("#inbound_form").removeAttr('hidden');
			var client = $('#client_id option:selected').val();
			$('#inbound_form tbody tr:first-child').children('td').first().children('.product_selection').empty();
			var model = $('#inbound_form tbody tr:first-child').children('td').first().children('.product_selection');
			model.append("<option value=''>-- Select Product --</option>");
			$.getJSON("{{url('inbound/get_product')}}"+"/"+client, 
	        function(data) {
	            $.each(data, function(index, element) {
	                model.append("<option value='"+element.id+"' data-producttype='"+element.product_type_id+"' data-color='"+((element.color != '')?element.color:"White")+"'>" + element.name + "</option>");
	            });
	        });
		} else{
			$("#inbound_form").attr('hidden','');
		};
	});

	$("select[name*='product_id'].first-product").change(function(){
		var index = $(this).data("index"),
			color = $(this).select2().find(":selected").data("color");
		$(this).closest('tr').children('td.inbound_variance').empty();
		$(this).closest('tr').children('td.inbound_color').html(color);
		var anchor= $(this).closest('tr').children('td.inbound_variance');
		var producttype = $(this).select2().find(":selected").data("producttype");
	    var index = 0;
		$.getJSON("{{url('inbound/get_variance')}}"+"/"+producttype, 
	    function(data) {
	        $.each(data, function(index, element) {
	            anchor.append(
	            	'<div class="col-auto">'+
            	        '<div class="input-group m-input-group">'+
            	            '<div class="input-group-prepend">'+
            	                '<span class="input-group-text">'+element.name+'</span>'+
            	            '</div>'+
            	            '<input type="number" min="0" class="form-control" placeholder="0" name="stated_qty[0]['+index+']">'+
            	            '<input type="hidden" value="'+element.name+'" name="product_type_size_name[0]['+index+']">'+
            	            '<input type="hidden" value="'+element.id+'" name="product_type_size_id[0]['+index+']">'+
            	            '<input type="hidden" value="'+color+'" name="product_color[0]['+index+']">'+
            	        '</div>'+
    				'</div>'
    			);
    			index++;
	        });
	    });
	});

	$('#arrival_date').datetimepicker({
		format: 'yyyy-mm-dd hh:ii',
		autoclose: true,
		todayHighlight: true,
	});

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
		@if(Auth::user()->roles == 'client')
		var client = $('input[name="client_id"]').val();
		@else
		var client = $('#client_id option:selected').val();
		@endif
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
		$(this).data('index',counter);
	});

	@if(Auth::user()->roles == 'client')
	var client = {{ Auth::user()->client_id }};
	$('#inbound_form tbody tr:first-child').children('td').first().children('.product_selection').empty();
	var model = $('#inbound_form tbody tr:first-child').children('td').first().children('.product_selection');
	model.append("<option value=''>-- Select Product --</option>");
	$.getJSON("{{url('inbound/get_product')}}"+"/"+client, 
    function(data) {
        $.each(data, function(index, element) {
            model.append("<option value='"+element.id+"' data-producttype='"+element.product_type_id+"' data-color='"+((element.color != '')?element.color:"White")+"'>" + element.name + "</option>");
        });
    });
	@endif

</script>
@endsection