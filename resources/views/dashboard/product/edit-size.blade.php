@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('productType/size').'/'.$size->product_type_id.'/delete/'.$size->id.'?e=1' }}">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Product Type Size</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this product type size?</p>
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
<!--begin::Portlet-->
<div class="m-portlet">

	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					Edit Product Type Size "{{ $size->type_name }}"
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('productType/size').'/'.$size->product_type_id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('productType/size').'/'.$size->product_type_id.'/update/'.$size->id }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="name">Product Type Size Name:</label>
					<input type="text" class="form-control m-input" name="name" value="{{ $size->name }}"/>
					<span class="m-form__help">The name of product type size</span>
				</div>
				<div class="form-group m-form__group">
					<label for="active">Product Type Size Status:</label>
					<select class="form-control m-input m-input--square m-select2" name="active">
						<option value="1" @if($size->active == 1){{'selected'}}@endif>Active</option>
						<option value="0" @if($size->active == 0){{'selected'}}@endif>Inactive</option>
					</select>
					<span class="m-form__help">Please select product type size status</span>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
				<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#m_modal_1">Delete</button>
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