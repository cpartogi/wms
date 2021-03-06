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
                                <label for="restrict">Restrict Order:</label><br>
                                <label class="m-checkbox">
                                    <input type="checkbox" name="restrict" value="1" checked="true"/> Enable Restriction
                                    <span></span>
                                </label><br>
                                <span class="m-form__help">By enabling this, all orders will be uploaded into live data.</span>
                            </div>
                            <div class="form-group m-form__group">
                                <label for="autoprint">Auto Print:</label><br>
                                <label class="m-checkbox">
                                    <input type="checkbox" name="autoprint" value="1" checked="true"/> Enable Auto Print
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
                <form method="post" action="{{ url('/order/delete') }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="order_id" value=""/>
                    @if($restrict)<input type="hidden" name="restricted" value="true"/>@endif
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
                    <p id="progress">Please wait, we are generating orders. Please be patient :)</p>
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
                            @if(!$restrict)
                                <!--<div class="btn-group">
                                    <button type="button" class="dropdown-toggle btn m-btn--pill m-btn--air m-btn m-btn--gradient-from-primary m-btn--gradient-to-info" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        All Order
                                    </button>
                                    <div class="dropdown-menu">
                                        <h6 class="dropdown-header">Order Status</h6>
                                        <a class="dropdown-item" href="{{ url('order/ready') }}">Ready to Outbound</a>
                                        <a class="dropdown-item" href="{{ url('outbound') }}">Ready to Pack</a>
                                        <a class="dropdown-item" href="{{ url('outbound/shipment') }}">Await Shipment</a>
                                        <a class="dropdown-item" href="{{ url('outbound/done') }}">Shipped</a>
                                        <a class="dropdown-item" href="{{ url('order/canceled') }}">Canceled</a>
                                    </div>
                                </div>-->
                            @endif
                            <a class="btn m-btn--pill m-btn--air m-btn m-btn--gradient-from-primary m-btn--gradient-to-info" style="margin-left:15px;" href="@if(!$restrict){{ url('order?r=0') }}@else{{ url('order') }}@endif"><i class="la la-exchange"></i> Switch @if($restrict){{'Back'}}@endif</a>
                        @endif
                    </h3>
                </div>
            </div>
            @if(!$restrict)
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
                                                        <a href="#" class="m-nav__link" id="bulk-btn">
                                                            <i class="m-nav__link-icon la la-upload"></i>
                                                            <span class="m-nav__link-text">Bulk Upload</span>
                                                        </a>
                                                    </li>
                                                    <li class="m-nav__item">
                                                        @if(Auth::user()->roles == 'client')
                                                        <a href="{{ url('format/order-sample-client.xlsx') }}" class="m-nav__link">
                                                        @else
                                                        <a href="{{ url('format/order-sample.xlsx') }}" class="m-nav__link">
                                                        @endif
                                                            <i class="m-nav__link-icon la la-download"></i>
                                                            <span class="m-nav__link-text">Bulk Format</span>
                                                        </a>
                                                    </li>
                                                    <li class="m-nav__item">
                                                        <a href="#" id="download-orders" class="m-nav__link">
                                                            <i class="m-nav__link-icon la la-download"></i>
                                                            <span class="m-nav__link-text">Download Orders</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
        <div class="m-portlet__body">

            @include('notif')

            <form action="{{ url('order/barcode') }}" id="orders-print" method="post">
                {{ csrf_field() }}
                <input type="hidden" id="n" name="n"/>
                @if($restrict)<input type="hidden" name="restricted" value="true"/>@endif
            </form>

            <!--begin: Search Form -->
            <form class="m-form m-form--fit m--margin-bottom-20">
                <div class="row m--margin-bottom-20">
                    @if(Auth::user()->roles != 'client')
                    <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Client:</label>
                        <select class="form-control m-input m-select2" name="client-name" data-col-index="2">
                            <option value=""> -- All Clients -- </option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Ship Date:</label>
                        <div class="input-daterange input-group" id="m_datepicker">
                            <input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-7 days')) }}" name="start" placeholder="From" data-col-index="5" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
                            </div>
                            <input type="text" class="form-control m-input" name="end" value="{{ date('Y-m-d') }}" placeholder="To" data-col-index="5" />
                        </div>
                    </div>
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Status:</label>
                        <select class="form-control m-input m-select2" name="status" data-col-index="6">
                            <option value=""> -- All Status -- </option>
                            @foreach(\App\Order::orderTypeList() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Warehouse:</label>
                        <select class="form-control m-input m-select2" name="warehouse" data-col-index="6">
                            <option value=""> -- All Warehouse -- </option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Due Date:</label>
                        <div class="input-daterange input-group" id="m_datepicker">
                            <input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-7 days')) }}" name="due_start" placeholder="From" data-col-index="5" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
                            </div>
                            <input type="text" class="form-control m-input" name="due_end" value="{{ date('Y-m-d', strtotime('+2 days', time())) }}" placeholder="To" data-col-index="5" />
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

            <!--begin: Datatable -->
            <table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
                <thead>
                <tr>
                    <th width="5%" style="text-align:center;">&nbsp;</th>
                    <th width="10%">Order Number</th>
                    <th width="10%">Source</th>
                    @if(Auth::user()->roles != 'client')
                        <th width="10%">External Order Number</th>
                        <th width="10%">Client</th>
                    @endif
                    <th width="10%">Customer</th>
                    <th width="15%">Products</th>
                    <th width="10%">Courier</th>
                    <th width="10%">Shipping Number</th>
                    @if(Auth::user()->roles == 'client')
                        <th width="10%">Shipping Cost</th>
                    @endif
                    <th width="10%">Due Date</th>
                    <th width="10%">Date</th>
                    <th width="10%">Status</th>
                    <th width="10%">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="11" class="dataTables_empty">Loading data from server</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th width="5%" style="text-align:center;">&nbsp;</th>
                    <th width="10%">Order Number</th>
                    <th width="10%">Source</th>
                    @if(Auth::user()->roles != 'client')
                        <th width="10%">External Order Number</th>
                        <th width="10%">Client</th>
                    @endif
                    <th width="10%">Customer</th>
                    <th width="15%">Products</th>
                    <th width="10%">Courier</th>
                    <th width="10%">Shipping Number</th>
                    @if(Auth::user()->roles == 'client')
                        <th width="10%">Shipping Cost</th>
                    @endif
                    <th width="10%">Due Date</th>
                    <th width="10%">Date</th>
                    <th width="10%">Status</th>
                    <th width="10%">Actions</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('script')
    <script src="{{ asset('mt/default/assets/vendors/custom/datatables/datatables.bundle.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/vendors/jquery-throttle-debounce/jquery.ba-throttle-debounce.js') }}" type="text/javascript"></script>
    <script>
        var variables = {
            e:null,
            loads: {
                _token: "{{ csrf_token() }}", 
                copy: "{{ $restrict }}",
                client:$('select[name="client-name"]').val(),
                start_date:$('input[name="start"]').val(),
                end_date:$('input[name="end"]').val(),
                status:$('select[name="status"]').val(),
                warehouse:$('select[name="warehouse"]').val(),
                start_due_date:$('input[name="due_start"]').val(),
                end_due_date:$('input[name="due_end"]').val(),
            }
        };
        var e;
        (e = $("#m_table_1").DataTable({
            responsive: !0,
            processing: true,
            serverSide: true,
            select: {
                style: "multi",
                selector: "td:first-child .m-checkable"
            },
            headerCallback: function (e, a, t, n, s) {
                e.getElementsByTagName("th")[0].innerHTML = '\n<label class="m-checkbox m-checkbox--single m-checkbox--solid m-checkbox--brand">\n<input type="checkbox" value="" class="m-group-checkable">\n                        <span></span>\n</label>'
            },
            columnDefs: [{
                targets: 0,
                orderable: !1,
                render: function (e, a, t, n) {
                    return '\n<label class="m-checkbox m-checkbox--single m-checkbox--solid m-checkbox--brand">\n<input type="checkbox" value="" class="m-checkable">\n<span></span>\n</label>'
                }
            }],
            ajax: {
                url: "{{ route('order-list') }}",
                dataType: "json",
                type: "POST",
                data: function ( d ) {
                    d.search.value = $('input[type="search"]').val()
                    return  $.extend(d, variables.loads);
                }
            },
            columns: [
                {data: 'id'},
                {data: 'order_number'},
                {data : 'source'},
                @if(Auth::user()->roles != 'client'){data : 'external_order_number'},{data: 'client_name'}, @endif
                {
                    data: 'customer_name'
                },
                {data: 'details'},
                {data: 'courier'},
                {data: 'no_resi'},
                @if(Auth::user()->roles == 'client'){data: 'shipping_cost'}, @endif
                {data: 'due_date'},
                {
                    data: 'created_at'
                },
                {data: 'status'},
                {data: 'action'}
            ],
            dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
            buttons: ["print", "excelHtml5", "csvHtml5",
            // {
            //     text: 'Print Label',
            //     action: function (x, dt, node, config) {
            //         printBulk();
            //     }
            // }
            , {
                text: 'Delete',
                action: function (x, dt, node, config) {
                    deleteBulk();
                }
            }]
        })).on("change", ".m-group-checkable", function () {
            var a = $(this).closest("table").find("td:first-child .m-checkable"),
                    t = $(this).is(":checked");
            $(a).each(function () {
                t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
            })
        });
        variables.e = e;

        // Datatables Search, Change behaviour
        $(".dataTables_filter input")
            .unbind()
            .bind("input", $.debounce(800, function(evt) { 
                // If the length is 3 or more characters, or the user pressed ENTER, search
                if (this.value.length >= 3 || e.keyCode == 13)
                    e.search(this.value).draw(); // Call the API search function
                
                // Ensure we clear the search if they backspace far enough
                if (this.value.length == 0)
                    e.search("").draw();
            }));

        function printBulk() {
            var data = e.rows({selected: true}).data(),
                    ids = [];

            $.each(data, function (i, v) {
                ids.push(v.order_number);
            });

            $('#n').val(ids.join(',')).closest('#orders-print').submit();
        }

        function deleteBulk() {
            var data = e.rows({selected: true}).data(),
                    ids = [];

            $.each(data, function (i, v) {
                ids.push(v.order_number);
            });

            $('input[name="order_id"]').val(ids.join(',')).closest('#m_modal_1').modal('show');
        }

        $('.m-select2').select2();

        $('#adv-button').click(function(){
            variables.loads.client = $('select[name="client-name"]').val();
            variables.loads.start_date = $('input[name="start"]').val();
            variables.loads.end_date = $('input[name="end"]').val();
            variables.loads.status = $('select[name="status"]').val();
            variables.loads.warehouse = $('select[name="warehouse"]').val();
            variables.loads.start_due_date = $('input[name="due_start"]').val();
            variables.loads.end_due_date = $('input[name="due_end"]').val();
            variables.e.ajax.reload();
        });

        $('#download-orders').click(function(e) {
            e.preventDefault();
            window.location.replace("{{ url('order/download') }}?start_date="+$('input[name="start"]').val()
                +"&end_date="+$('input[name="end"]').val()+"&status="+$('select[name="status"]').val()
                +"&client="+$('select[name="client-name"]').val());
        });

        $(".input-daterange").datepicker({
            orientation: "bottom auto",
            todayHighlight: !0,
            format: "yyyy-mm-dd",
            maxSpan: {
                days: 7
            },
        })

        $('body').click(function (e) {
            var $this = $(e.target);
            if ($this.hasClass('delete-btn')) {
                $('#m_modal_1').find('form input[name="order_id"]').val($this.attr('data-id')).end().modal('show');
            }
        });

        $('#bulk-btn').click(function () {
            $('#upload-bulk').click();
        });

        $('#upload-bulk').change(function () {
            $('#bulk-modal').modal('show');
        });

        $('#importing-btn').click(function () {
            $('#bulk-modal').modal('hide');
            $('#loading-modal').modal({
                backdrop: 'static',
                keyboard: false
            });
            $('#bulk-form').submit();
            setTimeout('waitForMsg()', 1000);
        });

        function waitForMsg() {
            $.get("{{ route('order-progress') }}", function (result) {
                if (!result.includes('Complete')) {
                    setTimeout('waitForMsg()', 2500);
                }
                else if (result.includes('Complete') && result.length > 0)
                    setTimeout(() => {
                        $('#loading-modal').modal('hide');
                        $("#progress").text('');
                    }, 2500);

                if (result.length > 0)
                    $("#progress").text(result);
                else
                    setTimeout(() => {
                        $('#loading-modal').modal('hide');
                        $("#progress").text('');
                    }, 2500);
            }).fail(function() {
                setTimeout( 'waitForMsg()', 5000);
            });
        }

        $('#bulk-modal').on('hidden.bs.modal', function (e) {
            $("#upload-bulk").val('');
        });
    </script>
    <!--end::Page Resources -->
@endsection