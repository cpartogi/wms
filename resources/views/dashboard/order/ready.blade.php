@extends('layouts.base',[
    'page' => 'Order'
])

@section('modal')
@if(Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="bulk-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form action="{{ url('order/bulk') }}" method="post" enctype="multipart/form-data" id="bulk-form">
			{{ csrf_field() }}
			<input type="file" name="bulk-order" id="upload-bulk" accept=".xls,.xlsx" style="display:none;"/>
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Order Bulk Upload</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group m-form__group">
						<label for="restrict">Restrict Stock:</label><br>
						<label class="m-checkbox">
							<input type="checkbox" name="restrict" value="1" checked="true" /> Enable Restriction
							<span></span>
						</label><br>
						<span class="m-form__help">By enabling this, all products with zero stock will blocking this process</span>
					</div>
					<div class="form-group m-form__group">
						<label for="autoprint">Auto Print:</label><br>
						<label class="m-checkbox">
							<input type="checkbox" name="autoprint" value="1" checked="true" /> Enable Auto Print
							<span></span>
						</label><br>
						<span class="m-form__help">By enabling this, you will redirected to bulk printing soon after the upload competed.</span>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="importing-btn">Start Importing</button>
				</div>
			</div>
		</form>
	</div>
</div>
<!--end::Modal-->
@endif
@if(Auth::user()->roles != 'client' && Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('/order/delete') }}?ready=1">
			{{ csrf_field() }}
			<input type="hidden" name="order_id" value=""/>
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Order</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete selected Orders? All related data to these orders will also be deleted.</p>
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
<!--begin::Modal-->
<div class="modal fade" id="loading-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Loading</h5>
			</div>
			<div class="modal-body">
				<p>Please wait, we are generating orders. Please be patient :)</p>
			</div>
		</div>
	</div>
</div>
<!--end::Modal-->
@endsection

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					@if(Auth::user()->roles == 'client')
						{{'Order List'}}
					@else
						<div class="btn-group">
							<button type="button" class="dropdown-toggle btn m-btn--pill m-btn--air m-btn m-btn--gradient-from-primary m-btn--gradient-to-info" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								Ready to Outbound
							</button>
							<div class="dropdown-menu">
								<h6 class="dropdown-header">Order Status</h6>
								<a class="dropdown-item" href="{{ url('order') }}">All Order</a>
								<a class="dropdown-item" href="{{ url('outbound') }}">Ready to Pack</a>
								<a class="dropdown-item" href="{{ url('outbound/shipment') }}">Await Shipment</a>
								<a class="dropdown-item" href="{{ url('outbound/done') }}">Shipped</a>
								<a class="dropdown-item" href="{{ url('order/canceled') }}">Canceled</a>
							</div>
						</div>
					@endif
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<a href="{{ url('order/add') }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-plus"></i>
							<span>New Order</span>
						</span>
					</a>
				</li>
				<li class="m-portlet__nav-item m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push" m-dropdown-toggle="hover">
					<a href="#" class="m-portlet__nav-link btn btn-primary m-btn m-btn--air m-btn--icon m-btn--icon-only m-btn--pill   m-dropdown__toggle">
						<i class="la la-ellipsis-v"></i>
					</a>
					<div class="m-dropdown__wrapper">
						<span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust"></span>
						<div class="m-dropdown__inner">
							<div class="m-dropdown__body">
								<div class="m-dropdown__content">
									<ul class="m-nav">
										<li class="m-nav__section m-nav__section--first">
											<span class="m-nav__section-text">Quick Actions</span>
										</li>
										<li class="m-nav__item">
											<form action="{{ url('order/bulk') }}" method="post" enctype="multipart/form-data" style="display:none;" id="bulk-form">
												{{ csrf_field() }}
												<input type="file" name="bulk-order" id="upload-bulk" accept=".xls,.xlsx"/>
											</form>
											<a href="#" class="m-nav__link" id="bulk-btn">
												<i class="m-nav__link-icon la la-upload"></i>
												<span class="m-nav__link-text">Bulk Upload</span>
											</a>
										</li>
										@if(Auth::user()->roles == 'client')
										<li class="m-nav__item">
											<a href="{{ url('format/order-sample-client.xlsx') }}" class="m-nav__link">
												<i class="m-nav__link-icon la la-download"></i>
												<span class="m-nav__link-text">Bulk Format</span>
											</a>
										</li>
										@else
										<li class="m-nav__item">
											<a href="{{ url('format/order-sample.xlsx') }}" class="m-nav__link">
												<i class="m-nav__link-icon la la-download"></i>
												<span class="m-nav__link-text">Bulk Format</span>
											</a>
										</li>
										<li class="m-nav__item">
											<a href="{{ url('order/download') }}" class="m-nav__link">
												<i class="m-nav__link-icon la la-download"></i>
												<span class="m-nav__link-text">Download Excel</span>
											</a>
										</li>
										@endif
									</ul>
								</div>
							</div>
						</div>
					</div>
				</li>
				@endif
			</ul>
		</div>
	</div>
	<div class="m-portlet__body">

		@include('notif')

		<form action="{{ url('order/barcode') }}" id="orders-print" method="post">
			{{ csrf_field() }}
			<input type="hidden" id="n" name="n" />
		</form>

		@if(Auth::user()->roles == 'client')
		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="15%">Order Number</th>
					<th width="10%">Customer</th>
					<th width="15%">Products</th>
					<th width="10%">Courier</th>
					<th width="10%">Shipping Number</th>
					<th width="10%">Shipping Cost</th>
					<th width="10%">Date</th>
					<th width="10%">Actions</th>
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
					<th width="15%">Order Number</th>
					<th width="10%">Customer</th>
					<th width="15%">Products</th>
					<th width="10%">Courier</th>
					<th width="10%">Shipping Number</th>
					<th width="10%">Shipping Cost</th>
					<th width="10%">Date</th>
					<th width="10%">Actions</th>
				</tr>
			</tfoot>
		</table>
		@else
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
				<tr>
					<td colspan="10" class="dataTables_empty">Loading data from server</td>			
				</tr>
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
		@endif
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
        	url: "{{ route('ready-list') }}",
        	dataType: "json",
        	type: "POST",
        	data: {_token: "{{ csrf_token() }}"}
        },
        columns: [
        	{ data: 'id' },
        	{ data: 'order_number' },
        	@if(Auth::user()->roles != 'client'){ data: 'order_type' },@endif
            { data: 'customer' },
            @if(Auth::user()->roles != 'client'){ data: 'client' },@endif
            @if(Auth::user()->roles == 'client'){ data: 'details' },@endif
            @if(Auth::user()->roles == 'client'){ data: 'courier' },@endif
            @if(Auth::user()->roles == 'client'){ data: 'no_resi' },@endif
            @if(Auth::user()->roles == 'client'){ data: 'shipping_cost' },@endif
            { data: 'created_at' },
            { data: 'action' }
        ],
        dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
        buttons: ["print", "excelHtml5", "csvHtml5", {
                text: 'Print Label',
                action: function ( x, dt, node, config ) {
                    printBulk();
                }
            }, {
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

    function printBulk()
	{
		var data = e.rows( { selected: true } ).data(),
        	ids = [];

        $.each(data, function(i,v){
        	ids.push(v.order_number);
        });

        $('#n').val(ids.join(',')).closest('#orders-print').submit();
	}

	function deleteBulk()
	{
		var data = e.rows( { selected: true } ).data(),
        	ids = [];

        $.each(data, function(i,v){
        	ids.push(v.order_number);
        });

        $('input[name="order_id"]').val(ids.join(',')).closest('#m_modal_1').modal('show');
	}

	$('body').click(function(e){
		var $this = $(e.target);
		if($this.hasClass('delete-btn')){
			$('#m_modal_1').find('form input[name="order_id"]').val($this.attr('data-id')).end().modal('show');
		}
	});

    $('#bulk-btn').click(function(){
    	$('#upload-bulk').click();
    });

    $('#upload-bulk').change(function(){
    	$('#bulk-modal').modal('show');
    });

    $('#importing-btn').click(function(){
    	$('#bulk-modal').modal('hide');
    	$('#loading-modal').modal({
    		backdrop: 'static',
    		keyboard: false
    	});
    	$('#bulk-form').submit();
    });

    $('#bulk-modal').on('hidden.bs.modal', function (e) {
	  	$("#upload-bulk").clearFiles();
	});
</script>
<!--end::Page Resources -->
@endsection