@extends('layouts.base',[
    'page' => 'Tools'
])

@section('content')
@if(Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('package/delete').'/'.$package->id.'?e=1' }}">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Package</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this data?</p>
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
					Edit Package
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('package') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('package/update').'/'.$package->id }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="name">Name:</label>
					<input type="name" class="form-control m-input" placeholder="Package name" name="name" value="{{ $package->name }}">
					<span class="m-form__help">Name of the package</span>
				</div>
				<div class="form-group m-form__group">
					<label for="barcode">Code:</label>
					<input type="text" class="form-control m-input" name="barcode" value="{{ $package->barcode }}" disabled>
					<span class="m-form__help">Package generated barcode</span>
				</div>
				<div class="form-group m-form__group">
					<label for="price">Package Price:</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text" id="basic-addon2">Rp</span>
						</div>
						<input type="number" class="form-control m-input" name="price" value="{{ $package->price }}" min="0" required>
					</div>
					<span class="m-form__help">Price of this package type</span>
				</div>
			</div>
		</div>
		@if(Auth::user()->roles != 'investor')
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
				<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#m_modal_1">Delete</button>
			</div>
		</div>
		@endif
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
<script type="text/javascript">
	$(".m-select2").select2({ width: '100%' }); 
</script>
@endsection