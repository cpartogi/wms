@extends('layouts.base',[
    'page' => 'Inbound'
])

@section('modal')
    @if(Auth::user()->roles != 'investor')
        <!--begin::Modal-->
        <div class="modal fade" id="bulk-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ url('inbound/bulk') }}" method="post" enctype="multipart/form-data" id="bulk-form">
                    {{ csrf_field() }}
                    <input type="file" name="bulk-inbound" id="upload-bulk" accept=".xls,.xlsx" style="display:none;"/>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Inbound Bulk Upload</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group m-form__group">
                                <label for="arrival_date">Arrival Date:</label>
                                <input type='text' class="form-control" value="{{ old('arrival_date') ?: date('Y-m-d H:i') }}" placeholder="Pick arrival date" name="arrival_date" id='arrival_date' autocomplete="off"/>
                            </div>
                            @if(Auth::user()->roles == 'client')
                                <input type="hidden" name="status" value="REGISTER"/>
                            @else
                            <div class="form-group m-form__group">
                                <label for="status">Status:</label>
                                <select class="form-control" name="status" required="">
                                    <option value="REGISTER">Register</option>
                                    <option value="RETURN">Return</option>
                                    <option value="EVENT">Event</option>
                                </select>
                            </div>
					        @endif
                            <div class="form-group m-form__group">
                                <label for="courier">Courier:</label>
                                <input type="text" class="form-control m-input" name="courier" />
                                <span class="m-form__help">The courier agent who sent the package</span>
                            </div>
                            <div class="form-group m-form__group">
                                <label for="sender_name">Sender Name:</label>
                                <input type="text" class="form-control m-input" name="sender_name" />
                                <span class="m-form__help">Person who send the package</span>
                            </div>
                            <div class="form-group m-form__group">
                                <label for="shipping_cost">Courier Cost:</label>
                                <input type="text" class="form-control m-input" name="shipping_cost" />
                                <span class="m-form__help">Cost of the delivery</span>
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
    @if(Auth::user()->roles != 'client' && Auth::user()->roles != 'investor')
    <!--begin::Modal-->
    <div class="modal fade" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ url('inbound/delete') }}" method="post" id="delete-form">
                {{ csrf_field() }}
                <input type="hidden" name="batch-id" id="batch-id"/>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Inbound Batch Removal</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure to delete this inbound batch? All inbound details will be removed.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
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
                    <p id="progress">Please wait, we are generating the inbound details. Please be patient :)</p>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal-->
@endsection

@section('content')
    <div class="m-portlet m-portlet--mobile">
        <div class="clearfix"></div>
	
	    <div class="m-portlet__head">
            <div class="m-portlet__head-caption">
                <div class="m-portlet__head-title">
                    <h3 class="m-portlet__head-text">
                        List of Inbound
                    </h3>
                </div>
            </div>
            <div class="m-portlet__head-tools">
                <ul class="m-portlet__nav">
                    @if(Auth::user()->roles != 'investor')
                    <li class="m-portlet__nav-item">
                        <a href="{{ url('inbound/add') }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
                            <span>
                                <i class="la la-plus"></i>
                                <span>New Inbound</span>
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
                                                <a href="@if(Auth::user()->roles == 'client'){{ url('format/inbound-sample-client.xlsx') }}@else{{ url('format/inbound-sample.xlsx') }}@endif" class="m-nav__link">
                                                    <i class="m-nav__link-icon la la-download"></i>
                                                    <span class="m-nav__link-text">Bulk Format</span>
                                                </a>
                                            </li>
                                            
                                            <li class="m-nav__item">
                                                <a href="#" id="download-inbounds" class="m-nav__link">
                                                    <i class="m-nav__link-icon la la-download"></i>
                                                    <span class="m-nav__link-text">Download Inbounds</span>
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
	    </div>

        <div class="m-portlet__body">

            @include('notif')

            <!--begin: Search Form -->
            <form class="m-form m-form--fit m--margin-bottom-20">
                <div class="row m--margin-bottom-20">
                    <div class="@if(Auth::user()->roles == 'client'){{'col-lg-6'}}@else{{'col-lg-4'}}@endif m--margin-bottom-10-tablet-and-mobile" style="padding: 10px">
                        <label>Inbound Date:</label>
                        <div class="input-daterange input-group" id="m_datepicker">
                            <input type="text" class="form-control m-input" value="{{ date('Y-m-d',strtotime('-30 days')) }}" name="start" placeholder="From" data-col-index="5" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="la la-ellipsis-h"></i></span>
                            </div>
                            <input type="text" class="form-control m-input" name="end" value="{{ date('Y-m-d') }}" placeholder="To" data-col-index="5" />
                        </div>
                    </div>
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
            <table class="table table-striped- table-bordered table-hover table-checkable" id="inbound_table">
                <thead>
                <tr>
                    <th>&nbsp;</th>
					<th>Batch ID</th>
					<th>Source</th>
                    <th>External Inbound Batch</th>
                    <th>Products</th>
                    <th>Client</th>
					<th>Arrival Date</th>
					<th>P/V</th>
					<th>Status</th>
					<th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="11" class="dataTables_empty">Loading data from server</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th>&nbsp;</th>
					<th>Batch ID</th>
					<th>Source</th>
                    <th>External Inbound Batch</th>
					<th>Products</th>
                    <th>Client</th>
					<th>Arrival Date</th>
                    <th>P/V</th>
					<th>Status</th>
					<th>Action</th>
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
                client:$('select[name="client-name"]').val(),
                start_date:$('input[name="start"]').val(),
                end_date:$('input[name="end"]').val()
            }
        };
        var e;
        (e = $("#inbound_table").DataTable({
            responsive: !0,
            processing: true,
            serverSide: true,
            ordering: false,
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
                url: "{{ route('inbound-list') }}",
                dataType: "json",
                type: "POST",
                data: function ( d ) {
                    d.search.value = $('input[type="search"]').val()
                    return  $.extend(d, variables.loads);
                }
            },
            columns: [
                { data: ''},
        	    { data: 'id'},
			    { data: "source"},
			    {data : "external_inbound_batch"},
                { data: "product_name" },
			    { data: "client_name"},
                { data: "arrival_date" },
                { data: "products"},
			    { data: "status"},
                { data: "action" }
            ],
            dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
            buttons: ["print", "copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"]
        })).on("change", ".m-group-checkable", function() {
            var a = $(this).closest("table").find("td:first-child .m-checkable"),
                t = $(this).is(":checked");
            $(a).each(function() {
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
            variables.e.ajax.reload();
        });

        $('#download-inbounds').click(function(e) {
            e.preventDefault();
            @if(Auth::user()->roles != 'client')
            window.location.replace("{{ url('inbound/download') }}?start_date="+$('input[name="start"]').val()
                +"&end_date="+$('input[name="end"]').val()+"&client="+$('select[name="client-name"]').val()+"&search="+$('input[type="search"]').val());
            @else
            window.location.replace("{{ url('inbound/download') }}?start_date="+$('input[name="start"]').val()
                +"&end_date="+$('input[name="end"]').val()+"&client=&search="+$('input[type="search"]').val());
            @endif    
        });

        $(".input-daterange").datepicker({
            orientation: "bottom auto",
            todayHighlight: !0,
            format: "yyyy-mm-dd",
            maxSpan: {
                days: 7
            },
        })

        $('body').bind('click',function(e){
    	    var $this = $(e.target);

    	    if($this.hasClass('delete-batch')){
    		    $('#batch-id').val($this.attr('data-id'));
    		    $('#delete-modal').modal('show');
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

       
        $('#delete-modal').on('hidden.bs.modal', function (e) {
	    	$('#batch-id').val('');
	    });
    </script>
    <!--end::Page Resources -->
@endsection