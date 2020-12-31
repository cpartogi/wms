@extends('layouts.base',[
    'page' => 'Warehouse'
])

@section('content')
<script>
	function printDiv(divName) {
	 var printContents = document.getElementById(divName).innerHTML;
	 var originalContents = document.body.innerHTML;

	 document.body.innerHTML = printContents;

	 window.print();

	 document.body.innerHTML = originalContents;
	}
</script>
@if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('shelf/delete').'/'.$shelf->id.'?e=1' }}">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Shelf</h5>
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
					Edit Shelf
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="/shelf/{{ $shelf->rack_id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
						</span>
					</a>
				</li>
				<li class="m-portlet__nav-item">
					<a href="{{ url('shelf/barcode/single').'/'.$shelf->id }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-print"></i>
							<span>Print Barcode</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('shelf/update').'/'.$shelf->id }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group">
					<label for="name">Code:</label>
					<input type="name" class="form-control m-input" value="{{ $shelf->code }}" disabled>
					<span class="m-form__help">Shelf generated code</span>
				</div>
				<div class="form-group m-form__group">
					<label for="name">Name:</label>
					<input type="name" class="form-control m-input" name="name" value="{{ $shelf->name }}">
					<span class="m-form__help">Shelf name</span>
				</div>
				<div class="form-group m-form__group">
					<label for="row">Row:</label>
					<input type="text" class="form-control m-input" placeholder="Shelf row" name="row" value="{{ $shelf->row }}">
					<span class="m-form__help">Please enter shelf row</span>
				</div>
				<div class="form-group m-form__group">
					<label for="column">Column:</label>
					<input type="text" class="form-control m-input" placeholder="Shelf column" name="col" value="{{ $shelf->col }}">
					<span class="m-form__help">Please enter shelf code</span>
				</div>
				<div class="form-grooup m-form__group">
					<label for="column">Barcode:</label>
					<div id="barcode-area">{{ QRCode::text($shelf->code)->svg() }}</div>
					<span class="m-form__help">Please enter shelf code</span>
				</div>
				<input type="hidden" name="rack_id" value="{{ $shelf->rack_id }}">
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