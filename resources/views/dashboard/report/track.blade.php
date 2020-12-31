@extends('layouts.base',[
    'page' => 'Report'
])

@section('modal')
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
                    <h3 class="m-portlet__head-text">Trace &amp; Track</h3>
                </div>
            </div>
        </div>
        <div class="m-portlet__body">

            @include('notif')

            <!--begin: Search Form -->
            <form class="m-form m-form--fit m--margin-bottom-20">
                <div class="row m--margin-bottom-20">
                    @if(Auth::user()->roles != 'client')
                    <div class="col-lg-6 m--margin-bottom-10-tablet-and-mobile">
                        <label>Client:</label>
                        <select class="form-control m-input m-select2" name="client-name" data-col-index="2">
                            <option value=""> -- All Clients -- </option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-lg-6 m--margin-bottom-10-tablet-and-mobile">
                        <label>Ship Date:</label>
                        <div class="input-daterange input-group" id="m_datepicker">
                            <input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-7 days')) }}" name="start" placeholder="From" data-col-index="5" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
                            </div>
                            <input type="text" class="form-control m-input" name="end" value="{{ date('Y-m-d') }}" placeholder="To" data-col-index="5" />
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
                        <th width="6%" style="text-align:center;">&nbsp;</th>
                        <th width="12%">Order Number</th>
                        @if(Auth::user()->roles != 'client')
                            <th width="10%">Client</th>
                        @endif
                        <th width="12%">Customer</th>
                        <th width="12%">Courier</th>
                        <th width="12%">Shipping Number</th>
                        <th width="12%">Status</th>
                        <th width="12%">Name</th>
                        <th style="display:none;" width="12%">Updated At</th>
                        <th width="12%">Trackings</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="10" class="dataTables_empty">Loading data from server</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th width="6%" style="text-align:center;">&nbsp;</th>
                        <th width="12%">Order Number</th>
                        @if(Auth::user()->roles != 'client')
                            <th width="10%">Client</th>
                        @endif
                        <th width="12%">Customer</th>
                        <th width="12%">Courier</th>
                        <th width="12%">Shipping Number</th>
                        <th width="12%">Status</th>
                        <th width="12%">Name</th>
                        <th width="12%">Updated At</th>
                        <th width="12%">Trackings</th>
                    </tr>
                </tfoot>
            </table>
        </div>
            <div class="modal fade" id="modal_status_trackings" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Status Trackings</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <span id="lbl_status_trackings" >Default</span>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">OK</button>
                            </div>
                        </div>
                </div>
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
                client:$('select[name="client-name"]').val(),
                start_date:$('input[name="start"]').val(),
                end_date:$('input[name="end"]').val()
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
                url: "{{ route('report-list') }}",
                dataType: "json",
                type: "POST",
                data: function ( d ) {
                    return  $.extend(d, variables.loads);
                }
            },
            columns: [
                {data: 'id'},
                {data: 'order_number'},
                @if(Auth::user()->roles != 'client'){data: 'client_name'}, @endif
                {data: 'customer_name'},
                {data: 'courier'},
                {data: 'no_resi'},
                {data: 'status'},
                {data: 'name'},
                {data: 'updated_at'},
                {data: 'button_trackings'}
            ],
            dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
            buttons: ["print", "excelHtml5", "csvHtml5"]
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

        $('.m-select2').select2();

        $('#adv-button').click(function(){
            variables.loads.client = $('select[name="client-name"]').val();
            variables.loads.start_date = $('input[name="start"]').val();
            variables.loads.end_date = $('input[name="end"]').val();
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

        function showTrackings(that){
            var $this = $(that);
            var $index = $this.attr('data-value');
            $('#modal_status_trackings').modal('show');
            $('#lbl_status_trackings').html($index);
        }
    </script>
    <!--end::Page Resources -->
@endsection