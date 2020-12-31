@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Product Type Size</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this Product Type Size?</p>
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
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					List of Product Type Size "{{ $size->name }}"
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('productType') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Product Type</span>
						</span>
					</a>
				</li>
				<li class="m-portlet__nav-item">
					<a href="{{ url('productType/size').'/'.$type_id.'/add' }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-plus"></i>
							<span>New Product Type Size</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>
	<div class="m-portlet__body">
		@include('notif')
		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="10%" style="text-align:center;">ID</th>
					<th width="50%">Name</th>
					<th width="25%">Status</th>
					<th width="15%">Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($sizes as $key => $size)
				<tr data-id="{{ $size->id }}">
					<td style="text-align:center;">{{ $size->id }}</td>
					<td>{{ $size->name }}</td>
					<td>@if($size->active == 1){{'Active'}}@else{{'Inactive'}}@endif</td>
					<td>
						<div class="dropdown">
							<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								Action
							</button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item" href="{{ url('productType/size/').'/'.$size->product_type_id.'/edit/'.$size->id }}">Edit</a>
								<a class="dropdown-item delete-btn" size-id="{{ $size->id }}" type-id="{{ $size->product_type_id }}">Delete</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th width="10%" style="text-align:center;">ID</th>
					<th width="50%">Name</th>
					<th width="25%">Status</th>
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

    $('.delete-btn').click(function(){
    	var $this = $(this);
    	$('#m_modal_1').find('form').attr('action','{{ url("/") }}/productType/size/'+$this.attr('type-id')+'/delete/'+$this.attr('size-id')).end().modal('show');

    });
</script>
<!--end::Page Resources -->
@endsection