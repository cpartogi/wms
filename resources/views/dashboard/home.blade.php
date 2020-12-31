@extends('layouts.base',[
    'page' => 'Dashboard'
])

@section('style')
<style>
#flotTip {
    padding: 3px 5px;
    background-color: #000;
    z-index: 100;
    color: #fff;
    opacity: .80;
    filter: alpha(opacity=85);
}

.dataTables_filter {
    text-align:right;
}
</style>
@endsection

@section('content')
<!--Begin::Section-->
<div class="row">
    <div class="col-sm-6">
        <div class="m-portlet m-portlet--mobile">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Top 10 Products
                        </h3>
                    </div>
                </div>
                <div class="m-portlet__head-tools">
                    <ul class="m-portlet__nav">
                        <li class="m-portlet__nav-item"><i class="flaticon-trophy"></i></li>
                    </ul>
                </div>
            </div>
            <div class="m-portlet__body">
                <!--begin: Datatable -->
                <table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Sold (Pcs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="dataTables_empty">Loading data from server</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="m-portlet m-portlet--mobile">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Running Out of Stocks
                        </h3>
                    </div>
                </div>
                <div class="m-portlet__head-tools">
                    <ul class="m-portlet__nav">
                        <li class="m-portlet__nav-item"><i class="flaticon-exclamation"></i></li>
                    </ul>
                </div>
            </div>
            <div class="m-portlet__body">
                <!--begin: Datatable -->
                <table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_2">
                    <thead>
                        <tr>
                            <th>Stock ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stocks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="dataTables_empty">Loading data from server</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!--end:: Widgets/Outbound Bandwidth-->
    </div>
</div>
<!--Begin::Section-->
<div class="row">
    <div class="col-sm-6">
        <div class="m-portlet m-portlet--tab">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <span class="m-portlet__head-icon m--hide">
                            <i class="la la-gear"></i>
                        </span>
                        <h3 class="m-portlet__head-text">
                            Sales Chart
                        </h3>
                    </div>
                </div>
                <div class="m-portlet__head-tools">
                    <ul class="nav nav-pills nav-pills--brand m-nav-pills--align-right m-nav-pills--btn-pill m-nav-pills--btn-sm" role="tablist">
                        <li class="nav-item m-tabs__item">
                            <a class="nav-link m-tabs__link active" data-toggle="tab" href="#m_widget2_tab1_content" role="tab">
                                Today
                            </a>
                        </li>
                        <li class="nav-item m-tabs__item">
                            <a class="nav-link m-tabs__link" data-toggle="tab" href="#m_widget2_tab2_content1" role="tab">
                                Week
                            </a>
                        </li>
                        <li class="nav-item m-tabs__item">
                            <a class="nav-link m-tabs__link" data-toggle="tab" href="#m_widget2_tab3_content1" role="tab">
                                Month
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="m-portlet__body">
                <div class="tab-content">
                    <div class="tab-pane active" id="m_widget2_tab1_content">
                        <div id="m_flotcharts_7" style="height: 300px;"></div>
                    </div>
                    <div class="tab-pane" id="m_widget2_tab2_content"></div>
                    <div class="tab-pane" id="m_widget2_tab3_content"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <!--begin:: Widgets/Support Cases-->
        <div class="m-portlet  m-portlet--full-height ">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Today's Status
                        </h3>
                    </div>
                </div>
            </div>
            <div class="m-portlet__body">
                <div class="m-widget16">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="m-widget16__head">
                                <div class="m-widget16__item">
                                    <span class="m-widget16__sceduled">
                                        Status
                                    </span>
                                    <span class="m-widget16__amount m--align-right">
                                        Amount
                                    </span>
                                </div>
                            </div>
                            <div class="m-widget16__body">

                                <!--begin::widget item-->
                                <div class="m-widget16__item">
                                    <span class="m-widget16__date">
                                        Total Order
                                    </span>
                                    <span class="m-widget16__price m--align-right m--font-brand">
                                        {{ round($params['total']) }}
                                    </span>
                                </div>

                                <!--end::widget item-->

                                <!--begin::widget item-->
                                <div class="m-widget16__item">
                                    <span class="m-widget16__date">
                                        Ready for Outbound
                                    </span>
                                    <span class="m-widget16__price m--align-right m--font-brand">
                                        {{ round($params['ready']) }}
                                    </span>
                                </div>
                                <!--end::widget item-->

                                <!--begin::widget item-->
                                <div class="m-widget16__item">
                                    <span class="m-widget16__date">
                                        Ready to Pack
                                    </span>
                                    <span class="m-widget16__price m--align-right m--font-brand">
                                        {{ round($params['packing']) }}
                                    </span>
                                </div>
                                <!--end::widget item-->

                                <!--begin::widget item-->
                                <div class="m-widget16__item">
                                    <span class="m-widget16__date">
                                        Awaiting Shipment
                                    </span>
                                    <span class="m-widget16__price m--align-right m--font-brand">
                                        {{ round($params['awaits']) }}
                                    </span>
                                </div>
                                <!--end::widget item-->

                                <!--begin::widget item-->
                                <div class="m-widget16__item">
                                    <span class="m-widget16__date">
                                        Shipped
                                    </span>
                                    <span class="m-widget16__price m--align-right m--font-brand">
                                        {{ round($params['shipped']) }}
                                    </span>
                                </div>
                                <!--end::widget item-->
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="m-widget16__stats">
                                <div class="m-widget16__visual">
                                    <div id="m_chart_orders" style="height: 180px"></div>
                                </div>
                                <div class="m-widget16__legends">
                                    <div class="m-widget16__legend">
                                        <span class="m-widget16__legend-bullet m--bg-info"></span>
                                        <span class="m-widget16__legend-text">{{ $params['total'] }} Orders</span>
                                    </div>
                                    <div class="m-widget16__legend">
                                        <span class="m-widget16__legend-bullet m--bg-accent"></span>
                                        <span class="m-widget16__legend-text">@if($params['total'] != 0){{ round(($params['ready']/$params['total'])*100,2) }}@else{{'0'}}@endif% Ready</span>
                                    </div>
                                    <div class="m-widget16__legend">
                                        <span class="m-widget16__legend-bullet m--bg-brand"></span>
                                        <span class="m-widget16__legend-text">@if($params['total'] != 0){{ round(($params['packing']/$params['total'])*100,2) }}@else{{'0'}}@endif% Packing</span>
                                    </div>
                                    <div class="m-widget16__legend">
                                        <span class="m-widget16__legend-bullet m--bg-warning"></span>
                                        <span class="m-widget16__legend-text">@if($params['total'] != 0){{ round(($params['awaits']/$params['total'])*100,2) }}@else{{'0'}}@endif% Awaits</span>
                                    </div>
                                    <div class="m-widget16__legend">
                                        <span class="m-widget16__legend-bullet m--bg-success"></span>
                                        <span class="m-widget16__legend-text">@if($params['total'] != 0){{ round(($params['shipped']/$params['total'])*100,2) }}@else{{'0'}}@endif% Shipped</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end:: Widgets/Support Stats-->
    </div>
</div>
<script>
    var variable = {
        'total':'{{ $params["total"] }}',
        'ready':'{{ $params["ready"] }}',
        'packing':'{{ $params["packing"] }}',
        'awaits':'{{ $params["awaits"] }}',
        'shipped':'{{ $params["shipped"] }}'
    };
</script>
@endsection

@section('style')
<link href="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<script src="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.js') }}" type="text/javascript"></script>
<script src="{{ asset('mt/default/assets/app/js/dashboard.js') }}" type="text/javascript"></script>
<script src="{{ asset('mt/default/assets/vendors/custom/flot/flot.bundle.js') }}" type="text/javascript"></script>
<script>
    var e;
    (e = $("#m_table_1").DataTable({
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
            url: "{{ route('top-list') }}",
            dataType: "json",
            type: "POST",
            data: {_token: "{{ csrf_token() }}"}
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'price' },
            { data: 'sum' }
        ]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });

    (e = $("#m_table_2").DataTable({
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
            url: "{{ route('stock-list') }}",
            dataType: "json",
            type: "POST",
            data: {_token: "{{ csrf_token() }}"}
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'price' },
            { data: 'stocks' }
        ]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), f.rows($(this).closest("tr")).deselect())
        })
    });

    $.plot($("#m_flotcharts_7"),[[10,0],[20,1],[30,2],[40,3]],{series:{bars:{show:!0}},bars:{horizontal:!0,barWidth:6,lineWidth:0,shadowSize:0,align:"left"},grid:{tickColor:"#eee",borderColor:"#eee",borderWidth:1}});

    // Set up our data array (Sales Chart)
    var my_data = [[0, 0], [0, 1], [0, 2], [0, 3]];  
    // Setup labels for use on the Y-axis  
    var tickLabels = [[0, 'Week 1'], [1, 'Week 2'], [2, 'Week 3'], [3, 'Week 4']];  
    $.plot($("#m_flotcharts_7"), 
        [  
            {  
                data: my_data,
                bars: {  
                    show: true,  
                    horizontal: true,
                    lineWidth:0,
                    shadowSize:0,
                    align:"left"
                }  
            }  
        ], 
        {  
            yaxis: {  
                ticks: tickLabels  
            },
            series: {
                stack: 1,
                bars: {
                    order: 1,
                    show: 1,
                    barWidth: 0.2,
                    fill: 0.8,
                    align: 'center',
                    horizontal: true
                }
            },
            tooltip: true,
            tooltipOpts: {
                cssClass: "flotTip",
                content: "%s: %y",
                defaultTheme: false
            },
            legend: {
                show: true
            },
            grid: {
                hoverable: true,
                borderWidth: 0
            }
        }
    );

    Morris.Donut({
      element: 'm_chart_orders',
      data: [
        {label: "Total Order", value: parseInt(variable.total)},
        {label: "Ready", value: parseInt(variable.ready)},
        {label: "Packing", value: parseInt(variable.packing)},
        {label: "Awaits", value: parseInt(variable.awaits)},
        {label: "Shipped", value: parseInt(variable.shipped)}
      ]
    });

</script>
<!--end::Page Resources -->
@endsection