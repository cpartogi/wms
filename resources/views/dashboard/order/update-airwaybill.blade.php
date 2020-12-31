@extends('layouts.base',[
    'page' => 'Order'
])

@section('modal')
        <!--begin::Modal-->
        <div class="modal fade" id="bulk-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ url('order/airwaybill/bulk') }}" method="post" enctype="multipart/form-data" id="bulk-form">
                    {{ csrf_field() }}
                    <input type="file" name="bulk-update-awb" id="upload-bulk" accept=".xls,.xlsx" style="display:none;"/>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Bulk Update Airwaybill</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="importing-btn">Start Importing</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Modal-->
    <!--begin::Modal-->
    <div class="modal fade" id="loading-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Loading</h5>
                </div>
                <div class="modal-body">
                    <p id="progress">Please wait, we are generating orders. Please be patient :)</p>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal-->
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
					Update Order Airwaybill
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('order') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
						</span>
					</a>
				</li>
                <li class="m-portlet__nav-item m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push" m-dropdown-toggle="hover">
                    <a href="#" class="m-portlet__nav-link btn btn-primary m-btn m-btn--air m-btn--icon m-btn--icon-only m-btn--pill   m-dropdown__toggle">
                        <i class="la la-ellipsis-v"></i>
                    </a>
                    <div class="m-dropdown__wrapper">
                        <span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust"></span>
                        <div class="m-dropdown__inner">
                            <div class="m-dropdown__body">
                                <div class="m-dropdown__content">
                                    <ul class="m-nav">
                                        <li class="m-nav__section m-nav__section--first">
                                            <span class="m-nav__section-text">Quick Actions</span>
                                        </li>
                                        <li class="m-nav__item">
                                            <a href="#" class="m-nav__link" id="bulk-btn">
                                                <i class="m-nav__link-icon la la-upload"></i>
                                                <span class="m-nav__link-text">Bulk Upload</span>
                                            </a>
                                        </li>
                                        <li class="m-nav__item">
                                            <a href="{{ url('format/update-awb-sample.xlsx') }}" class="m-nav__link">
                                                <i class="m-nav__link-icon la la-download"></i>
                                                <span class="m-nav__link-text">Bulk Format</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form" method="post" action="{{ url('order/airwaybill/update') }}">
		{{ csrf_field() }}
		<div class="m-portlet__body">

			@include('notif')

			<div class="m-form__section m-form__section--first">
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="order_number">Pakde Order Number:</label>
						<input type="order_number" class="form-control m-input" placeholder="Pakde order number" name="order_num" value="{{ old('order_num') }}">
						<span class="m-form__help">Please enter Pakde order number</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="logistic_name">Logistic name:</label>
						<input type="logistic_name" class="form-control m-input" placeholder="Logistic name" name="logistic_name" value="{{ old('logistic_name') }}">
						<span class="m-form__help">Please enter logistic name</span>
					</div>
				</div>
				<div class="form-group m-form__group row">
					<div class="col-sm-6">
						<label for="airwaybill_number">Airwaybill Number:</label>
						<input type="airwaybill_number" class="form-control m-input" placeholder="Airwaybill number" name="awb_num" value="{{ old('awb_num') }}">
						<span class="m-form__help">Please enter airwaybill number</span>
					</div>
				</div>
			</div>
		</div>
		<div class="m-portlet__foot m-portlet__foot--fit">
			<div class="m-form__actions m-form__actions">
				<button type="submit" class="btn btn-success">Update Airwaybill</button>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>
<!--end::Portlet-->
@endsection

@section('script')
	<script>
		$('#bulk-btn').click(function () {
			$('#upload-bulk').click();
		});

        $('#upload-bulk').change(function () {
            $('#bulk-modal').modal('show');
        });

        $('#importing-btn').click(function () {
            $('#bulk-modal').modal('hide');
            $('#loading-modal').modal({
                backdrop: 'static',
                keyboard: false
            });
            $('#bulk-form').submit();
            setTimeout(() => {
                        $('#loading-modal').modal('hide');
                        $("#progress").text('');
                    }, 2500);
        });

        $('#bulk-modal').on('hidden.bs.modal', function (e) {
            $("#upload-bulk").val('');
        });
	</script>
@endsection
