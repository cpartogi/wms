@extends('layouts.base',[
    'page' => 'Client'
])

@section('content')
@if(Auth::user()->roles != 'crew')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('client/delete').'/'.$client->id.'?e=1' }}">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Client</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this client?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Confirm</button>
				</div>
			</div>
		</form>
	</div>
</div>
<!--end::Modal-->
@endif
<!--begin::Portlet-->
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					Edit Client
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('client') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed" method="post" action="{{ url('client/update').'/'.$client->id }}" enctype="multipart/form-data">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="name">Client's Name:</label>
						<input type="text" class="form-control m-input" placeholder="Enter client name" name="name" value="{{ $client->name }}">
						<span class="m-form__help">Please enter your client name</span>
					</div>
					<div class="col-sm-6">
						<label for="name">Client's Acronym:</label>
						<input type="text" class="form-control m-input" placeholder="Enter client acronym" name="acronym" maxlength="5" value="{{ $client->acronym }}">
						<span class="m-form__help">Please enter the short name of client name</span>
					</div>
				</div>
				<div class="form-group m-form__group">
					<label for="email">Email address:</label>
					<input type="email" class="form-control m-input" placeholder="Enter email" name="email" value="{{ $client->email }}">
					<span class="m-form__help">We'll never share your email with anyone else</span>
				</div>
				<div class="form-group m-form__group">
					<label for="warehouse_id">Warehouse</label>
					<select class="form-control m-input m-input--square m-select2" name="warehouse_id">
						@foreach(\App\Warehouse::all() as $warehouse)
							<option value="{{ $warehouse->id }}" @if($warehouse->id == $client->warehouse_id){{'selected'}}@endif>{{ $warehouse->name }}</option>
						@endforeach
					</select>
					<span class="m-form__help">Warehouse base of this user</span>
				</div>
				<div class="form-group m-form__group">
					<label for="mobile">Mobile:</label>
					<input type="number" class="form-control m-input" placeholder="Enter client phone number" name="mobile" value="{{ $client->mobile }}">
					<span class="m-form__help">Client mobile phone number</span>
				</div>
				<div class="form-group m-form__group">
					<label for="pic">PIC:</label>
					<input type="text" class="form-control m-input" placeholder="Enter pic name" name="pic" value="{{ $client->pic }}">
					<span class="m-form__help">Person in Charge of the client brand</span>
				</div>
				<div class="form-group m-form__group">
					<label for="address">Address:</label>
					<input type="text" class="form-control m-input" placeholder="Enter address name" name="address" value="{{ $client->address }}">
					<span class="m-form__help">Enter client address name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="zip_code">Postcode:</label>
					<input type="text" class="form-control m-input" placeholder="Enter postcode number" name="zip_code" value="{{ $client->zip_code }}">
					<span class="m-form__help">Postcode number of client address</span>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_qty_less">Inbound Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_qty_less" value="{{ $client->pricing_qty_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_qty">Inbound Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_qty" value="{{ $client->pricing_qty }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_qty_more">Inbound Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_qty_more" value="{{ $client->pricing_qty_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_qty_more_value">Inbound More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_qty_more_value" value="{{ $client->pricing_qty_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_small_item_less">Warehousing-S Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_small_item_less" value="{{ $client->pricing_small_item_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_small_item">Warehousing-S Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_small_item" value="{{ $client->pricing_small_item }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_small_item_more">Warehousing-S Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_small_item_more" value="{{ $client->pricing_small_item_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_small_item_more_value">Warehousing-S More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_small_item_more_value" value="{{ $client->pricing_small_item_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_medium_item_less">Warehousing-M Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_medium_item_less" value="{{ $client->pricing_medium_item_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_medium_item">Warehousing-M Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_medium_item" value="{{ $client->pricing_medium_item }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_medium_item_more">Warehousing-M Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_medium_item_more" value="{{ $client->pricing_medium_item_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_medium_item_more_value">Warehousing-M More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_medium_item_more_value" value="{{ $client->pricing_medium_item_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_large_item_less">Warehousing-L Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_large_item_less" value="{{ $client->pricing_large_item_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_large_item">Warehousing-L Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_large_item" value="{{ $client->pricing_large_item }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_large_item_more">Warehousing-L Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_large_item_more" value="{{ $client->pricing_large_item_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_large_item_more_value">Warehousing-L More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_large_item_more_value" value="{{ $client->pricing_large_item_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_extra_large_item_less">Warehousing-XL Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_extra_large_item_less" value="{{ $client->pricing_extra_large_item_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_extra_large_item">Warehousing-XL Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_extra_large_item" value="{{ $client->pricing_extra_large_item }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_extra_large_item_more">Warehousing-XL Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_extra_large_item_more" value="{{ $client->pricing_extra_large_item_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_extra_large_item_more_value">Warehousing-XL More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_extra_large_item_more_value" value="{{ $client->pricing_extra_large_item_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_order_less">Outbound Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_order_less" value="{{ $client->pricing_order_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_order">Outbound Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_order" value="{{ $client->pricing_order }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_order_more">Outbound Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_order_more" value="{{ $client->pricing_order_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_order_more_value">Outbound More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_order_more_value" value="{{ $client->pricing_order_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="pricing_event_less">Event Qty Less:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1"><</span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_event_less" value="{{ $client->pricing_event_less }}" placeholder="3000" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_event">Event Less Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_event" value="{{ $client->pricing_event }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_event_more">Event Qty More:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">></span>
							</div>
							<input type="number" class="form-control m-input" name="pricing_event_more" value="{{ $client->pricing_event_more }}" placeholder="3001" required="">
						</div>
					</div>
					<div class="col-sm-6">
						<label for="pricing_event_more_value">Event More Charge:</label>
						<div class="input-group m-input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Rp</span>
							</div>
							<input type="number" class="form-control m-input" placeholder="0" name="pricing_event_more_value" value="{{ $client->pricing_event_more_value }}" required="">
							<div class="input-group-append">
								<span class="input-group-text" id="basic-addon2">/ pcs</span>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group m-form__group">
					<label for="pricing_order">Client Logo:</label>
					@if($client->logo_url != null)
						<img src="https://s3-ap-southeast-1.amazonaws.com/static-pakde/{{ str_replace(' ','+',$client->logo_url) }}" width="100"/>
					@else
						<img src="http://13.229.209.36:3006/assets/client-image.png" width="100"/>
					@endif
					<input type="file" name="logo_url">
				</div>
			</div>
		</div>
		@if(Auth::user()->roles != 'investor')
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
				@if(Auth::user()->roles != 'crew')<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#m_modal_1">Delete</button>@endif
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
	});
</script>
@endsection