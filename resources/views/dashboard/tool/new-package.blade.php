@extends('layouts.base',[
    'page' => 'Tools'
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
					New Package
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
	<form class="m-form" method="post" action="{{ url('package/create') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="name">Name:</label>
					<input type="name" class="form-control m-input" placeholder="Package name" name="name" value="{{ old('name') }}">
					<span class="m-form__help">Name of the package</span>
				</div>
				<div class="form-group m-form__group">
					<label for="barcode">Code:</label>
					<input type="text" class="form-control m-input" name="barcode" value="{{ generate_code(10) }}" readonly>
					<span class="m-form__help">Package generated barcode</span>
				</div>
				<div class="form-group m-form__group">
					<label for="price">Package Price:</label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text" id="basic-addon2">Rp</span>
						</div>
						<input type="number" class="form-control m-input" name="price" min="0" value="{{ old('price') }}" required>
					</div>
					<span class="m-form__help">Price of this package type</span>
				</div>
			</div>

		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Package</button>
			</div>
		</div>
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