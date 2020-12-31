@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
<script>
	function printDiv(divName) {
	 var printContents = document.getElementById(divName).innerHTML;
	 var originalContents = document.body.innerHTML;

	 document.body.innerHTML = printContents;

	 window.print();

	 document.body.innerHTML = originalContents;
	}
</script>
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Product Details
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('product/edit/').'/'.$product->product_id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Product</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<form class="m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed">
		<div class="m-portlet__body">
			<div class="form-group m-form__group row">
				<div class="col-lg-4">
					<label>Client:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $product->client_name }}"/>
				</div>
				<div class="col-lg-4">
					<label>Product Name:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $product->product_name }}"/>
				</div>
				<div class="col-lg-4">
					<label>Product Type:</label>
					<input type="text" class="form-control m-input" readonly value="{{ $product->type_name }}"/>
				</div>
			</div>
			<div class="form-group m-form__group row">
				@php
					$dimension = null;
					if(isset($product->dimension)){
						$dimension = json_decode($product->dimension);
					}
				@endphp
				<div class="col-lg-6">
					<label>Product Dimension ( W x H x D ):</label>
					<div class="row">
						<input type="number" class="form-control col-sm-4" value="@if(isset($dimension->w)){{ $dimension->w }}@endif" readonly />
						<input type="number" class="form-control col-sm-4" value="@if(isset($dimension->h)){{ $dimension->h }}@endif" readonly />
						<input type="number" class="form-control col-sm-4" value="@if(isset($dimension->d)){{ $dimension->d }}@endif" readonly />
					</div>
				</div>
				<div class="col-lg-6">
					<label>Price:</label>
					<div class="input-group m-input-group m-input-group--square">
						<div class="input-group-prepend">
							<span class="input-group-text">Rp</span>
						</div>
						<input type="text" class="form-control m-input" readonly value="{{ $product->price }}"/>
					</div>
				</div>
			</div>
		</div>
	</form>
	<!--end::Form-->
</div>

<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Product Location
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<div class="m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push" m-dropdown-toggle="hover" aria-expanded="true">
						<a href="#" class="m-portlet__nav-link btn btn-lg btn-secondary  m-btn m-btn--icon m-btn--icon-only m-btn--pill  m-dropdown__toggle">
							<i class="la la-ellipsis-h m--font-brand"></i>
						</a>
						<div class="m-dropdown__wrapper">
							<span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust"></span>
							<div class="m-dropdown__inner">
								<div class="m-dropdown__body">
									<div class="m-dropdown__content">
										<ul class="m-nav">
											<li class="m-nav__section m-nav__section--first">
												<span class="m-nav__section-text">Actions</span>
											</li>
											<li class="m-nav__item">
												<a href="" class="m-nav__link">
													<i class="m-nav__link-icon flaticon-like"></i>
													<span class="m-nav__link-text">Set as Available</span>
												</a>
											</li>
											<li class="m-nav__item">
												<a href="" class="m-nav__link">
													<i class="m-nav__link-icon flaticon-warning-2"></i>
													<span class="m-nav__link-text">Set as Rejected</span>
												</a>
											</li>
											<li class="m-nav__item">
												<a href="" class="m-nav__link">
													<i class="m-nav__link-icon flaticon-lock-1"></i>
													<span class="m-nav__link-text">Set as Reserved</span>
												</a>
											</li>
											<li class="m-nav__separator m-nav__separator--fit m--hide">
											</li>
											<li class="m-nav__item m--hide">
												<a href="#" class="btn btn-outline-danger m-btn m-btn--pill m-btn--wide btn-sm">Submit</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</div>

	<div class="m-portlet__body">
		@include('notif')

		<!--begin: Datatable -->
		<table class="table table-striped- table-bordered table-hover table-checkable" id="m_table_1">
			<thead>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="15%">Code</th>
					<th width="15%">Location</th>
					<th width="10%">Color</th>
					<th width="10%">Size</th>
					<th width="10%">Stored</th>
					<th width="10%">Reserved</th>
					<th width="10%">Rejected</th>
					<th width="15%">Actions</th>
				</tr>
			</thead>
			<tbody>
				@foreach($locations as $var)
				<tr>
					<td style="text-align:center;">&nbsp;</td>
					<td>{{ $var->code }}</td>
					<td>@if($var->shelf_name != null){{ $var->shelf_name }}<br>{{ $var->warehouse }}@else{{'-'}}@endif</td>
					<td>{{ $var->color }}</td>
					<td>{{ $var->size_name }}</td>
					<td>@if($var->date_stored != null){{ date('d M Y H:i',strtotime($var->date_stored)) }}@else{{'-'}}@endif</td>
					<td>@if($var->date_ordered != null && $var->date_picked != null && $var->date_outbounded == null){{ 'Yes' }}@else{{ 'No' }}@endif</td>
					<td>@if($var->date_rejected != null){{ 'Yes' }}@else{{ 'No' }}@endif</td>
					<td>
						<a href="{{ url('inbound/barcode/single').'/'.$var->id }}" class="btn btn-primary" target="_blank"><i class="fa fa-print"></i> Print Barcode</a>
					</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="15%">Code</th>
					<th width="15%">Location</th>
					<th width="10%">Color</th>
					<th width="10%">Size</th>
					<th width="10%">Stored</th>
					<th width="10%">Reserved</th>
					<th width="10%">Rejected</th>
					<th width="15%">Actions</th>
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
	var e;
	(e = $("#m_table_1").DataTable({
        responsive: !0,
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
        dom: "<'row'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>\n\t\t\t<'row'<'col-sm-12'tr>>\n\t\t\t<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
        buttons: ["print", "copyHtml5", "excelHtml5", "csvHtml5", "pdfHtml5"]
    })).on("change", ".m-group-checkable", function() {
        var a = $(this).closest("table").find("td:first-child .m-checkable"),
            t = $(this).is(":checked");
        $(a).each(function() {
            t ? ($(this).prop("checked", !0), e.rows($(this).closest("tr")).select()) : ($(this).prop("checked", !1), e.rows($(this).closest("tr")).deselect())
        })
    });
</script>
<!--end::Page Resources -->
@endsection