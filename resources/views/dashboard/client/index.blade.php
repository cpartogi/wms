@extends('layouts.base',[
    'page' => 'Client'
])

@section('content')
@if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Client</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this client?</p>
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
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					List of Client
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<a href="{{ url('client/add') }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-plus"></i>
							<span>New Client</span>
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
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="10%">&nbsp;</th>
					<th width="20%">Name</th>
					<th width="20%">Email</th>
					<th width="25%">Products / Variants</th>
					<th width="20%">Actions</th>
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
					<th width="10%">&nbsp;</th>
					<th width="20%">Name</th>
					<th width="20%">Email</th>
					<th width="25%">Products / Variants</th>
					<th width="20%">Actions</th>
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
        ajax : { url: '{{route("client-list")}}', type: 'GET'},
        columns: [
        	{ data: '' },
        	{
                data: "image_url",
                render: function ( url ) {
                    return '<img src="'+url+'" width="50"/>';
                },
                defaultContent: "No image",
                title: "Image"
            },
            { data: 'name' },
            { data: 'email' },
            { data: 'products' } ,
            { data: 'action' }
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

    $('.delete-btn').click(function(){
    	var $this = $(this);
    	$('#m_modal_1').find('form').attr('action','/client/delete/'+$this.attr('data-id')).end().modal('show');

    });
</script>
<!--end::Page Resources -->
@endsection