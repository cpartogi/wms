@extends('layouts.base',[
    'page' => 'Order'
])

@section('modal')
@if(Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Order</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete this Order? All related data to this order will also be deleted.</p>
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
@if(Auth::user()->roles != 'client' && Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="courier-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form action="{{ url('outbound/check/packing') }}" method="post">
			{{ csrf_field() }}
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Courier Check Packing</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group m-form__group">
						<label for="checking_date">Date:</label>
						<input type="text" class="form-control m-input m-datepicker" placeholder="Pick checking date" name="checking_date" required>
					</div>
					<div class="form-group m-form__group">
						<label for="courier">Courier:</label>
						<input type="text" class="form-control m-input" name="courier" required/>
						<span class="m-form__help">The courier agent who sent the package</span>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary" id="download-btn">Download Excel</button>
				</div>
			</div>
		</form>
	</div>
</div>
<!--end::Modal-->
@endif
@endsection

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					<div class="btn-group">
						<button type="button" class="dropdown-toggle btn m-btn--pill m-btn--air m-btn m-btn--gradient-from-primary m-btn--gradient-to-info" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							Await Shipment
						</button>
						<div class="dropdown-menu">
							<h6 class="dropdown-header">Order Status</h6>
							<a class="dropdown-item" href="{{ url('order') }}">All Order</a>
							<a class="dropdown-item" href="{{ url('order/ready') }}">Ready to Outbound</a>
							<a class="dropdown-item" href="{{ url('outbound') }}">Ready to Pack</a>
							<a class="dropdown-item" href="{{ url('outbound/done') }}">Shipped</a>
							<a class="dropdown-item" href="{{ url('order/canceled') }}">Canceled</a>
						</div>
					</div>
				</h3>
			</div>
		</div>
		@if(Auth::user()->roles != 'client')
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="#" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air" data-toggle="modal" data-target="#courier-modal">
						<span>
							<i class="la la-list-alt"></i>
							<span>Courier Check</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
		@endif
	</div>
	<div class="m-portlet__body">
		@include('notif')

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="15%">Order Number</th>
					<th width="25%">Client</th>
					<th width="25%">Customer</th>
					<th width="15%">Packed Items</th>
					<th width="15%">Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($orders as $order)
				<tr>
					<td style="text-align:center;">&nbsp;</td>
					<td>{{ $order->order_number }}</td>
					<td>{{ $order->client_name }}</td>
					<td>{{ $order->customer_name }}</td>
					<td>{{ $order->packed }} / {{ $order->total }}</td>
					<td>
						<div class="dropdown">
							<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								Action
							</button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item" href="{{ url('order/edit').'/'.$order->id.'?ref=waiting' }}">Edit</a>
								<a class="dropdown-item" href="{{ url('order/location').'/'.$order->id.'?ref=waiting' }}">View Items</a>
								<a class="dropdown-item" href="{{ url('outbound/print').'/'.$order->id }}">Print Delivery</a>
								@if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')<a class="dropdown-item delete-btn" data-id="{{ $order->id }}">Force Delete</a>@endif
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="15%">Order Number</th>
					<th width="25%">Client</th>
					<th width="25%">Customer</th>
					<th width="15%">Packed Items</th>
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
    	$('#m_modal_1').find('form').attr('action','/order/delete/'+$this.attr('data-id')).end().modal('show');

    });

    $('.m-datepicker').datepicker({format:'yyyy-mm-dd',endDate:'d',autoclose:true,todayHighlight: true});
</script>
<!--end::Page Resources -->
@endsection