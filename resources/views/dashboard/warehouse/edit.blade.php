@extends('layouts.base',[
    'page' => 'Warehouse'
])

@section('content')
@if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('warehouse/delete').'/'.$warehouse->id.'?e=1' }}">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Warehouse</h5>
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
					Edit Warehouse
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
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<form action="{{ url('warehouse/bulk') }}" method="post" enctype="multipart/form-data" style="display:none;" id="bulk-form">
						{{ csrf_field() }}
						<input type="file" name="bulk-shelf" id="upload-bulk" accept=".xls,.xlsx"/>
						<input type="hidden" name="warehouse-id" value="{{ $warehouse->id }}"/>
					</form>
					<a href="#" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" id="bulk-btn">
						<span>
							<i class="la la-upload"></i>
							<span>Bulk Upload</span>
						</span>
					</a>
				</li>
				<li class="m-portlet__nav-item">
					<a href="{{ url('format/shelf-sample.xlsx') }}" class="btn btn-warning m-btn m-btn--custom m-btn--icon m-btn--air" download>
						<span>
							<i class="la la-download"></i>
							<span>Bulk Format</span>
						</span>
					</a>
				</li>
				@endif
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('warehouse/update').'/'.$warehouse->id }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="name">Code:</label>
						<input type="name" class="form-control m-input" value="{{ $warehouse->code }}" disabled>
						<span class="m-form__help">Warehouse generated code</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="name">Name:</label>
						<input type="name" class="form-control m-input" placeholder="Warehouse name" name="name" value="{{ $warehouse->name }}">
						<span class="m-form__help">Warehouse name</span>
					</div>
					<div class="col-sm-6">
						<label for="code">Warehouse Acronym:</label>
						<input type="acronym" class="form-control m-input" placeholder="Warehouse Acronym" name="acronym" value="{{ $warehouse->acronym }}" maxlength="3" id="warehouse_acronym">
						<span class="m-form__help">Please enter warehouse acronym with 3 characters maximum (Alphabet Only)</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="email">Warehouse address:</label>
						<textarea name="address" class="form-control m-input">{{ $warehouse->address }}</textarea>
						<span class="m-form__help">Warehouse address</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="zip">ZIP Code:</label>
						<input type="text" class="form-control m-input" placeholder="Zipcode" name="zip_code" value="{{ $warehouse->zip_code }}">
						<span class="m-form__help">Warehouse zip code</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="head_id">Warehouse Head:</label>
						<select class="form-control m-select2" name="head_id">
							<option value="">-- Pick warehouse head --</option>
							@foreach(\App\User::where('roles','head')->get() as $user)
								<option value="{{ $user->id }}" @if($user->id == $warehouse->head_id){{'selected'}}@endif>{{ $user->name }}</option>
							@endforeach
						</select>
						<span class="m-form__help">Person in charge of the warehouse</span>
					</div>
				</div>
			</div>
		</div>
		@if(Auth::user()->roles != 'investor')
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Save</button>
				@if(Auth::user()->roles != 'crew')
				<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#m_modal_1">Delete</button>
				@endif
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

	$('#bulk-btn').click(function(){
    	$('#upload-bulk').click();
    });

    $('#upload-bulk').change(function(){
    	$('#bulk-form').submit();
    })

	$('#warehouse_acronym').change(function() {
		$(this).val($(this).val().toUpperCase());
	});
</script>
@endsection