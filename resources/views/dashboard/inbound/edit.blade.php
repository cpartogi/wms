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
					Edit Inbound
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
				<li class="m-portlet__nav-item">
					<a href="{{ url('inbound/location').'/'.$batch->id }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" id="bulk-btn">
						<span>
							<i class="la la-map-pin"></i>
							<span>View Location</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('inbound/update').'/'.$batch->id}}">
		{{ csrf_field() }}
		<div class="m-portlet__body">
			@include('notif')
			<div id="client_edit_form" class="m-form__section m-form__section--first">
				@if(Auth::user()->roles == 'client')
					<input type="hidden" name="client_id" value="{{ $client->id }}" />
					<input type="hidden" name="status" value="{{ $batch->status }}"/>
				@else
					<div class="form-group m-form__group row">
						<input type="hidden" name="client_id" value="{{ $client->id }}" />
						<div class="col-sm-6">
							<label for="client">Client:</label>
							<input type="text" class="form-control m-input" name="name" value="{{ $client->name }}" readonly="">
						</div>
						<div class="col-sm-6">
							<label for="status">Status:</label>
							<select class="form-control m-select2" name="status">
								<option value="REGISTER" @if($batch->status == 'REGISTER'){{'selected'}}@endif>Register</option>
								<option value="RETURN" @if($batch->status == 'RETURN'){{'selected'}}@endif>Return</option>
								<option value="EVENT" @if($batch->status == 'EVENT'){{'selected'}}@endif>Event</option>
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
						<input type="text" class="form-control m-input" name="notes" value="{{ $batch->notes }}">
						<span class="m-form__help">Extra information for this inbound batch</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-4">
						<label for="courier">Courier:</label>
						<input type="text" class="form-control m-input" name="courier" value="{{ $batch->courier }}" />
						<span class="m-form__help">The courier agent who sent the package</span>
					</div>
					<div class="col-sm-4">
						<label for="sender_name">Sender Name:</label>
						<input type="text" class="form-control m-input" name="sender_name" value="{{ $batch->sender_name }}">
						<span class="m-form__help">Person who send the package</span>
					</div>
					<div class="col-sm-4">
						<label for="shipping_cost">Courier Cost:</label>
						<input type="number" class="form-control m-input" name="shipping_cost" value="{{ $batch->shipping_cost }}" />
						<span class="m-form__help">Cost of the delivery</span>
					</div>
				</div>
				<table id="inbound_form" class="table table-bordered">
					<thead>
						<tr>
							<th width="35%">Product</th>
							<th width="30%">Color</th>
							<th width="35%">Size</th>
						</tr>
					</thead>
					<tbody>
						@foreach($datas as $key => $data)
						<tr>
							<td>
								<input type="hidden" name="inbound_id[{{$key}}]" value="{{ $data['inbound']->id }}"/>
								<input type="text" readonly class="form-control m-input" name="name[{{$key}}]" value="{{ $data['inbound']->name }}">
								<input type="hidden" class="form-control m-input" value="{{ $data['inbound']->product_id}}" name="product_id[{{$key}}]">
							</td>
							<td class="inbound_color">@if(isset($data['variance'][0]->color)){{ $data['variance'][0]->color }}@else{{'-'}}@endif</td>
							<td class="inbound_variance">
								@php
									$index = 0
								@endphp
								@foreach(\App\ProductTypeSize::where('product_type_id', $data['inbound']->product_type_id)->get() as $typesize)								
									<div class="col-auto">
										<div class="input-group m-input-group">
											<div class="input-group-prepend">
												<span class="input-group-text">{{ $typesize->name }}</span>
											</div>
											<input type="number" min="0" placeholder="0" class="form-control" name="stated_qty[{{$key}}][{{$index}}]" 
											@foreach($data['variance'] as $detail)@if($typesize->id == $detail->product_type_size_id) value="{{$detail->stated_qty}}"@endif
											@endforeach" readonly>
											<input type="hidden" name="product_type_size_id[{{$key}}][{{$index}}]" value="{{$typesize->id}}">
										</div>
									</div>
									@php ($index++)
								@endforeach
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
		@if(Auth::user()->roles != 'investor')
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
			</div>
		</div>
		@endif
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<script src="{{ asset('js/vendros/bootstrap-datetimepicker/bootstrap-datetimepicker.js') }}" type="text/javascript"></script>
<script>
	$(function() {
		$('.m-select2').select2();

		$('#arrival_date').datetimepicker({
			format: 'yyyy-mm-dd hh:ii',
			autoclose: true,
			todayHighlight: true,
		});
	});
</script>
@endsection