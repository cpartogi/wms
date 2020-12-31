@extends('layouts.base',[
    'page' => 'Product'
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
					Add New Product
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="@if($client_id != null){{ url('client/product/').'/'.$client_id.'/list' }}@else{{ url('product') }}@endif" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('product/create') }}" enctype="multipart/form-data">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				@php
					$client = \App\Client::find(Auth::user()->client_id);
				@endphp
				@if(Auth::user()->roles == 'client')
				<input type="hidden" name="client_id" value="{{ $client->id }}"/>
				@else
				<div class="form-group m-form__group">
					<label for="name">Client:</label>
					<select class="form-control m-input m-input--square m-select2" name="client_id">
						<option value="">-- Select client --</option>
						@foreach(\App\Client::all() as $client)
							<option value="{{ $client->id }}" @if($client->id == old('client_id') || ($client_id != null && $client->id == $client_id)){{'selected'}}@endif>{{ $client->name }}</option>
						@endforeach
					</select>
					<span class="m-form__help">Please select from listed clients</span>
				</div>
				<div class="m-form__seperator m-form__seperator--dashed m-form__seperator--space"></div>
				@endif
				<div class="form-group m-form__group">
					<label for="name">Product Type:</label>
					<select class="form-control m-input m-input--square m-select2" name="product_type_id">
						<option value="">-- Select product type --</option>
						@foreach(\App\ProductType::where('active',1)->get() as $type)
							<option value="{{ $type->id }}" @if($type->id == old('product_type_id')){{'selected'}}@endif>{{ $type->name }}</option>
						@endforeach
					</select>
					<span class="m-form__help">Please select from listed product type</span>
				</div>
				<div class="form-group m-form__group">
					<label for="name">Product Name:</label>
					<input type="text" class="form-control m-input" name="name" value="{{ old('name') }}"/>
					<span class="m-form__help">The name of product</span>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="product_price_sizing">Product Sizing:</label>
						<select class="form-control m-input m-input--square m-select2" name="product_price_sizing" required>
							<option value="S" @if(old('product_price_sizing') == 'S'){{'selected'}}@endif>S</option>
							<option value="M" @if(old('product_price_sizing') == 'M'){{'selected'}}@endif>M</option>
							<option value="L" @if(old('product_price_sizing') == 'L'){{'selected'}}@endif>L</option>
							<option value="XL" @if(old('product_price_sizing') == 'XL'){{'selected'}}@endif>XL</option>
						</select>
						<span class="m-form__help">Sizing level of product to categorize pricing</span>
					</div>
					<div class="col-sm-6">
						<label for="price">Product Price:</label>
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon2">Rp</span>
							</div>
							<input type="number" class="form-control m-input" name="price" value="{{ old('price') }}" required>
						</div>
						<span class="m-form__help">Price of this product variant</span>
					</div>
					
				</div>
				<div class="form-group m-form__group">
					<label for="color">Color:</label>
					<select class="form-control m-input m-input--square m-select2" name="color" required>
						<option value="">-- Select product color --</option>
						@foreach($colors as $key => $color)
							<optgroup label="{{ $key }}">
								@foreach($color as $v)
									@php
										$value = $key.(($key != $v)?' '.$v:'');
									@endphp
									<option value="{{ $value }}" @if(old('color') == $value){{'selected'}}@endif>{{ $value }}</option>
								@endforeach
							</optgroup>
						@endforeach
					</select>
					<span class="m-form__help">Please select color of the product.</span>
				</div>
				<div class="m-form__seperator m-form__seperator--dashed m-form__seperator--space"></div>
				<div class="row">
					<div class="form-group col-sm-6">
						<label for="weight">Product Weight:</label>
						<div class="input-group">
							<input type="text" class="form-control m-input" name="weight" value="{{ old('weight') }}"/>
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon2">Kg</span>
							</div>
						</div>
						<span class="m-form__help">The name of product</span>
					</div>
					<div class="form-group col-sm-6">
						<label for="dimension">Product Dimension ( W x H x D ):</label>
						<div class="row">
							<input type="number" min="0" class="form-control col-sm-4" name="dimension-w" placeholder="Width" value="{{ old('dimension-w') }}" />
							<input type="number" min="0" class="form-control col-sm-4" name="dimension-h" placeholder="Height" value="{{ old('dimension-h') }}"/>
							<input type="number" min="0" class="form-control col-sm-4" name="dimension-d" placeholder="Depth" value="{{ old('dimension-d') }}" />
						</div>
						<span class="m-form__help">The name of product</span>
					</div>
				</div>
				<div class="m-form__seperator m-form__seperator--dashed m-form__seperator--space"></div>
				<div class="form-group m-form__group">
					<label for="qc_point">QC Points:</label>
					<input type="text" class="form-control m-input" name="qc_point" value="{{ old('qc_point') }}"/>
					<span class="m-form__help">Quality check pinpoint</span>
				</div>
				<div class="form-group m-form__group">
					<label for="product_img">Main Product Image:</label>
					<img src="http://13.229.209.36:3006/assets/client-image.png" width="100"/>
					<input type="file" name="product_img" accept="image/*"/>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Product</button>
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
	});
</script>
@endsection