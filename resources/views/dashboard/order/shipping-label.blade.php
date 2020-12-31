@extends('layouts.base',[
    'page' => 'Shipping Label'
])

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="clearfix"></div>
	<script type="text/javascript">
	    
	</script>
	<form id="bulk_download" method="post" action="{{ url('order/download/bulk') }}">
		{{ csrf_field() }}
		<input type="hidden" id="order_ids" name="order_ids" />
	</form>
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					List of Shipping Label
				</h3>
			</div>
		</div>
	</div>
	<div class="m-portlet__body">

		@include('notif')

		<div class="row m--margin-bottom-20">
			<div class="col-lg-6 m--margin-bottom-10-tablet-and-mobile">
				<label>Order Uploaded Date:</label>
				<div class="input-daterange input-group" id="m_datepicker">
					<input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-7 days')) }}" name="start" placeholder="From" data-col-index="5" />
					<div class="input-group-append">
						<span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
					</div>
					<input type="text" class="form-control m-input" name="end" value="{{ date('Y-m-d') }}" placeholder="To" data-col-index="5" />
				</div>
			</div>
			<div class="col-lg-6 m--margin-bottom-10-tablet-and-mobile">
				<label>Due Date:</label>
				<div class="input-daterange input-group" id="m_datepicker">
					<input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-7 days')) }}" name="start_due_date" placeholder="From" data-col-index="5" />
					<div class="input-group-append">
						<span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
					</div>
					<input type="text" class="form-control m-input" name="end_due_date" value="{{ date('Y-m-d', strtotime('+2 days', time())) }}" placeholder="To" data-col-index="5" />
				</div>
			</div>
		</div>
		<div class="row m--margin-bottom-20">
			<div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile">
				<label>Printed Status:</label>
				<select class="form-control m-input m-select2" name="printed-status" data-col-index="2">
						<option value="1">Printed</option>
						<option value="0" selected>Not Printed</option>
				</select>
			</div>
			<div class="row">
				<div class="col-lg-6">
				<label>.</label>
					<button type="button" id="btnFiterSubmitSearch" class="btn btn-brand m-btn m-btn--icon" id="m_search">
						<span>
							<i class="la la-search"></i>
							<span>Search</span>
						</span>
					</button>
				</div>
			</div>
		</div>

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="shipping_table">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="10%">Download URL</th>
					<th width="50%">Order Number</th>
					<th width="10%">Success/Total Order</th>
					@if(Auth::user()->roles != 'client')
					<th width="10%">Client</th>
					@endif
					<th width="10%">User</th>
					<th width="20%">Uploaded Order</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="5" class="dataTables_empty">Loading data from server</td>					
				</tr>
			</tbody>
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
	
	$(document).ready(function(){
		e = $("#shipping_table").DataTable({
			bFilter: false,
			responsive: true,
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
			ajax : { 
				url: '/order/shipping/label/list', 
				type: 'GET',
				data: function (d) {
					d.start_date = $('input[name="start"]').val();
					d.end_date = $('input[name="end"]').val();
					d.start_due_date = $('input[name="start_due_date"]').val();
					d.end_due_date = $('input[name="end_due_date"]').val();
					d.is_printed = $('select[name="printed-status"]').val();
          		}
			},
			columns: [
        		{ data: 'id' },
				{ data: 'url' },
				{ data: 'orders'},
				{ data : 'order_summary'},
				@if(Auth::user()->roles != 'client')
				{ data: 'client_name' },
				@endif
				{ data: 'user_name' },
				{ data: 'created_at'},
			],
			dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
			buttons: [{
				text: 'Download',
				action: function ( x, dt, node, config ) {
					var data = e.rows( { selected: true } ).data(),
						ids = [];
					$.each(data, function(i,v){
						ids.push(v.id);
					});

					if (ids.length > 0) {
						$('#order_ids').val(ids.join(','));
						$('#bulk_download').submit();
					}
				}
			}]
		})
	}).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });

	$(".input-daterange").datepicker({
		orientation: "bottom auto",
		todayHighlight: !0,
		format: "yyyy-mm-dd",
		maxSpan: {
			days: 7
		},
	});
	
	$(function(){
		$('.m-select2').select2();
	});

	$('#btnFiterSubmitSearch').click(function(){
		$('#shipping_table').DataTable().draw(true);
	});
</script>
<!--end::Page Resources -->
@endsection