@extends('layouts.base',[
    'page' => 'Report'
])

@section('content')
<!--begin:: Widgets/Stats-->
<div class="m-portlet ">
    <div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Outbound Analytics
				</h3>
			</div>
		</div>
	</div>
    <div class="m-portlet__body">
            <!--begin: Search Form -->
            <form class="m-form m-form--fit m--margin-bottom-20">
                <div class="row m--margin-bottom-20">
                    <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Warehouse:</label>
                        <select class="form-control m-input m-select2" name="warehouse" data-col-index="6">
                            <option value=""> -- All Warehouse -- </option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(Auth::user()->roles != 'client')
                    <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Client:</label>
                        <select class="form-control m-input m-select2" name="client" data-col-index="2">
                            <option value=""> -- All Clients -- </option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Order Created Date:</label>
                        <div class="input-daterange input-group" id="m_datepicker">
                            <input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-7 days')) }}" name="date_start" placeholder="From" data-col-index="5" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
                            </div>
                            <input type="text" class="form-control m-input" name="date_end" value="{{ date('Y-m-d', strtotime('+2 days', time())) }}" placeholder="To" data-col-index="5" />
                        </div>
                    </div>
                </div>
                <div class="m-separator m-separator--md m-separator--dashed"></div>
                <div class="row">
                    <div class="col-lg-12">
                        <button type="button" id="adv-button" class="btn btn-brand m-btn m-btn--icon" id="m_search">
                            <span>
                                <i class="la la-search"></i>
                                <span>Search</span>
                            </span>
                        </button>
                        &nbsp;&nbsp;
                        <button class="btn btn-secondary m-btn m-btn--icon" id="m_reset">
                            <span>
                                <i class="la la-close"></i>
                                <span>Reset</span>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
            <!--end: Search Form -->
        <div class="row m-row--no-padding m-row--col-separator-xl">
            <div class="col-md-12 col-lg-3">

                <!--begin::Total order-->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Total Order
                        </h4><br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_order" style="width: 100%; text-align: center;">
                        </span>
                    </div>
                </div>
                <!--end::Total order-->
            </div>
            <div class="col-md-12 col-lg-3">
                <!--begin::pending-->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Pending
                        </h4> <br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_pending" style="width: 100%; text-align: center;">
                        </span>                       
                    </div>
                </div>
                 <!--end::pending-->
            </div>
            <div class="col-md-12 col-lg-3">

                <!--begin::insufficient stock-->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Insufficient Stock
                        </h4><br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_insufficient" style="width: 100%; text-align: center;">
                        </span>
                    </div>
                </div>

                <!--end:: insufficient stock -->
            </div>
            <div class="col-md-12 col-lg-3">

                <!--begin::canceled -->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Canceled
                        </h4><br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_canceled" style="width: 100%; text-align: center;">
                        </span>
                    </div>
                </div>

                <!--end:: canceled-->
            </div>
        </div>
        <div class="m-separator m-separator--md m-separator--dashed"></div>
        <div class="row m-row--no-padding m-row--col-separator-xl">
            <div class="col-md-12 col-lg-3">

                <!--begin::Total order-->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Ready To Outbound
                        </h4><br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_rfo" style="width: 100%; text-align: center;">
                        </span>
                    </div>
                </div>
                <!--end::Total order-->
            </div>
            <div class="col-md-12 col-lg-3">
                <!--begin::pending-->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Ready to Pack
                        </h4> <br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_rtp" style="width: 100%; text-align: center;">
                        </span>                       
                    </div>
                </div>
                 <!--end::pending-->
            </div>
            <div class="col-md-12 col-lg-3">

                <!--begin::insufficient stock-->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Await Shipment
                        </h4><br>
                        <div class="m--space-40"></div> 
                        <span class="m-widget24__stats m--font-info" id="total_aws" style="width: 100%; text-align: center;">
                        </span>
                    </div>
                </div>

                <!--end:: insufficient stock -->
            </div>
            <div class="col-md-12 col-lg-3">

                <!--begin::canceled -->
                <div class="row m-widget24">
                    <div class="m-widget24__item" style="width: 100%;">
                        <h4 class="m-widget24__title">
                            Shipped
                        </h4><br>
                        <div class="m--space-40"></div>
                        <span class="m-widget24__stats m--font-info" id="total_shipped" style="width: 100%; text-align: center;">
                        </span>
                    </div>
                </div>

                <!--end:: canceled-->
            </div>
        </div>
    </div>
</div>

<!--end:: Widgets/Stats-->

<!--Begin::Section-->
<div class="row">
    <div class="col-sm-12">
        <!--begin:: Widgets/Sale Reports-->
        <div class="m-portlet m-portlet--full-height ">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Shipment Progress by Courier
                        </h3>
                    </div>
                </div>
            </div>
            <div class="m-portlet__body">

                @if($errors->count() > 0)
                <div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="m-alert__icon">
                        <i class="flaticon-exclamation-1"></i>
                        <span></span>
                    </div>
                    <div class="m-alert__text">
                        <strong>Uh oh!</strong>
                        <ul class="list-styled">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        </ul>
                    </div>
                    <div class="m-alert__close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        </button>
                    </div>
                </div>
                @endif
                <!--Begin::Tab Content-->
                <div class="tab-content">
                    <!--begin::tab 1 content-->
                    <div class="tab-pane active" id="stock_movement_today">
                        <div class="clearfix"></div>
                        <br>

                        <!--begin: Datatable -->
                        <table class="table table-striped- table-bordered table-hover table-checkable" id="shipment_progress">
                            <thead>
                                <tr>
                                    <th width="15%">Courier</th>
                                    <th width="15%">Await Shipment</th>
                                    <th width="15%">Shipped</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="dataTables_empty">Loading data from server</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="clearfix"></div>
                        <br>
                    </div>
                    <!--end::tab 1 content-->
                </div>
                <!--End::Tab Content-->
            </div>
        </div>
        <!--end:: Widgets/Sale Reports-->
    </div>
</div>
<!--End::Section-->

<!--Begin::Section-->
<div class="row">
    <div class="col-sm-12">
        <!--begin:: Widgets/Sale Reports-->
        <div class="m-portlet m-portlet--full-height ">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Daily Report
                        </h3>
                    </div>
                </div>
            </div>
            <div class="m-portlet__body">

                @if($errors->count() > 0)
                <div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="m-alert__icon">
                        <i class="flaticon-exclamation-1"></i>
                        <span></span>
                    </div>
                    <div class="m-alert__text">
                        <strong>Uh oh!</strong>
                        <ul class="list-styled">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        </ul>
                    </div>
                    <div class="m-alert__close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        </button>
                    </div>
                </div>
                @endif
                <!--Begin::Tab Content-->
                <div class="tab-content">
                    <!--begin::tab 1 content-->
                    <div class="tab-pane active" id="stock_movement_today">
                        <div class="clearfix"></div>
                        <br>

                        <!--begin: Datatable -->
                        <table class="table table-striped- table-bordered table-hover table-checkable" id="daily_report">
                            <thead>
                                <tr>
                                    <th width="15%">Date</th>
                                    <th width="15%">Warehouse</th>
                                    <th width="15%">Client</th>
                                    <th width="15%">Total Order</th>
                                    <th width="15%">Shipped</th>
                                    <th width="15%">Cancelled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="dataTables_empty">Loading data from server</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="clearfix"></div>
                        <br>
                    </div>
                    <!--end::tab 1 content-->
                </div>
                <!--End::Tab Content-->
            </div>
        </div>
        <!--end:: Widgets/Sale Reports-->
    </div>
</div>
<!--End::Section-->


@endsection

@section('style')
<link href="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<script src="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.js') }}" type="text/javascript"></script>
<script src="{{ asset('mt/default/assets/app/js/dashboard.js') }}" type="text/javascript"></script>
<script>
    var variables = {
            e: null,
            f: null,
            loads: {
                _token: "{{ csrf_token() }}", 
                client:$('select[name="client"]').val(),
                warehouse:$('select[name="warehouse"]').val(),
                start_date:$('input[name="date_start"]').val(),
                end_date:$('input[name="date_end"]').val(),
            }
        };
        var e;
        (e = $("#shipment_progress").DataTable({
            bFilter: false,
            responsive: !0,
            processing: true,
            serverSide: true,
            paging: false,
            ajax: function(data, callback, settings) {
                fetchData(variables.loads).then(res => {
                    callback({
                        recordsTotal: res.courier_progress.recordsTotal,
                        recordsFiltered: res.courier_progress.recordsFiltered,
                        data: res.courier_progress.data
                    });
                });
            },
            columns: [
                {data : 'courier'},
                {data : 'awaiting_for_shipment'},
                {data : 'shipped'}
            ]
        }));
        variables.e = e;

        var f;
        (f = $("#daily_report").DataTable({
            bFilter: false,
            responsive: !0,
            processing: true,
            serverSide: true,
            paging: false,
            ajax: function(data, callback, settings) {
                fetchData(variables.loads).then(res => {
                    callback({
                        recordsTotal: res.daily_report.recordsTotal,
                        recordsFiltered: res.daily_report.recordsFiltered,
                        data: res.daily_report.data
                    });
                });
            },
            columns: [
                {data : 'date'},
                {data : 'warehouse'},
                {data : 'client'},
                {data : 'total'},
                {data : 'shipped'},
                {data : 'canceled'}
            ]
        }));
        variables.f = f;

        $('.m-select2').select2();

        $('#adv-button').click(function(){
            variables.loads.client = $('select[name="client"]').val();
            variables.loads.warehouse = $('select[name="warehouse"]').val();
            variables.loads.start_date = $('input[name="date_start"]').val();
            variables.loads.end_date = $('input[name="date_end"]').val();
            variables.e.ajax.reload();
            variables.f.ajax.reload();

            updateMetrics();
        });

        $(".input-daterange").datepicker({
            orientation: "bottom auto",
            todayHighlight: !0,
            format: "yyyy-mm-dd",
            maxSpan: {
                days: 7
            },
        });

        function updateMetrics() {
            fetchData(variables.loads).then(res => {
                $('#total_order').text(res.total || 0);
                $('#total_pending').text(res.status.PENDING || 0);
                $('#total_insufficient').text(res.status.INSUFFICIENT_STOCK || 0);
                $('#total_canceled').text(res.status.CANCELED || 0);
                $('#total_rfo').text(res.status.READY_FOR_OUTBOUND || 0);
                $('#total_rtp').text(res.status.READY_TO_PACK || 0);
                $('#total_aws').text(res.status.AWAITING_FOR_SHIPMENT || 0);
                $('#total_shipped').text(res.status.SHIPPED || 0);
            });
        }

        updateMetrics();

        function fetchData(payload) {
            return new Promise((resolve, reject) => {
                const url = "{{ route('report-performance') }}?start_date="+payload.start_date
                +"&end_date="+payload.end_date+"&client="+payload.client+"&warehouse="+payload.warehouse;
                $.get(url, {}, function(res) {
                    res = JSON.parse(res);
                    resolve(res);
                });
            });
        }
</script>
@endsection