@extends('layouts.base',[
    'page' => 'Order'
])

@section('json')
<script>
	var products = [],
		clients = {!! $clients !!},
		productDetail = '',
		selectedClient = '',
		totalQty = 0,
		pricePcs = 0;
</script>
@endsection

@section('style')
<style>
.btn-warehouse {
	display:inline-block;
	margin-right:5px;
}
</style>
@endsection

@section('modal')
<!--begin::Modal-->
<div class="modal fade" id="warehouse-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Warehouse Selection</h5>
			</div>
			<div class="modal-body"></div>
		</div>
	</div>
</div>
<!--end::Modal-->
@endsection

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
					Add New Order
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('order') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<span>Ready to Outbound</span>&nbsp;&nbsp;<i class="flaticon-truck"></i>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('order/create') }}" id="order-form" enctype="multipart/form-data">
		{{ csrf_field() }}
		<input type="hidden" value="" name="order_pricing" id="order_pricing"/>
		<input type="hidden" value="" name="order_details" id="order_details"/>
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section row">
				<div class="col-md-7 col-xs-6">
					<div class="form-group m-form__group m--margin-top-10">
						<div class="alert m-alert m-alert--default" role="alert"><i class="fa fa-pencil-alt"></i> Fill in the order form</div>
					</div>
					<div class="row">
						<div class="col-sm-10">
							<div class="form-group m-form__group row">
								<label for="order_type" class="col-sm-3">Order Type:</label>
								<div class="col-sm-9">
									<select class="form-control m-input m-input--square m-select2" name="order_type" required>
										@foreach(\App\Order::orderType() as $value => $display)
											<option value="{{ $value }}">{{ $display }}</option>
										@endforeach
									</select>
									<span class="m-form__help">Please select order type above</span>
								</div>
							</div>
							@if(Auth::user()->roles == 'client')
							@php
								$client = \App\Client::find(Auth::user()->client_id);
							@endphp
							<input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}"/>
							@else
							<div class="form-group m-form__group row">
								<label for="client_id" class="col-sm-3">Client</label>
								<div class="col-sm-9 m-typeahead">
									<input type="text" class="form-control" name="client_id" id="client_id"/>
									<span class="m-form__help">Please type in your selected client</span>
								</div>
							</div>
							@endif
							<div class="form-group m-form__group row">
								<label for="notes" class="col-sm-3">Notes</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Another extra points" name="notes" value="{{ old('notes') }}" />
									<span class="m-form__help">Order extra notes</span>
								</div>
							</div>
							<div class="clearfix"></div>
							<br>
							<div class="m-form__seperator m-form__seperator--dashed"></div>
							<br>
							<div class="form-group m-form__group row">
								<label for="name" class="col-sm-3">Name</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer name" name="name" value="{{ old('name') }}" required>
									<span class="m-form__help">Customer name</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="phone" class="col-sm-3">Phone</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer phone number" name="phone" value="{{ old('phone') }}" required>
									<span class="m-form__help">Customer phone number</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="email" class="col-sm-3">Email</label>
								<div class="col-sm-9">
									<input type="email" class="form-control m-input" placeholder="Customer email address" name="email" value="{{ old('email') }}">
									<span class="m-form__help">Customer email address</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="address" class="col-sm-3">Address</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer destination address" name="address" value="{{ old('address') }}" required>
									<span class="m-form__help">Customer full address</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="zip_code" class="col-sm-3">Postcode</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer address postcode" name="zip_code" value="{{ old('zip_code') }}" required>
									<span class="m-form__help">Customer destination address postcode</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="courier" class="col-sm-3">Courier Name</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Courier company name" name="courier" value="{{ old('courier') }}">
									<span class="m-form__help">Courier / Logictics company name</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="no_resi" class="col-sm-3">Receipt Number</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Delivery receipt number" name="no_resi" value="{{ old('no_resi') }}" required>
									<span class="m-form__help">Delivery receipt number from courier agent</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="shipping_cost" class="col-sm-3">Shipping Cost</label>
								<div class="col-sm-9">
									<input type="number" min="0" class="form-control m-input" placeholder="Shipment cost" name="shipping_cost" value="{{ old('shipping_cost') }}" required>
									<span class="m-form__help">Shipment cost from courier</span>
								</div>
							</div>
							<div id="source-container">
								<div class="form-group m-form__group row">
									<label for="source_order" class="col-sm-3">Source Order:</label>
									<div class="col-sm-7">
										<input type="file" name="source_order[]" class="form-control"/>
									</div>
									<div class="col-sm-2">&nbsp;</div>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<div class="col-sm-3">&nbsp;</div>
								<div class="col-sm-9">
									<button class="btn m-btn--pill btn-primary" type="button" id="add-source-btn"><i class="la la-plus"></i> Add Source</button>
								</div>
							</div>
						</div>
						<div class="col-sm-2"><!-- Barcode --></div>
					</div>
				</div>
				<div class="col-md-5 col-xs-6">
					<div class="form-group m-form__group m--margin-top-10">
						<div class="alert m-alert m-alert--default" role="alert"><i class="fa fa-cubes"></i> Order Basket</div>
					</div>
					<div class="form-group m-form__group row" style="padding-top:0;">
						<label for="product_id" class="col-sm-3">Product</label>
						<div class="col-sm-9">
							<select class="form-control m-select2" name="product_id" @if(Auth::user()->roles != 'client'){{'disabled'}}@endif id="product_id">
								<option value="">-- Please select product --</option>
								@if(Auth::user()->roles == 'client')
									@foreach($products as $product)
										<option value="{{ $product->product_id }}|{{ $product->inbound_detail_id }}|{{ $product->inbound_location_id }}|{{ $product->name }}|{{ $product->color }}|{{ $product->size_name }}|{{ $product->qty }}|{{ $product->product_type_size_id }}">{{ $product->name }} - {{ $product->color }} / {{ $product->size_name }} / {{ $product->qty }}</option>
									@endforeach
								@endif
							</select>
							<span class="m-form__help">Select client and product to initiate</span>
						</div>
					</div>
					<div class="form-group m-form__group">
						<button type="button" class="btn btn-primary pull-right" id="add-basket"><i class="flaticon-cart"></i> Add to Basket</button>
					</div>
					<div class="clearfix"></div>
					<br>
					<table class="table" id="order-list">
						<tbody></tbody>
					</table>
					<table class="table">
						<tbody>
							<tr>
								<td width="70%">Order pricing:</td>
								<td width="30%">IDR <span id="order-pricing">0</span></td>
							</tr>
							<tr>
								<td width="70%">Total:</td>
								<td width="30%">IDR <span id="total-pricing">0</span></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="button" class="btn btn-success" id="save-order">Save Order</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<script>
	$(function(){
		$('.m-select2').select2();

		$('#client_id').on('typeahead:selected', function(evt, client) {
		    if(client != ''){

		    	$('#order-list tbody').html('');
		    	$('#order-pricing, #total-pricing').html(0);
				$('#order_pricing').val(0);
				products = [];

				$.ajax({
					method:"post",
					url:"{{ url('order/ajax/product') }}",
					data:{
						"_token":"{{ csrf_token() }}",
						"client":client
					},
					complete:function(xhr){
						if(xhr.status === 200)
						{
							var json = $.parseJSON(xhr.responseText),
								html = '<option value="">-- Please select product --</option>';
							if(json.code == '00'){
								selectedClient = json.data.client;
								$.each(json.data.product,function(i,v){
									html += '<option value="'+v.product_id+'|'+v.inbound_detail_id+'|'+v.inbound_location_id+'|'+v.name+'|'+v.color+'|'+v.size_name+'|'+v.qty+'|'+v.product_type_size_id+'">'+v.name+' - '+v.color+' / '+v.size_name+' / '+v.qty+'</option>';
								});
								$('#product_id').attr('disabled',false).html(html);
							} else {
								alert(json.message);
							}
						}
						else
						{
							$('#product_id').html('<option value="">-- Please select product --</option>').attr('disabled',true);
							alert('Unable to load from server.');
						}
					}
				})
			} else {
				$('#product_id').html('<option value="">-- Please select product --</option>').attr('disabled',true);
			}
		});

		$('#add-basket').click(function(){

			productDetail = $('#product_id').val();
			if(productDetail != ""){
				$.ajax({
					method:"post",
					url:"{{ url('order/ajax/warehouse') }}",
					data:{
						"_token":"{{ csrf_token() }}",
						"inbound_detail":productDetail
					},
					complete:function(xhr){
						if(xhr.status === 200)
						{
							var json = $.parseJSON(xhr.responseText);
							if(json.code == '00')
							{
								if(json.data.length > 1){

									var html = '';
									$.each(json.data,function(i,v){
										html += '<button type="button" class="btn btn-info btn-warehouse" data-id="'+v.id+'" data-qty="'+v.qty+'">'+v.name+'</button>';
									});

									$('#warehouse-modal').find('.modal-body').html(html).end().modal({
										backdrop: 'static',
						    			keyboard: false
									});

								} else if (json.data.length == 1) {

									var product = productDetail.split("|");
									if(products.indexOf(product[0]+'|'+product[1]+'|'+product[2]+'|'+json.data[0].id+'|'+product[7]) !== -1){
										alert('This product has been added to cart.');
									} else if (product[6] == 0) {
										alert('This product is sold out!');
									} else {
										$('<tr data-id="'+product[0]+'|'+product[1]+'|'+product[2]+'|'+json.data[0].id+'|'+product[7]+'"><td width="10%" align="center"><a href="#" data-id="'+product[0]+'|'+product[1]+'|'+product[2]+'|'+json.data[0].id+'|'+product[7]+'" class="delete-product"><i class="fa fa-trash"></i></a></td><td width="50%"><div class="show">'+product[3]+' - '+product[4]+' - '+product[5]+'</div><div class="show">Warehouse: '+json.data[0].name+'<input type="hidden" name="warehouse[]" value="'+json.data[0].id+'"/></div></td><td width="40%"><div class="input-group"><input type="number" min="0" class="form-control m-input qty-details" placeholder="0" value="0" name="products[]"><input type="hidden" name="actuals[]" value="'+json.data[0].qty+'"/><div class="input-group-append"><span class="input-group-text"> / '+json.data[0].qty+'</span></div></div></td></tr>').find('.delete-product').on('click',function(){
											if(confirm('Are you sure to delete this product from basket?')){
												var id = $(this).attr('data-id');
												products.splice(products.indexOf(id),1);
												$(this).closest('tr').remove();

												totalQty = 0;
												$('.qty-details').each(function(i,v){
													totalQty += parseInt($(v).val());
												});

												if(totalQty <= selectedClient.pricing_order_less){
													pricePcs = parseInt(selectedClient.pricing_order);
												} else if (totalQty >= selectedClient.pricing_order_more) {
													pricePcs = parseInt(selectedClient.pricing_order_more_value);
												}

												$('#order-pricing, #total-pricing').html(totalQty * pricePcs);
												$('#order_pricing').val(totalQty * pricePcs);
											}
										}).end().find('.qty-details').change(function(){
											totalQty = 0;
											$('.qty-details').each(function(i,v){
												totalQty += parseInt($(v).val());
											});

											if(totalQty <= selectedClient.pricing_order_less){
												pricePcs = parseInt(selectedClient.pricing_order);
											} else if (totalQty >= selectedClient.pricing_order_more) {
												pricePcs = parseInt(selectedClient.pricing_order_more_value);
											}

											$('#order-pricing, #total-pricing').html(totalQty * pricePcs);
											$('#order_pricing').val(totalQty * pricePcs);

										}).end().appendTo('#order-list tbody');
										products.push(product[0]+'|'+product[1]+'|'+product[2]+'|'+json.data[0].id+'|'+product[7]);
									}
								} else {
									alert('This product of this variant is out of stock.');
								}
							}
							else
							{
								alert(json.message);
							}
						}
						else
						{
							alert('Unable to load from server.');
						}
					}
				})
			} else {
				alert('Please select product from list first.');
			}
			
		});

		$('body').bind('click',function(e){
			var $this = $(e.target);
			if($this.hasClass('btn-warehouse')){
				$('#warehouse-modal').find('.modal-body').html('').end().modal('hide');
				var product = productDetail.split("|"),
					wId = $this.attr('data-id'),
					wName = $this.html(),
					wQty = $this.attr('data-qty');
				if(products.indexOf(product[0]+'|'+product[1]+'|'+product[2]+'|'+wId+'|'+product[7]) !== -1){
					alert('This product has been added to cart.');
				} else if (product[6] == 0) {
					alert('This product is sold out!');
				} else {
					$('<tr data-id="'+product[0]+'|'+product[1]+'|'+product[2]+'|'+wId+'|'+product[7]+'"><td width="10%" align="center"><a href="#" data-id="'+product[0]+'|'+product[1]+'|'+product[2]+'|'+wId+'|'+product[7]+'" class="delete-product"><i class="fa fa-trash"></i></a></td><td width="50%"><div class="show">'+product[3]+' - '+product[4]+' - '+product[5]+'</div><div class="show">Warehouse: '+wName+'<input type="hidden" name="warehouse[]" value="'+wId+'"/></div></td><td width="40%"><div class="input-group"><input type="number" min="0" class="form-control m-input qty-details" placeholder="0" value="0" name="products[]"><input type="hidden" name="actuals[]" value="'+wQty+'"/><div class="input-group-append"><span class="input-group-text"> / '+wQty+'</span></div></div></td></tr>').find('.delete-product').on('click',function(){
						if(confirm('Are you sure to delete this product from basket?')){
							var id = $(this).attr('data-id');
							products.splice(products.indexOf(id),1);
							$(this).closest('tr').remove();

							totalQty = 0;
							$('.qty-details').each(function(i,v){
								totalQty += parseInt($(v).val());
							});

							if(totalQty <= selectedClient.pricing_order_less){
								pricePcs = parseInt(selectedClient.pricing_order);
							} else if (totalQty >= selectedClient.pricing_order_more) {
								pricePcs = parseInt(selectedClient.pricing_order_more_value);
							}

							$('#order-pricing, #total-pricing').html(totalQty * pricePcs);
							$('#order_pricing').val(totalQty * pricePcs);
						}
					}).end().find('.qty-details').change(function(){
						totalQty = 0;
						$('.qty-details').each(function(i,v){
							totalQty += parseInt($(v).val());
						});

						if(totalQty <= selectedClient.pricing_order_less){
							pricePcs = parseInt(selectedClient.pricing_order);
						} else if (totalQty >= selectedClient.pricing_order_more) {
							pricePcs = parseInt(selectedClient.pricing_order_more_value);
						}

						$('#order-pricing, #total-pricing').html(totalQty * pricePcs);
						$('#order_pricing').val(totalQty * pricePcs);
						
					}).end().appendTo('#order-list tbody');
					products.push(product[0]+'|'+product[1]+'|'+product[2]+'|'+wId+'|'+product[7]);
				}
			}
		});

		$('#save-order').click(function(){
			$('#order_details').val(JSON.stringify(products));
			$('#order-form').submit();
		});

		var substringMatcher = function(strs) {
		  return function findMatches(q, cb) {
		    var matches, substringRegex;

		    // an array that will be populated with substring matches
		    matches = [];

		    // regex used to determine if a string contains the substring `q`
		    substrRegex = new RegExp(q, 'i');

		    // iterate through the pool of strings and for any string that
		    // contains the substring `q`, add it to the `matches` array
		    $.each(strs, function(i, str) {
		      if (substrRegex.test(str)) {
		        matches.push(str);
		      }
		    });

		    cb(matches);
		  };
		};

		$('#client_id').typeahead({
		  hint: true,
		  highlight: true,
		  minLength: 1
		},
		{
		  name: 'client_id',
		  source: substringMatcher(clients)
		});

		$('#add-source-btn').click(function(){
			$('<div class="form-group m-form__group row">\
				<label for="source_order" class="col-sm-3">&nbsp;</label>\
				<div class="col-sm-7">\
					<input type="file" name="source_order[]" class="form-control"/>\
				</div>\
				<div class="col-sm-2">\
					<button class="btn m-btn--pill btn-danger remove-source" type="button"><i class="la la-times"></i> Remove</button>\
				</div>\
			</div>').find('.remove-source').click(function(){
				var $this = $(this);
				if(confirm('Are you sure to delete this source?')){
					$this.closest('.row').remove();
				}
			}).end().appendTo('#source-container');
		});
	});
</script>
@endsection