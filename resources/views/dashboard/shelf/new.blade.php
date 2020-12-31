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
					New Shelf
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="/shelf/{{ $rack->id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
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
	<form class="m-form" method="post" action="{{ url('shelf/create') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="name">Name:</label>
					<input type="name" class="form-control m-input" placeholder="Shelf name" name="name" >
					<span class="m-form__help">Please enter shelf name</span>

					<input type="hidden" name="rack_id" value="{{ $rack->id}}">
				</div>
				<div class="form-group m-form__group">
					<label for="name">Row:</label>
					<input type="name" class="form-control m-input" placeholder="Shelf row" name="row" >
					<span class="m-form__help">Please enter shelf row</span>
				</div>
				<div class="form-group m-form__group">
					<label for="name">Column:</label>
					<input type="name" class="form-control m-input" placeholder="Shelf column" name="col" >
					<span class="m-form__help">Please enter shelf code</span>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Add Shelf</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection