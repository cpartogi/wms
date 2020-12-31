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
					New Product Type Size "{{ $size->name }}"
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('productType/size').'/'.$type_id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('productType/size').'/'.$type_id.'/create' }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="form-group m-form__group">
				<div class="form-group m-form__group">
					<label for="name">Product Type Size Name:</label>
					<input type="text" class="form-control m-input" name="name"/>
					<span class="m-form__help">The name of product size type</span>
				</div>
			</div>

		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Product Type Size</button>
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