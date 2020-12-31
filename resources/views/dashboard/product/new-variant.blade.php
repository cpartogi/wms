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
					Add New Variant
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
	<form class="m-form" method="post" action="{{ url('product/variants').'/'.$product->id.'/create' }}" enctype="multipart/form-data">
		{{ csrf_field() }}
		<input type="hidden" name="product_id" value="{{ $product->id }}"/>
		<input type="hidden" name="product_type_id" value="{{ $product->product_type_id }}"/>
		<input type="hidden" name="client_id" value="{{ $product->client_id }}"/>
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first row">
				<div class="col-md-7 col-xs-6">
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
					<div class="m-form__seperator m-form__seperator--dashed"></div>
					<div class="form-group m-form__group">
						<label for="name">Variant Name:</label>
						<input type="text" class="form-control" name="name" value="{{ old('name') }}"/>
						<span class="m-form__help">Name of variant, try using the color name</span>
					</div>
					<div class="form-group m-form__group">
						<label for="color">Variant Color:</label>
						<select class="form-control m-input m-input--square m-select2" name="color">
							<option value="">-- Select color variant --</option>
							@foreach($colors as $key => $color)
								<optgroup label="{{ $key }}">
									@foreach($color as $v)
										<option value="{{ $key }} @if($key != $v){{ $v }}@endif">{{ $key }} @if($key != $v){{ $v }}@endif</option>
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
							<input type="number" class="form-control m-input" name="price" value="{{ old('price') }}">
						</div>
						<span class="m-form__help">Price of this product variant</span>
					</div>
					<div class="form-group m-form__group">
						<label for="tags">Variant Tags:</label>
						<br>
						<input type="text" class="form-control" name="tags" data-role="tagsinput" value="{{ old('tags') }}"/>
						<span class="m-form__help">Tags related to this product variant</span>
					</div>
					<div class="form-group m-form__group">
						<label for="description">Description:</label>
						<textarea class="form-control" name="description" data-provide="markdown" rows="10">{{ old('description') }}</textarea>
						<span class="m-form__help">Describe about variant of this product</span>
					</div>
				</div>
				<div class="col-md-5 col-xs-6">
					<div class="m-form__heading">
						<h3 class="m-form__heading-title"><i class="la la-sitemap"></i> Variant Size:</h3>
					</div>
					<div class="form-group m-form__group">
						<label for="type_size">Type Size:</label>
						<select class="form-control m-select2" name="type_size" id="select-type">
							<option value="NUMERICAL" @if(old('type_size') == 'NUMERICAL'){{'selected'}}@endif>Numerical</option>
							<option value="ALPHABETIC" @if(old('type_size') == 'ALPHABETIC'){{'selected'}}@endif>Alphabetic</option>
						</select>
						<span class="m-form__help">Numerical (30,32,42,44) or Alphabetic (S,M,L,ALLSIZE)</span>
					</div>
					<div class="form-group m-form__group">
						<label for="product_type_size_id">Size Detail:</label>
						<div id="numeric-chart-holder" class="row">
							<div class="col">
								<input type="number" min="0" class="form-control" name="numeric-key" placeholder="Sizing" value="{{ old('numeric-key') }}"/>
							</div>
							<div class="col">
								<input type="number" min="0" class="form-control" name="numeric-value" placeholder="Amount" value="{{ old('numeric-value') }}" />
							</div>
						</div>
						<div id="alphabetic-chart-holder" class="row" style="display:none;">
							<div class="col-sm-6">
								<select class="form-control m-select2" name="alphabetic-key" style="width:100%;">
									@foreach(\App\Dimension::orderBy('ordering')->get() as $dimension)
										<option value="{{ $dimension->id }}|{{ $dimension->label }}" @if(old('alphabetic-key') == $dimension->id.'|'.$dimension->label){{'selected'}}@endif>{{ $dimension->label }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-sm-6">
								<input type="number" min="0" class="form-control" name="alphabetic-value" placeholder="Amount" value="{{ old('alphabetic-value') }}" />
							</div>
						</div>
						<span class="m-form__help">Numerical (30,32,42,44) or Alphabetic (S,M,L,ALLSIZE)</span>
					</div>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Variant</button>
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

		$('#select-type').change(function(){
			var type = $(this).val();
			if(type == 'NUMERICAL'){
				$('#numeric-chart-holder').css('display','flex').siblings('#alphabetic-chart-holder').css('display','none');
			}else if(type == 'ALPHABETIC'){
				$('#alphabetic-chart-holder').css('display','flex').siblings('#numeric-chart-holder').css('display','none');
			}
		});
	});
</script>
@endsection