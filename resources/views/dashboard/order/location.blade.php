@extends('layouts.base',[
    'page' => 'Order'
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
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Order Details
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
					Product Location
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
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="20%">Code</th>
					<th width="20%">Product</th>
					<th width="10%">Color</th>
					<th width="20%">Location</th>
					<th width="15%">Picked</th>
					<th width="15%">Outbounded</th>
				</tr>
			</thead>
			<tbody>
				@foreach($locations as $var)
				<tr>
					<td style="text-align:center;">&nbsp;</td>
					<td>{{ $var->code }}</td>
					<td>{{ $var->product_name }} ({{ $var->size_name }})</td>
					<td>{{ $var->color }}</td>
					<td>@if($var->shelf_name != null){{ $var->shelf_name }}@else{{'-'}}@endif<br>@if($var->warehouse_name != null){{ $var->warehouse_name }}@else{{'-'}}@endif</td>
					<td>@if($var->date_picked != null){{ date('d M Y H:i',strtotime($var->date_picked)) }}@else{{'-'}}@endif</td>
					<td>@if($var->date_outbounded != null){{ date('d M Y H:i',strtotime($var->date_outbounded)) }}@else{{'-'}}@endif</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="20%">Code</th>
					<th width="20%">Product</th>
					<th width="10%">Color</th>
					<th width="20%">Location</th>
					<th width="15%">Picked</th>
					<th width="15%">Outbounded</th>
				</tr>
			</tfoot>
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