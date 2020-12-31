@extends('layouts.base',[
    'page' => 'User'
])

@section('content')
@if(Auth::user()->roles != 'investor' && Auth::user()->roles != 'crew')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete User</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this user?</p>
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
					List of Users
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<a href="{{ url('user/add') }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-user-plus"></i>
							<span>New User</span>
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
					<th style="text-align:center;" width="5%">&nbsp;</th>
					<th width="15%">ID</th>
					<th width="20%">Name</th>
					<th width="20%">Phone</th>
					@if(Auth::user()->roles == 'client')
						<th width="20%">Email</th>
						<th width="10%">Status</th>
						<th width="10%">Actions</th>
					@else
						<th width="10%">Email</th>
						<th width="10%">Role</th>
						<th width="10%">Status</th>
						<th width="10%">Actions</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@foreach($users as $user)
				<tr>
					<td style="text-align:center;">&nbsp;</td>
					<td>{{ $user->id }}</td>
					<td>{{ $user->name }}</td>
					<td>{{ $user->phone }}</td>
					<td>{{ $user->email }}</td>
					@if(Auth::user()->roles != 'client')
					<td>{{ $user->roles }} @if($user->client_name != null)({{ $user->client_name }})@endif</td>
					@endif
					<td>{{ $user->status == 'A' ? 'Active' : 'Inactive' }}</td>
					<td>
						<div class="dropdown">
							<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								Action
							</button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item" href="{{ url('user/edit').'/'.$user->id }}">Edit</a>
								@if(Auth::user()->roles != 'investor' && Auth::user()->roles != 'crew')
								<a class="dropdown-item delete-btn" data-id="{{ $user->id }}">Delete</a>
								@endif
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th style="text-align:center;" width="5%">&nbsp;</th>
					<th width="15%">ID</th>
					<th width="20%">Name</th>
					@if(Auth::user()->roles == 'client')
						<th width="30%">Email</th>
						<th width="30%">Actions</th>
					@else
						<th width="20%">Email</th>
						<th width="20%">Role</th>
						<th width="20%">Actions</th>
					@endif
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
        }]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });

    $('.delete-btn').click(function(){
    	var $this = $(this);
    	$('#m_modal_1').find('form').attr('action','/user/delete/'+$this.attr('data-id')).end().modal('show');

    });
</script>
<!--end::Page Resources -->
@endsection