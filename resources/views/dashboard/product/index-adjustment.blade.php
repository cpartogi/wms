@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Adjusted Products
				</h3>
			</div>
		</div>
	</div>
	<div class="m-portlet__body">

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="25%">Product</th>
					@if(Auth::user()->roles != 'client')
					<th width="10%">Client</th>
					<th width="10%">Size</th>
					<th width="15%">Batch</th>
					<th width="10%">Status</th>
					<th width="10%">Data Added</th>
					@else
					<th width="10%">Size</th>
					<th width="15%">Batch</th>
					<th width="15%">Status</th>
					<th width="15%">Data Added</th>
					@endif
					<th width="15%">Actions</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="10" class="dataTables_empty">Loading data from server</td>					
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="25%">Product</th>
					@if(Auth::user()->roles != 'client')
					<th width="10%">Client</th>
					<th width="10%">Size</th>
					<th width="15%">Batch</th>
					<th width="10%">Status</th>
					<th width="10%">Data Added</th>
					@else
					<th width="10%">Size</th>
					<th width="15%">Batch</th>
					<th width="15%">Status</th>
					<th width="15%">Data Added</th>
					@endif
					<th width="15%">Actions</th>
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
    var e,
    	modes = ["print", "copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"];
	(e = $("#m_table_1").DataTable({
        responsive: !0,
        processing: true,
        serverSide: true,
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
        ajax: {
        	url: "{{ route('adjustment-list') }}",
        	dataType: "json",
        	type: "POST",
        	data: {_token: "{{ csrf_token() }}"}
        },
        columns: [
        	{ data: 'id' },
            { data: 'product' },
            @if(Auth::user()->roles != 'client'){ data: 'client' },@endif
            { data: 'size' },
            { data: 'batch' },
            { data: 'status' },
            { data: 'data_added' },
            { data: 'action' }
        ],
        dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
        buttons: modes
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