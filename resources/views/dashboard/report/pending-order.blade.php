@extends('layouts.base',[
    'page' => 'Report'
])

@section('content')
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/report">Order Status</a></li>
    <li class="breadcrumb-item active">Pending</li>
    <li class="breadcrumb-item" aria-current="page">{{ \Carbon\Carbon::parse($current_date)->format('d-M-Y') }}</li>
  </ol>
</nav>

<!--Begin::Section-->
<div class="row">
    <div class="col-sm-12">
        <!--begin:: Widgets/Sale Reports-->
        <div class="m-portlet m-portlet--full-height ">
            <div class="m-portlet__body">
                <!--Begin::Tab Content-->
                <div class="tab-content">
                    <!--begin::tab 1 content-->
                    <div class="tab-pane active" id="pending_order_today">
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
                        </div>

                        <div class="clearfix"></div>
                        <br>

                        <!--begin: Datatable -->
                        <table class="table table-striped- table-bordered table-hover table-checkable" id="order_pending_table">
                            <thead>
                                <tr>
                                    <th width="15%">Order Number</th>
                                    <th width="10%">Customer</th>
                                    <th width="30%">Address</th>
                                    <th width="10%">Items</th>
                                    <th width="10%">Size</th>
                                    <th width="10%">Ordered</th>
                                    <th width="10%">Stock</th>
                                    <th width="5%">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="dataTables_empty">Loading data from server</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='5'><b>Total (page <span id="order_status_page">0</span> of <span id="order_status_total_page">0</span>):</b></td>
                                    <td> <span id="total_order"></span> </td>
                                    <td> <span id="total_stock"></span> </td>
                                    <th>&nbsp;</th>
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
@endsection


@section('style')
<link href="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<script src="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.js') }}" type="text/javascript"></script>
<script src="{{ asset('mt/default/assets/app/js/dashboard.js') }}" type="text/javascript"></script>
<script>
    $('.m-select2').select2();

    var pendingOrdersTable = $("#order_pending_table").DataTable({
        bSort: false,
        bFilter: false,
        responsive: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('order-pending-report') }}",
            dataType: "json",
            type: "POST",
            data: function(d) {
                return  $.extend(d, {
                    _token: "{{ csrf_token() }}",
                    date: '{{ $current_date }}',
                    client: $('#client-selector').val()
                });
            }
        },
        columns: [
            { data: 'order_number' },
            { data: 'customer' },
            { data: 'address' },
            { data: 'product' },
            { data: 'size' },
            { data: 'ordered' },
            { data: 'stock' },
            { data: 'warning' },
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(), totalOrdered, totalStock;

            totalOrdered = api
                .column(4, { page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return parseInt(a) + parseInt(b);
                }, 0);

            totalStock = api
                .column(5, { page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return parseInt(a) + parseInt(b);
                }, 0);

            $('#total_order').html(totalOrdered);
            $('#total_stock').html(totalStock);
        },
        fnDrawCallback: function() {
            updatePageOnTotal();
            MergeGridCells();
        }
    });
    
    $('#order_status_table').on('page.dt', updatePageOnTotal);

    $('#client-selector').change(function() {
        pendingOrdersTable.draw();
    });

    function updatePageOnTotal() {
        var info = pendingOrdersTable.page.info();
        $('#order_status_page').html(info.page+1);
        $('#order_status_total_page').html(info.pages);
    }

    function MergeGridCells() {
        var dimension_cells = [];
        var dimension_col = null;
        // first_instance holds the first instance of identical td
        var first_instance = [];
        var rowspan = 1;

        // iterate through rows
        $("#order_pending_table").find('tr').each(function () {

            // find the td of the correct column (determined by the dimension_col set above)
            var dimension_td_number = $(this).find('td:nth-child(1)');
            var dimension_td_customer = $(this).find('td:nth-child(2)');
            var dimension_td_address = $(this).find('td:nth-child(3)');

            if (first_instance.length == 0) {
                // must be the first row
                first_instance[0] = dimension_td_number;
                first_instance[1] = dimension_td_customer;
                first_instance[2] = dimension_td_address;
            } else if (
                dimension_td_number.text() == first_instance[0].text() &&
                dimension_td_customer.text() == first_instance[1].text() &&
                dimension_td_address.text() == first_instance[2].text()
            ) {
                // the current td is identical to the previous
                // remove the current td
                dimension_td_number.remove();
                dimension_td_customer.remove();
                dimension_td_address.remove();
                ++rowspan;

                // increment the rowspan attribute of the first instance
                first_instance.forEach(function(value) {
                    $(value).attr('rowspan', rowspan);
                });
            } else {
                // this cell is different from the last
                first_instance[0] = dimension_td_number;
                first_instance[1] = dimension_td_customer;
                first_instance[2] = dimension_td_address;
                rowspan = 1;
            }
        });
    }
</script>
@endsection