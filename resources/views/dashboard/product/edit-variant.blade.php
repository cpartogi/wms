@extends('layouts.base',[
    'page' => 'Product'
])

@section('style')
<link href="{{ asset('css/bootstrap-tagsinput.css') }}" rel="stylesheet" type="text/css" />
<style>
.bootstrap-tagsinput {
    width: 100%;
}
.bootstrap-tagsinput .tag {
    background-color: #36a3f7;
    border-radius: 2px;
    padding: 2px 4px;
    font-weight: bold;
}
</style>
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
					Edit Variant
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('product/variants').'/'.$product->id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('product/variants').'/'.$product->id.'/update/'.$product->variant_id }}" enctype="multipart/form-data">
		{{ csrf_field() }}
		<input type="hidden" name="product_id" value="{{ $product->id }}"/>
		<input type="hidden" name="product_type_id" value="{{ $product->product_type_id }}"/>
		<input type="hidden" name="client_id" value="{{ $product->client_id }}"/>
		<div class="m-portlet__body">

			@include('notif')
			
			<div class="m-form__section m-form__section--first">
				<div class="m-form__heading">
					<h3 class="m-form__heading-title"><i class="fa fa-pencil-alt"></i> Fill in the variant form:</h3>
				</div>
				<div class="form-group m-form__group">
					<label for="client_id">Brand Name:</label>
					<input type="text" class="form-control" name="client_name" value="{{ $product->client_name }}" readonly />
					<span class="m-form__help">The client name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="product_name">Product Name:</label>
					<input type="text" class="form-control" name="product_name" value="{{ $product->product_name }}" readonly />
					<span class="m-form__help">Product name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="product_type_name">Product Type:</label>
					<input type="text" class="form-control" name="product_type_name" value="{{ $product->type_name }}" readonly />
					<span class="m-form__help">Type of the product</span>
				</div>
				<div class="form-group m-form__group">
					<label for="product_type_size">Variant Size:</label>
					<input type="text" class="form-control" name="product_type_size" value="{{ $product->size_name }}" readonly />
					<span class="m-form__help">The size label of variant</span>
				</div>
				<div class="m-form__seperator m-form__seperator--dashed"></div>
				<div class="form-group m-form__group">
					<label for="name">Variant Name:</label>
					<input type="text" class="form-control" name="name" value="{{ $product->variant_name }}"/>
					<span class="m-form__help">Name of variant, try using the color name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="color">Variant Color:</label>
					<input type="hidden" name="old_color" value="{{ $product->color }}"/>
					<select class="form-control m-input m-input--square m-select2" name="color">
						<option value="">-- Select color variant --</option>
						@foreach($colors as $key => $color)
							<optgroup label="{{ $key }}">
								@foreach($color as $v)
									@php
										$cval = $key.(($key != $v)?" ".$v:"");
									@endphp
									<option value="{{ $cval }}" @if($cval == $product->color){{'selected'}}@endif>{{ $key }} @if($key != $v){{ $v }}@endif</option>
								@endforeach
							</optgroup>
						@endforeach
					</select>
					<span class="m-form__help">Please select color of the variant.</span>
				</div>
				<div class="form-group m-form__group">
					<label for="price">Variant Price:</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text" id="basic-addon2">Rp</span>
						</div>
						<input type="number" class="form-control m-input" name="price" value="{{ $product->price }}">
					</div>
					<span class="m-form__help">Price of this product variant</span>
				</div>
				<div class="form-group m-form__group">
					<label for="tags">Variant Tags:</label>
					<br>
					<input type="text" class="form-control" name="tags" data-role="tagsinput" value="{{ $product->tags }}"/>
					<span class="m-form__help">Tags related to this product variant</span>
				</div>
				<div class="form-group m-form__group">
					<label for="description">Description:</label>
					<textarea class="form-control" name="description" data-provide="markdown" rows="10">{{ $product->description }}</textarea>
					<span class="m-form__help">Describe about variant of this product</span>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save Variant</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<script src="{{ asset('js/bootstrap-tagsinput.min.js') }}" type="text/javascript"></script>
<script>
	$(function(){
		$('.m-select2').select2();
	});
</script>
@endsection