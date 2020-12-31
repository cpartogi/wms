@extends('layouts.base',[
    'page' => 'Warehouse'
])

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="clearfix"></div>
	<script type="text/javascript">
	    
	</script>
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					List of Warehouse
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<a href="warehouse/add" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-plus"></i>
							<span>New Warehouse</span>
						</span>
					</a>
				</li>
				@endif
			</ul>
		</div>
	</div>
	<div class="m-portlet__body">

		@include('notif')

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="warehouse_table">
			<thead>
				<tr>
					<th width="5%">Warehouse ID</th>
					<th width="20%">Warehouse Name</th>
					<th width="30%">Address</th>
					<th width="10%">ZIP</th>
					<th width="20%">Head</th>
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
					<th width="5%">Warehouse ID</th>
					<th width="20%">Warehouse Name</th>
					<th width="30%">Address</th>
					<th width="10%">ZIP</th>
					<th width="20%">Head</th>
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
	var e;
	(e = $("#warehouse_table").DataTable({
        responsive: !0,
        select: {
            style: "multi",
            selector: "td:first-child .m-checkable"
        },
        headerCallback: function(e, a, t, n, s) {
            e.getElementsByTagName("th")[0].innerHTML = '\n<label class="m-checkbox m-checkbox--single m-checkbox--solid m-checkbox--brand">\n<input type="checkbox" value="" class="m-group-checkable">\n                        <span></span>\n</label>'
        },
        ajax : { url: '{{route("warehouse-list")}}', type: 'GET'},
        columns: [
        	{ data: 'id'},
            { data: "name" },
            { data: "address" },
            { data: "zip_code", name: 'zip' },
            { data: "head" },
            { data: "Actions" }
        ],
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