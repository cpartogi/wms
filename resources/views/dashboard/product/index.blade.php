@extends('layouts.base',[
    'page' => 'Product'
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
				<p>Please wait, we are generating products. Please be patient :)</p>
			</div>
		</div>
	</div>
</div>
<!--end::Modal-->
@if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')
<!--begin::Modal-->
<div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<form method="post" action="{{ url('product/delete') }}">
			{{ csrf_field() }}
			<input type="hidden" id="products" name="p" />
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Delete Product</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>Are you sure to delete selected products?</p>
					<div class="form-group m-form__group">
						<label class="m-checkbox">
							<input type="checkbox" name="forced" value="1" /> Delete includes all related data?
							<span></span>
						</label><br>
						<span class="m-form__help">By enabling this, all related inbound and order to this product also deleted.</span>
					</div>
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
@endsection

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					List of Product
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				@if(Auth::user()->roles != 'investor')
				<li class="m-portlet__nav-item">
					<a href="{{ url('product/add') }}" class="btn btn-info m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-plus"></i>
							<span>New Product</span>
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
											<form action="{{ url('product/bulk') }}" method="post" enctype="multipart/form-data" style="display:none;" id="bulk-form">
												{{ csrf_field() }}
												<input type="file" name="bulk-product" id="upload-bulk" accept=".xls,.xlsx"/>
											</form>
											<a href="#" class="m-nav__link" id="bulk-btn">
												<i class="m-nav__link-icon la la-upload"></i>
												<span class="m-nav__link-text">Bulk Upload</span>
											</a>
										</li>
										@if(Auth::user()->roles == 'client')
										<li class="m-nav__item">
											<a href="{{ url('format/product-sample-client.xlsx') }}" class="m-nav__link" download>
												<i class="m-nav__link-icon la la-download"></i>
												<span class="m-nav__link-text">Bulk Format</span>
											</a>
										</li>
										@else
										<li class="m-nav__item">
											<a href="{{ url('format/product-sample.xlsx') }}" class="m-nav__link" download>
												<i class="m-nav__link-icon la la-download"></i>
												<span class="m-nav__link-text">Bulk Format</span>
											</a>
										</li>
										@endif
										<li class="m-nav__item">
											<a  href="#" id="download-stocks" class="m-nav__link">
												<i class="m-nav__link-icon la la-download"></i>
												<span class="m-nav__link-text">Download Stocks</span>
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

		<form class="m-form m-form--fit m--margin-bottom-20">
			<div class="row m--margin-bottom-20">
				@if(Auth::user()->roles != 'client')
				<div class="col-lg-4 m--margin-bottom-10-tablet-and-mobile">
					<label>Client:</label>
					<select class="form-control m-input m-select2" name="client-name" data-col-index="2">
						@foreach($clients as $client)
							<option value="{{ $client->id }}">{{ $client->name }}</option>
						@endforeach
					</select>
				</div>
				<div class="col-lg-2">
					<button type="button" id="adv-button" class="btn btn-brand m-btn m-btn--icon" id="m_search" style="margin-top: 25px">
						<span>
							<i class="la la-search"></i>
							<span>Search</span>
						</span>
					</button>
				</div>
				@endif
			</div>
		</form>

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="10%">&nbsp;</th>
					<th width="10%">Source</th>
					<th width="10%">Product Type</th>
					@if(Auth::user()->roles == 'client')
					<th width="20%">Name</th>
					<th width="20%">Color</th>
					@else
					<th width="10%">External SKU</th>
					<th width="15%">Name</th>
					<th width="15%">Client</th>
					<th width="10%">Color</th>
					@endif
					<th width="10%">Data Added</th>
					<th width="20%">Actions</th>
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
					<th width="10%">&nbsp;</th>
					<th width="10%">Source</th>
					<th width="10%">Product Type</th>
					@if(Auth::user()->roles == 'client')
					<th width="20%">Name</th>
					<th width="20%">Color</th>
					@else
					<th width="10%">External SKU</th>
					<th width="15%">Name</th>
					<th width="15%">Client</th>
					<th width="10%">Color</th>
					@endif
					<th width="10%">Data Added</th>
					<th width="20%">Actions</th>
				</tr>
			</tfoot>
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
    var e,
    	modes = ["print", "copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"];
	@if(Auth::user()->roles != 'crew')
	modes = ["print", "copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5", {
                text: 'Delete',
                action: function ( x, dt, node, config ) {
                    var data = e.rows( { selected: true } ).data(),
                    	ids = [];
                    $.each(data, function(i,v){
                    	ids.push(v.id);
                    });

                    $('#m_modal_1').find('#products').val(ids.join(',')).end().modal('show');
                }
            }];
	@endif

	$('.m-select2').select2();
	var variables = {
		e: null,
		loads: {
			_token: "{{ csrf_token() }}", 
			client:$('select[name="client-name"]').val(),
		}
	};

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
        	url: "{{ route('product-list') }}",
        	dataType: "json",
        	type: "POST",
        	data: function ( d ) {
				return  $.extend(d, variables.loads);
			}
        },
        columns: [
        	{ data: 'id' },
        	{
                data: "image_url",
                render: function ( url ) {
                    return '<img src="'+url+'" width="50"/>';
                },
                defaultContent: "No image",
                title: "Image"
            },
            { data: 'source' },
            { data: 'product_type' },
			@if(Auth::user()->roles != 'client'){ data: 'external_sku' },@endif
            { data: 'name' },
            @if(Auth::user()->roles != 'client'){ data: 'client' },@endif
            { data: 'color' },
            { data: 'data_added' },
            { data: 'action' }
        ],
        dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
        buttons: modes
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });
	variables.e = e;

	$('#adv-button').click(function(){
		variables.loads.client = $('select[name="client-name"]').val();
		variables.e.ajax.reload();
	});

    $('body').on('click',function(e){
    	var $this = $(e.target);
    	if($this.hasClass('delete-btn')){
    		$('#m_modal_1').find('#products').val($this.attr('data-id')).end().modal('show');
    	}
    });
	
	$('#download-stocks').click(function(e) {
		e.preventDefault();
		window.location.replace("{{ url('product/download') }}?client="+$('select[name="client-name"]').val());
	});

    $('#bulk-btn').click(function(){
    	$('#upload-bulk').click();
    });

    $('#upload-bulk').change(function(){
    	$('#loading-modal').modal({
    		backdrop: 'static',
    		keyboard: false
    	});
    	$('#bulk-form').submit();
    })
</script>
<!--end::Page Resources -->
@endsection