@extends('layouts.base',[
    'page' => 'Report'
])

@section('modal')
@if(Auth::user()->roles == 'head' || Auth::user()->roles == 'admin')
<!--begin::Modal-->
<div class="modal fade" id="email-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ url('outbound/report') }}" method="post">
            {{ csrf_field() }}
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Outbound Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure to send all outbound report to clients?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Send</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!--end::Modal-->
@endif
@endsection

@section('content')
<!--begin:: Widgets/Stats-->
<div class="m-portlet ">
    <div class="m-portlet__body  m-portlet__body--no-padding">
        <div class="row m-row--no-padding m-row--col-separator-xl">
            <div class="col-md-12 col-lg-4">

                <!--begin::Total Profit-->
                <div class="m-widget24">
                    <div class="m-widget24__item">
                        <h4 class="m-widget24__title">
                            Today's Inbound
                        </h4>
                        <br>
                        <span class="m-widget24__desc">
                            All Customs Value
                        </span>
                        <span class="m-widget24__stats m--font-brand">
                            {{ $total['inbound'] }}
                        </span>
                        <div class="m--space-10"></div>
                        @php
                            $inbound_diff = $total['inbound'] - $total['inbound_before'];
                            $inbound_div = $inbound_diff / max($total['inbound_before'],1);
                            $inbound_percent = $inbound_div * 100;
                        @endphp
                        <div class="progress m-progress--sm">
                            <div class="progress-bar m--bg-brand" role="progressbar" style="width: {{ $inbound_percent }}%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="m-widget24__change">
                            Change
                        </span>
                        <span class="m-widget24__number">
                            {{ $inbound_percent }}%
                        </span>
                    </div>
                </div>

                <!--end::Total Profit-->
            </div>
            <div class="col-md-12 col-lg-4">

                <!--begin::New Orders-->
                <div class="m-widget24">
                    <div class="m-widget24__item">
                        <h4 class="m-widget24__title">
                            Today's Orders
                        </h4>
                        <br>
                        <span class="m-widget24__desc">
                            Fresh Order Amount
                        </span>
                        <span class="m-widget24__stats m--font-danger">
                            {{ $total['order'] }}
                        </span>
                        <div class="m--space-10"></div>
                        @php
                            $order_diff = $total['order'] - $total['order_before'];
                            $order_div = $order_diff / max($total['order_before'],1);
                            $order_percent = $order_div * 100;
                        @endphp
                        <div class="progress m-progress--sm">
                            <div class="progress-bar m--bg-danger" role="progressbar" style="width: {{ $order_percent }}%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="m-widget24__change">
                            Change
                        </span>
                        <span class="m-widget24__number">
                            {{ $order_percent }}%
                        </span>
                    </div>
                </div>

                <!--end::New Orders-->
            </div>
            <div class="col-md-12 col-lg-4">

                <!--begin::New Feedbacks-->
                <div class="m-widget24">
                    <div class="m-widget24__item">
                        <h4 class="m-widget24__title">
                            Today's Outbound
                        </h4>
                        <br>
                        <span class="m-widget24__desc">
                            Shipped Order
                        </span>
                        <span class="m-widget24__stats m--font-info">
                            {{ $total['outbound'] }}
                        </span>
                        <div class="m--space-10"></div>
                        @php
                            $outbound_diff = $total['outbound'] - $total['outbound_before'];
                            $outbound_div = $outbound_diff / max($total['outbound_before'],1);
                            $outbound_percent = $outbound_div * 100;
                        @endphp
                        <div class="progress m-progress--sm">
                            <div class="progress-bar m--bg-info" role="progressbar" style="width: {{ $outbound_percent }}%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="m-widget24__change">
                            Change
                        </span>
                        <span class="m-widget24__number">
                            {{ $outbound_percent }}%
                        </span>
                    </div>
                </div>

                <!--end::New Feedbacks-->
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
                            Order Status
                        </h3>
                    </div>
                </div>
            </div>
            <div class="m-portlet__body">

                @if(Session::has('success_download'))
                <div class="m-alert m-alert--icon m-alert--air alert alert-success alert-dismissible fade show" role="alert">
                    <div class="m-alert__icon">
                        <i class="la la-warning"></i>
                    </div>
                    <div class="m-alert__text">
                        <strong>Success!</strong> {{ Session::get('success_download') }}.
                    </div>
                    <div class="m-alert__close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        </button>
                    </div>
                </div>
                @endif

                @if(Session::has('error_download'))
                <div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="m-alert__icon">
                        <i class="flaticon-exclamation-1"></i>
                        <span></span>
                    </div>
                    <div class="m-alert__text">
                        <strong>Uh oh!</strong> {{ Session::get('error_download') }}.
                    </div>
                    <div class="m-alert__close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        </button>
                    </div>
                </div>
                @endif

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
                        <div class="row">
                            @if(Auth::user()->roles != 'client')
                            <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile">
                                <label>Client:</label>
                                <select class="form-control m-input m-select2" name="client-name" id="client-selector" data-col-index="2">
                                    <option value=""> -- All Clients -- </option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile">
                                <label>Status:</label>
                                <select id="order-status" class="form-control m-input m-select2" name="order-status" data-col-index="2">
                                    <option value=""> -- All -- </option>
                                    <option value="c">Completed Orders</option>
                                    <option value="i">On Progress Orders</option>
                                </select>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                        <br>

                        <!--begin: Datatable -->
                        <table class="table table-striped- table-bordered table-hover table-checkable" id="order_status_table">
                            <thead>
                                <tr>
                                    <th width="15%">Date</th>
                                    @foreach(Config::get('constants.order_status') as $status)
                                    <th width="12%">{{ $status }}</th>
                                    @endforeach
                                    <th width="13%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="dataTables_empty">Loading data from server</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='7'><b>Total (page <span id="order_status_page">0</span> of <span id="order_status_total_page">0</span>):</b></td>
                                    <td> <span id="total_order"></span> </td>
                                </tr>
                            </tfoot>
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
    @if(Auth::user()->roles != 'client')
    <div class="col-sm-6 col-xs-12">
        <!--begin:: Widgets/Sale Reports-->
        <div class="m-portlet m-portlet--full-height ">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Sales Reports
                        </h3>
                    </div>
                </div>
                <!--<div class="m-portlet__head-tools">
                    <ul class="nav nav-pills nav-pills--brand m-nav-pills--align-right m-nav-pills--btn-pill m-nav-pills--btn-sm" role="tablist">
                        <li class="nav-item m-tabs__item">
                            <a class="nav-link m-tabs__link active" data-toggle="tab" href="#m_widget11_tab1_content" role="tab">
                                Today
                            </a>
                        </li>
                    </ul>
                </div>-->
            </div>
            <div class="m-portlet__body">

                @include('notif')

                <!--Begin::Tab Content-->
                <div class="tab-content">
                    <!--begin::tab 1 content-->
                    <div class="tab-pane active" id="m_widget11_tab1_content">
                        <!--begin::Widget 11-->
                        <div class="m-widget11">
                            <div class="table-responsive">
                                <!--begin::Table-->
                                <table class="table table-checkable" id="order_report">
                                    <!--begin::Thead-->
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Order Number</th>
                                            <th>Client</th>
                                            <th>Customer</th>
                                            <th>Courier</th>
                                            <th>No. Resi</th>
                                        </tr>
                                    </thead>
                                    <!--end::Thead-->
                                    <!--begin::Tbody-->
                                    <tbody>
                                        <tr>
                                            <td colspan="10" class="dataTables_empty">Loading data from server</td>
                                        </tr>
                                    </tbody>
                                    <!--end::Tbody-->
                                </table>
                                <!--end::Table-->
                            </div>
                            @if(Auth::user()->roles != 'investor')
                            <div class="m-widget11__action m--align-right">
                                <button type="button" data-target="#email-modal" data-toggle="modal" class="btn m-btn--pill btn-outline-brand m-btn m-btn--custom">Send Daily Report</button>
                            </div>
                            @endif
                        </div>
                        <!--end::Widget 11-->
                    </div>
                    <!--end::tab 1 content-->
                </div>
                <!--End::Tab Content-->
            </div>
        </div>
        <!--end:: Widgets/Sale Reports-->
    </div>
    @endif
    <div class="@if(Auth::user()->roles != 'client') col-sm-6 @else col-sm-12 @endif">
        <!--begin:: Widgets/Sale Reports-->
        <div class="m-portlet m-portlet--full-height ">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Stock Movement
                        </h3>
                    </div>
                </div>
            </div>
            <div class="m-portlet__body">

                @if(Session::has('success_download'))
                <div class="m-alert m-alert--icon m-alert--air alert alert-success alert-dismissible fade show" role="alert">
                    <div class="m-alert__icon">
                        <i class="la la-warning"></i>
                    </div>
                    <div class="m-alert__text">
                        <strong>Success!</strong> {{ Session::get('success_download') }}.
                    </div>
                    <div class="m-alert__close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        </button>
                    </div>
                </div>
                @endif

                @if(Session::has('error_download'))
                <div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="m-alert__icon">
                        <i class="flaticon-exclamation-1"></i>
                        <span></span>
                    </div>
                    <div class="m-alert__text">
                        <strong>Uh oh!</strong> {{ Session::get('error_download') }}.
                    </div>
                    <div class="m-alert__close">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        </button>
                    </div>
                </div>
                @endif

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
                        <!--begin::Widget 11-->
                        <form action="{{ url('report/stocks') }}" method="get">
                            {{ csrf_field() }}
                            <div class="m-widget11 row">
                                <div class="col-sm-3">
                                    <label for="stock_start">Start Date:</label>
                                    <input type="text" class="form-control m-datepicker" name="start" value="{{ date('Y-m-d', strtotime('-1 week')) }}"/>
                                </div>
                                <div class="col-sm-3">
                                    <label for="stock_end">End Date:</label>
                                    <input type="text" class="form-control m-datepicker" name="end" value="{{ date('Y-m-d') }}"/>
                                </div>
                                <div class="col-sm-6" style="display: flex; align-items: flex-end">
                                    <button type="submit" class="btn m-btn--pill btn-outline-brand m-btn m-btn--custom">Download Stock Movement</button>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                            <br>

                            <!--end::Widget 11-->
                        </form>
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
    $('.m-datepicker').datepicker({format:'yyyy-mm-dd'});
    var e;
    (e = $("#order_report").DataTable({
        responsive: !0,
        processing: true,
        serverSide: true,
        scrollY:"25vh",
        scrollX:!0,
        scrollCollapse:!0,
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
            url: "{{ route('order-report-list') }}",
            dataType: "json",
            type: "POST",
            data: {_token: "{{ csrf_token() }}"}
        },
        columns: [
            { data: 'id' },
            { data: 'order_number' },
            { data: 'client_name' },
            { data: 'customer_name' },
            { data: 'courier' },
            { data: 'no_resi' }
        ]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), f.rows($(this).closest("tr")).deselect())
        })
    });

    $('.m-select2').select2();

    var orderStatusTable = $("#order_status_table").DataTable({
        bFilter: false,
        responsive: true,
        processing: true,
        serverSide: true,
        order: [[ 0, "desc" ]],
        ajax: {
            url: "{{ route('order-status-report') }}",
            dataType: "json",
            type: "POST",
            data: function(d) {
                return  $.extend(d, {
                    _token: "{{ csrf_token() }}",
                    status: $('#order-status').val(),
                    client: $('#client-selector').val()
                });
            }
        },
        columns: [
            { data: 'date' },
            { data: 'total_pending' },
            { data: 'total_canceled' },
            { data: 'total_ready_outbound' },
            { data: 'total_ready_pack' },
            { data: 'total_waiting_shipment' },
            { data: 'total_shipped' },
            { data: 'total' },
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(), total;

            total = api
                .column(7, { page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return parseInt(a) + parseInt(b);
                }, 0);

            $('#total_order').html(total);
        },
        fnDrawCallback: function() {
            updatePageOnTotal();
        }
    });

    $('#order-status,#client-selector').each(function() {
        $(this).change(function() {
            orderStatusTable.draw();
        });
    });

    function updatePageOnTotal() {
        var info = orderStatusTable.page.info();
        $('#order_status_page').html(info.page+1);
        $('#order_status_total_page').html(info.pages);
    }

    $('#order_status_table').on('page.dt', updatePageOnTotal);
</script>
@endsection