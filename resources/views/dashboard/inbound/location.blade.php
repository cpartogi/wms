@extends('layouts.base',[
    'page' => 'Inbound'
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
					Product Details
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('inbound/edit').'/'.$inbound->batch_id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Details</span>
						</span>
					</a>
				</li>
				<li class="m-portlet__nav-item">
					<a 	href="	@if(is_null($inbound->template_url) || $inbound->template_url == '' || $inbound->diff_updated > 60)
									{{ url('inbound/barcode/bulk').'/'.$inbound->batch_id }}
								@else
									{{ url($inbound->template_url) }}
								@endif" 
						class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" id="bulk-btn">
						<span>
							<i class="la la-qrcode"></i>
							<span>Bulk Print</span>
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
					<label>Batch:</label>
					<input type="text" class="form-control m-input" readonly value="#{{ str_pad($inbound->batch_id,5,'0',STR_PAD_LEFT) }}"/>
				</div>
				<div class="col-lg-4">
					<label>Client:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $inbound->client_name }}"/>
				</div>
				<div class="col-lg-4">
					<label>Arrival Date:</label>
					<input type="text" class="form-control m-input" readonly value="{{ date('d M Y',strtotime($inbound->arrival_date)) }}"/>
				</div>
			</div>
			<div class="form-group m-form__group row">
				<div class="col-lg-6">
					<label>Receiver:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $inbound->receiver_name }}"/>
				</div>
				<div class="col-lg-6">
					<label>Status:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $inbound->status }}"/>
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
					Inbound Location
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav"></ul>
		</div>
	</div>

	<div class="m-portlet__body">
		@include('notif')

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="20%">Product</th>
					<th width="20%">Location</th>
					<th width="20%">Color</th>
					<th width="10%">Size</th>
					<th width="25%">Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($locations as $var)
				<tr>
					<td style="text-align:center;">&nbsp;</td>
					<td>{{ $var->product_name }}</td>
					<td>@if($var->shelf_name != null){{ $var->shelf_name }}@else{{'-'}}@endif</td>
					<td>{{ $var->color }}</td>
					<td>{{ $var->size_name }}</td>
					<td>
						<a href="{{ url('inbound/barcode/single').'/'.$var->id }}" class="btn btn-primary" target="_blank"><i class="fa fa-print"></i> Print Barcode</a>
					</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="20%">Code</th>
					<th width="20%">Location</th>
					<th width="20%">Color</th>
					<th width="10%">Size</th>
					<th width="25%">Actions</th>
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