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
					List of Rack
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('warehouse').'/edit/'.$warehouse_id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Warehouse</span>
						</span>
					</a>
				</li>
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<a href="add/{{ $warehouse_id }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-plus"></i>
							<span>New Rack</span>
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
		<table class="table table-striped- table-bordered table-hover table-checkable" id="rack_table">
			<thead>
				<tr>
					<th width="5%">Rack ID</th>
					<th width="20%">Rack Code</th>
					<th width="50%">Rack Name</th>
					<th width="25%">Actions</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="10" class="dataTables_empty">Loading data from server</td>					
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th width="5%">Rack ID</th>
					<th width="20%">Rack Code</th>
					<th width="50%">Rack Name</th>
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
	(e = $("#rack_table").DataTable({
        responsive: !0,
        select: {
            style: "multi",
            selector: "td:first-child .m-checkable"
        },
        headerCallback: function(e, a, t, n, s) {
            e.getElementsByTagName("th")[0].innerHTML = '\n<label class="m-checkbox m-checkbox--single m-checkbox--solid m-checkbox--brand">\n<input type="checkbox" value="" class="m-group-checkable">\n                        <span></span>\n</label>'
        },
        ajax : { url: '/rack/list/{{ $warehouse_id}}', type: 'GET'},
        columns: [
        	{ data: 'id' },
            { data: 'code' },
            { data: "name" },
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