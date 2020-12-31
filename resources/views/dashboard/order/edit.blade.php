@extends('layouts.base',[
    'page' => 'Order'
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
					Edit Order
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					@if($ref == 'picked')
					<a href="{{ url('outbound') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<span>Ready to Pack</span>&nbsp;&nbsp;<i class="flaticon-bag"></i>
						</span>
					</a>
					@elseif($ref == 'waiting')
					<a href="{{ url('outbound/shipment') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<span>Await Shipment</span>&nbsp;&nbsp;<i class="flaticon-clock-1"></i>
						</span>
					</a>
					@elseif($ref == 'shipped')
					<a href="{{ url('outbound/done') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<span>Shipped</span>&nbsp;&nbsp;<i class="flaticon-paper-plane"></i>
						</span>
					</a>
					@else
					<a href="@if($restrict){{ url('order').'?r=0' }}@else{{ url('order') }}@endif" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<span>Ready to Outbound</span>&nbsp;&nbsp;<i class="flaticon-truck"></i>
						</span>
					</a>
					@endif
				</li>
				<li class="m-portlet__nav-item m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push" m-dropdown-toggle="hover">
					<a href="#" class="m-portlet__nav-link btn btn-primary m-btn m-btn--air m-btn--icon m-btn--icon-only m-btn--pill   m-dropdown__toggle">
						<i class="la la-ellipsis-v"></i>
					</a>
					<div class="m-dropdown__wrapper">
						<span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust"></span>
						<div class="m-dropdown__inner">
							<div class="m-dropdown__body">
								<div class="m-dropdown__content">
									<ul class="m-nav">
										<li class="m-nav__section m-nav__section--first">
											<span class="m-nav__section-text">Quick Actions</span>
										</li>
										<li class="m-nav__item">
											@if($restrict)
											<a href="{{ url('order/location').'/'.$order->id.'?r=0' }}@if($ref != null){{'?ref='.$ref}}@endif" class="m-nav__link">
												<i class="m-nav__link-icon la la-map-pin"></i>
												<span class="m-nav__link-text">View Location</span>
											</a>
											@else
											<a href="{{ url('order/location').'/'.$order->id }}@if($ref != null){{'?ref='.$ref}}@endif" class="m-nav__link">
												<i class="m-nav__link-icon la la-map-pin"></i>
												<span class="m-nav__link-text">View Location</span>
											</a>
											@endif
										</li>
										<li class="m-nav__item">
											@if($restrict)
											<a href="{{ url('order/barcode').'/'.$order->id.'?r=0' }}" class="m-nav__link">
												<i class="m-nav__link-icon la la-qrcode"></i>
												<span class="m-nav__link-text">Print Shipping Label</span>
											</a>
											@else
											<a href="{{ url('order/barcode').'/'.$order->id }}" class="m-nav__link">
												<i class="m-nav__link-icon la la-qrcode"></i>
												<span class="m-nav__link-text">Print Shipping Label</span>
											</a>
											@endif
										</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('order/update').'/'.$order->id }}" id="order-form" enctype="multipart/form-data">
		{{ csrf_field() }}
		<input type="hidden" value="" name="order_details" id="order_details"/>
		@if($restrict)<input type="hidden" name="restricted" value="true"/>@endif
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section row">
				<div class="col-md-7 col-xs-6">
					<div class="form-group m-form__group m--margin-top-10">
						<div class="alert m-alert m-alert--default" role="alert"><i class="fa fa-pencil-alt"></i> Fill in the order form</div>
					</div>
					<div class="row">
						<div class="col-sm-10">
							<!-- Left Form -->
							<div class="form-group m-form__group row">
								<label for="order_number" class="col-sm-3">Order Number</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="PKDYYMMDD###1" name="order_number" value="{{ $order->order_number }}" disabled>
									<span class="m-form__help">This is auto generated order number</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="order_type" class="col-sm-3">Order Type:</label>
								<div class="col-sm-9">
									<select class="form-control m-input m-input--square m-select2" name="order_type" disabled>
										@foreach(\App\Order::orderType() as $value => $display)
											<option value="{{ $value }}" @if($order->order_tyoe == $value){{'selected'}}@endif>{{ $display }}</option>
										@endforeach
									</select>
									<span class="m-form__help">Please select order type above</span>
								</div>
							</div>
							@if(Auth::user()->roles != 'client')
							<div class="form-group m-form__group row">
								<label for="client_id" class="col-sm-3">Client</label>
								<div class="col-sm-9">
									<select class="form-control m-select2" name="client_id" id="client_id" disabled>
										<option value="">-- Please select client --</option>
										@foreach($clients as $client)
											<option value="{{ $client->id }}" data-order="{{ $client->pricing_order }}" @if($order->client_id == $client->id){{'selected'}}@endif>{{ $client->name }}</option>
										@endforeach
									</select>
									<span class="m-form__help">Please select your client</span>
								</div>
							</div>
							@endif
							<div class="form-group m-form__group row">
								<label for="notes" class="col-sm-3">Notes</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Another extra points" name="notes" value="{{ $order->notes }}"/>
									<span class="m-form__help">Order extra notes</span>
								</div>
							</div>
							<div class="clearfix"></div>
							<br><br>
							<div class="m-form__seperator m-form__seperator--dashed"></div>
							<br>
							<div class="form-group m-form__group row">
								<label for="name" class="col-sm-3">Name</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer name" name="name" value="{{ $customer->name }}">
									<span class="m-form__help">Customer name</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="phone" class="col-sm-3">Phone</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer phone number" name="phone" value="{{ $customer->phone }}">
									<span class="m-form__help">Customer phone number</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="text" class="col-sm-3">Email</label>
								<div class="col-sm-9">
									<input type="email" class="form-control m-input" placeholder="Customer email address" name="email" value="{{ $customer->email }}">
									<span class="m-form__help">Customer email address</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="address" class="col-sm-3">Address</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer destination address" name="address" value="{{ $customer->address }}">
									<span class="m-form__help">Customer full address</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="zip_code" class="col-sm-3">Postcode</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Customer address postcode" name="zip_code" value="{{ $customer->zip_code }}">
									<span class="m-form__help">Customer destination address postcode</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="courier" class="col-sm-3">Courier Name</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Courier company name" name="courier" value="{{ $order->courier }}">
									<span class="m-form__help">Courier / Logictics company name</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="no_resi" class="col-sm-3">Receipt Number</label>
								<div class="col-sm-9">
									<input type="text" class="form-control m-input" placeholder="Delivery receipt number" name="no_resi" value="{{ $order->no_resi }}">
									<span class="m-form__help">Delivery receipt number from courier agent</span>
								</div>
							</div>
							<div class="form-group m-form__group row">
								<label for="shipping_cost" class="col-sm-3">Shipping Cost</label>
								<div class="col-sm-9">
									<input type="number" min="0" class="form-control m-input" placeholder="Shipment cost" name="shipping_cost" value="{{ $order->shipping_cost }}">
									<span class="m-form__help">Shipment cost from courier</span>
								</div>
							</div>
							@if($ref == 'waiting')
							<div class="form-group m-form__group row">
								<label for="is_shipped" class="col-sm-3">Shipment Status</label>
								<div class="col-sm-9">
									<div class="m-checkbox-list">
										<label class="m-checkbox">
											<input type="checkbox" name="is_shipped" value="done"> Done Shipping?
											<span></span>
										</label>
									</div>
									<span class="m-form__help">The readiness status order after shipment</span>
								</div>
							</div>
							@endif
							<div class="row">
								<div class="col-sm-3">
									<label for="source_order">Source Order:</label>
								</div>
								<div class="col-sm-9" id="source-container">
								@php
									$sources = \App\OrderSource::where('order_id',$order->id)->where('status',1)->get();
								@endphp
								@if(count($sources) > 0)
									@foreach($sources as $key => $source)
										<div class="form-group m-form__group row">
											<div class="col-sm-10">
												@if($source->source_order != null)
													<input type="hidden" name="old_source[]" value="{{ $source->id }}"/>
													@if(filter_var($source->source_order, FILTER_VALIDATE_URL) !== FALSE)
														<a class="btn m-btn--pill m-btn m-btn--gradient-from-info m-btn--gradient-to-accent m-full" href="{{ $source->source_order }}" target="_blank" style="display:block;"><i class="la la-paperclip"></i> View File</a>
													@else
														<a class="btn m-btn--pill m-btn m-btn--gradient-from-info m-btn--gradient-to-accent m-full" href="https://s3-ap-southeast-1.amazonaws.com/static-pakde/{{ urlencode($source->source_order) }}" target="_blank" style="display:block;"><i class="la la-paperclip"></i> View File</a>
													@endif
												@endif
											</div>
											<div class="col-sm-2">
												<button class="btn m-btn--pill btn-danger remove-source" type="button"><i class="la la-times"></i> Remove</button>
											</div>
										</div>
									@endforeach
								@else
									<div class="form-group m-form__group row">
										<div class="col-sm-10">
											<input type="file" name="source_order[]" class="form-control" />
										</div>
										<div class="col-sm-2">&nbsp;</div>
									</div>
								@endif
									<div class="form-group m-form__group row">
										<div class="col-sm-10">
											<button class="btn m-btn--pill btn-primary" type="button" id="add-source-btn"><i class="la la-plus"></i> Add Source</button>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-2">
							<!-- Barcode -->
							{{ QRCode::text($order->order_number)->svg() }}
						</div>
					</div>
				</div>
				<div class="col-md-5 col-xs-6">
					<div class="form-group m-form__group m--margin-top-10">
						<div class="alert m-alert m-alert--default" role="alert"><i class="fa fa-cubes"></i> Order Basket</div>
					</div>
					<table class="table" id="order-list">
						<tbody>
							@foreach($details as $key => $val)
							<tr data-id="{{ $val['product_id'] }}|{{ $key }}|{{ $val['product_location_id'] }}">
								<td width="60%">{{ $val['name'] }} - {{ $val['color'] }} - {{ $val['size'] }}</td>
								<td width="40%">
									<div class="input-group">
										<input type="number" min="0" max="{{ $val['total'] }}" class="form-control m-input" placeholder="0" value="{{ $val['count'] }}" disabled name="products[]">
										<div class="input-group-append"><span class="input-group-text"> / {{ $val['total'] - $val['pending'] }}</span></div>
									</div>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
					<table class="table">
						<tbody>
							<tr>
								<td width="70%">Order pricing:</td>
								<td width="30%">IDR <span id="order-pricing">{{ $order->client_pricing_order }}</span></td>
							</tr>
							<tr>
								<td width="70%">Total:</td>
								<td width="30%">IDR <span id="total-pricing">{{ $order->total }}</span></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		@if(Auth::user()->roles != 'investor')
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success" id="save-order">Save Order</button>
			</div>
		</div>
		@endif
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<script>
	$(function(){
		$('.m-select2').select2();

		$('#add-source-btn').click(function(){
			$('<div class="form-group m-form__group row">\
				<div class="col-sm-10">\
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
			}).end().prependTo('#source-container');
		});

		$('.remove-source').click(function(){
			var $this = $(this);
			if(confirm('Are you sure to delete this source? it will be removed temporarily.')){
				$this.closest('.row').remove();
			}
		});
	});
</script>
@endsection