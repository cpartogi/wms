@extends('layouts.base',[
    'page' => 'Order'
])

@section('modal')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="post" action="{{ url('/order/revalidate') }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="request_id" value=""/>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Revalidate Order</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure to revalidate selected Orders?</p>
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
@endsection

@section('content')
    <div class="m-portlet m-portlet--mobile">
        <div class="m-portlet__body">

            @include('notif')
            <!--begin: Search Form -->
            <form class="m-form m-form--fit m--margin-bottom-20">
                <div class="row m--margin-bottom-20">
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Created Date:</label>
                        <div class="input-daterange input-group" id="m_datepicker">
                            <input type="text" class="form-control m-input" name="start_date" value="{{ date('Y-m-d',strtotime('-18 days')) }}" placeholder="From" data-col-index="5" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
                            </div>
                            <input type="text" class="form-control m-input" name="end_date" value="{{ date('Y-m-d') }}" placeholder="To" data-col-index="5" />
                        </div>
                    </div>
                    <div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Status:</label>
                        <select class="form-control m-input m-select2" name="status" data-col-index="2">
                            <option value=""> -- All Status -- </option>
                            @foreach(\Config::get('constants.order_issue_status') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>External Order Number:</label>    
                        <input type="text" class="form-control m-input" name="external_order_number" placeholder="External Order Number" class="m-messenger__form-input">
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
                    <th width="10%">External ID</th>
                    <th width="10%">External Order Number</th>
                    <th width="10%">Status</th>
                    <th width="15%">Created At</th>
                    <th width="10%">Updated At</th>
                    <th width="10%">Action</th>
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
                    <th width="10%">External ID</th>
                    <th width="10%">External Order Number</th>
                    <th width="10%">Status</th>
                    <th width="15%">Created At</th>
                    <th width="10%">Updated At</th>
                    <th width="10%">Action</th>
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
                created_at__gte : $('input[name="start_date"]').val(),
                created_at__lte : $('input[name="end_date"]').val(),
                status : $('select[name="status"]').val(),
                external_order_number : $('input[name="external_order_number"]').val(),
                sort_by:"-created_at",
            }
        };
        var e;
        (e = $("#m_table_1").DataTable({
            responsive: !0,
            processing: true,
            serverSide: true,
            bFilter: false,
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
                url: "{{ route('order-issue-list') }}",
                dataType: "json",
                type: "GET",
                data: function ( d ) {
                    return  $.extend(d, variables.loads);
                }
            },
            columns: [
                {data: 'request_id'},
                {data: 'external_id'},
                {data : 'external_order_number'},
                {data: 'status'},
                {data: 'created_at'},
                {data: 'updated_at'},
                {data: 'action'}
            ],
            dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
            buttons: [
            {
                text: 'Revalidate Order',
                action: function (x, dt, node, config) {
                    revalidateBulk();
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

        function revalidateBulk() {
            var data = e.rows({selected: true}).data(),
                    requestIDs = [];

            $.each(data, function (i, v) {
                requestIDs.push(v.request_id);
            });

            $('input[name="request_id"]').val(requestIDs.join(',')).closest('#m_modal_1').modal('show');
        }

        $('.m-select2').select2();

        $('#adv-button').click(function(){
            variables.loads.created_at__gte = $('input[name="start_date"]').val();
            variables.loads.created_at__lte = $('input[name="end_date"]').val();
            variables.loads.status = $('select[name="status"]').val();
            variables.loads.external_order_number = $('input[name="external_order_number"]').val();
            variables.e.ajax.reload();
        });

        $(".input-daterange").datepicker({
            orientation: "bottom auto",
            todayHighlight: !0,
            format: "yyyy-mm-dd",
            maxSpan: {
                days: 7
            },
        })
    </script>
    <!--end::Page Resources -->
@endsection