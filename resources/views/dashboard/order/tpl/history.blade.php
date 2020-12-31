@extends('layouts.base',[
    'page' => 'Order'
])

@section('content')

<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Third Party Logistic Histories
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					@if($restrict)
					<a href="{{ url('order/edit/').'/'.$order->id.'?r=0' }}@if($ref != null){{'?ref='.$ref}}@endif" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Order</span>
						</span>
					</a>
					@else
					<a href="{{ url('order/edit/').'/'.$order->id }}@if($ref != null){{'?ref='.$ref}}@endif" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Order</span>
						</span>
					</a>
					@endif
				</li>
				<li class="m-portlet__nav-item">
					<a href="@if($restrict){{ url('order/tpl/history').'/'.$order->id.'?r=0' }}@else{{ url('order/tpl/history').'/'.$order->id }}@endif" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" id="bulk-btn">
						<span>
							<i class="la la-refresh"></i>
							<span>Refresh</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed">
		<div class="m-portlet__body">
			<div class="form-group m-form__group row">
				<div class="col-lg-4">
					<label>Order Number:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $order->order_number }}"/>
				</div>
				<div class="col-lg-4">
					<label>Client:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $order->name }}"/>
				</div>
				<div class="col-lg-4">
					<label>Order Type:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $order->order_type }}"/>
				</div>
			</div>
			<div class="form-group m-form__group row">
				<div class="col-lg-4">
					<label>Total Amount:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $order->total }}"/>
				</div>
				<div class="col-lg-4">
					<label>Courier:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $order->courier }}"/>
				</div>
				<div class="col-lg-4">
					<label>Receipt Number:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $order->no_resi }}"/>
				</div>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>

<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Trace & Track
				</h3>
			</div>
		</div>
	</div>

	<div class="m-portlet__body">

		@include('notif')

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th style="text-align:center;">&nbsp;</th>
					<th style="text-align:center;">No.</th>
					<th>WMS Status</th>
					<th>User ID</th>
					<th>Date Time</th>
					<th>Notes</th>
					<th>Manifested at</th>
					<th>Tracked at</th>
				</tr>
			</thead>
			<tbody>
				@foreach($histories as $key => $history)
				<tr>
					<td style="text-align:center;">&nbsp;</td>
					<td style="text-align:center;">{{ $key+1 }}</td>
					<td>{{ Config::get('constants.order_status.'.$history->status) }}</td>
					<td>{{ $history->name }}</td>
					<td>{{ $history->updated_at }}</td>
					<td>{{ $history->notes }}</td>
					<td>{{ $history->tracking_at }}</td>
					<td>{{ $history->created_at }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endsection

@section('style')
<link href="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<script src="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.js') }}" type="text/javascript"></script>
<script>
	var e;
	(e = $("#m_table_1").DataTable({
        responsive: !0,
        select: {
            style: "multi",
            selector: "td:first-child .m-checkable"
        },
        headerCallback: function(e, a, t, n, s) {
            e.getElementsByTagName("th")[0].innerHTML = '\n<label class="m-checkbox m-checkbox--single m-checkbox--solid m-checkbox--brand">\n<input type="checkbox" value="" class="m-group-checkable">\n                        <span></span>\n</label>'
        },
        columnDefs: [{
            targets: 0,
            orderable: !1,
            render: function(e, a, t, n) {
                return '\n<label class="m-checkbox m-checkbox--single m-checkbox--solid m-checkbox--brand">\n<input type="checkbox" value="" class="m-checkable">\n<span></span>\n</label>'
            }
        }],
        dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
        buttons: ["print", "copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });
</script>
<!--end::Page Resources -->
@endsection