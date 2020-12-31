@extends('layouts.base',[
    'page' => 'Warehouse'
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
					New Warehouse
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('warehouse') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('warehouse/create') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="name">Name:</label>
						<input type="name" class="form-control m-input" placeholder="Warehouse name" name="name" value="{{ old('name') }}">
						<span class="m-form__help">Please enter warehouse name</span>
					</div>
					<div class="col-sm-6">
						<label for="code">Warehouse Acronym:</label>
						<input type="acronym" class="form-control m-input" placeholder="Warehouse Acronym" name="acronym" value="{{ old('acronym') }}" maxlength="3" id="warehouse_acronym">
						<span class="m-form__help">Please enter warehouse acronym with 3 characters maximum (Alphabet Only)</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="email">Warehouse Address:</label>
						<textarea name="address" class="form-control m-input"></textarea>
						<span class="m-form__help">Please enter warehouse address</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="zip">ZIP Code:</label>
						<input type="text" class="form-control m-input" placeholder="Zipcode" name="zip_code" value="{{ old('zip_code') }}">
						<span class="m-form__help">Please enter warehouse zip code</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="head_id">Warehouse Head:</label>
						<select class="form-control m-select2" name="head_id">
							<option value="">-- Pick warehouse head --</option>
							@foreach(\App\User::where('roles','head')->get() as $user)
								<option value="{{ $user->id }}" @if($user->id == old('head_id')){{'selected'}}@endif>{{ $user->name }}</option>
							@endforeach
						</select>
						<span class="m-form__help">Person in charge of the warehouse</span>
					</div>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Warehouse</button>
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

	$('#warehouse_acronym').change(function() {
		$(this).val($(this).val().toUpperCase());
	});
</script>
@endsection