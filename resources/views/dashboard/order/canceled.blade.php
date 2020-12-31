@extends('layouts.base',[
    'page' => 'Order'
])

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					<div class="btn-group">
						<button type="button" class="dropdown-toggle btn m-btn--pill m-btn--air m-btn m-btn--gradient-from-primary m-btn--gradient-to-info" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							Canceled
						</button>
						<div class="dropdown-menu">
							<h6 class="dropdown-header">Order Status</h6>
							<a class="dropdown-item" href="{{ url('order') }}">All Order</a>
							<a class="dropdown-item" href="{{ url('order/ready') }}">Ready to Outbound</a>
							<a class="dropdown-item" href="{{ url('outbound') }}">Ready to Pack</a>
							<a class="dropdown-item" href="{{ url('outbound/shipment') }}">Await Shipment</a>
							<a class="dropdown-item" href="{{ url('outbound/done') }}">Shipped</a>
						</div>
					</div>
				</h3>
			</div>
		</div>
	</div>
	<div class="m-portlet__body">

		@include('notif')

		<form action="{{ url('order/barcode') }}" id="orders-print" method="post">
			{{ csrf_field() }}
			<input type="hidden" id="n" name="n" />
		</form>

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="15%">Order Number</th>
					<th width="15%">Order Type</th>
					<th width="20%">Client</th>
					<th width="20%">Customer</th>
					<th width="10%">Date</th>
					<th width="15%">Actions</th>
				</tr>
			</thead>
			<tbody>
				@php
					$pretty = \App\Order::orderType();
				@endphp
				@foreach($orders as $order)
				<tr>
					<td style="text-align:center;">{{ $order->id }}</td>
					<td>{{ $order->order_number }}</td>
					<td>{{ $pretty[$order->order_type] }}</td>
					<td>{{ $order->client_name }}</td>
					<td>{{ $order->customer_name }}</td>
					<td>{{ date('d-M-Y H:i',strtotime($order->created_at)) }}</td>
					<td>
						<div class="dropdown">
							<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								Action
							</button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item" href="{{ url('order/edit').'/'.$order->id }}">Edit</a>
								<a class="dropdown-item" href="{{ url('order/location').'/'.$order->id }}">View Location</a>
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
					<th width="15%">Order Type</th>
					<th width="20%">Client</th>
					<th width="20%">Customer</th>
					<th width="10%">Date</th>
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
        buttons: ["print", "excelHtml5", "csvHtml5", {
            	text: 'Delete',
            	action: function ( x, dt, node, config ) {
                    deleteBulk();
                }
            }]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });

	function deleteBulk()
	{
		var data = e.rows( { selected: true } ).data(),
        	ids = [];

        $.each(data, function(i,v){
        	ids.push(v[0]);
        });

        $('input[name="order_id"]').val(ids.join(',')).closest('#m_modal_1').modal('show');
	}

</script>
<!--end::Page Resources -->
@endsection