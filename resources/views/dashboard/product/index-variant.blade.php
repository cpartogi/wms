@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
<div class="m-portlet m-portlet--mobile">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					{{ $product->name }}'s Variant
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('product/edit').'/'.$product->id }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to Product</span>
						</span>
					</a>
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
					<th width="3%">&nbsp;</th>
					<th width="9%">Price</th>
					<th width="10%">Warehouse</th>
					<th width="5%">Size</th>
					<th width="9%">Total Quantity</th>
					<th width="9%">Available Quantity</th>
					@if (\Auth::user()->roles != 'client')
					<th width="9%">Adjustment Quantity</th>
					@endif
					<th width="9%">Ordered Quantity</th>
					<th width="9%">Processing Quantity</th>
					<th width="9%">Shipped Quantity</th>
					<th width="9%">Rejected Quantity</th>
				</tr>
			</thead>
			<tbody>
				@if(count($variant) > 0)
					@foreach($variant as $var)
					@if($var->color != null)
					<tr>
						<td style="text-align:center;">&nbsp;</td>
						<td>
							<img src="http://13.229.209.36:3006/assets/client-image.png" width="50"/>
						</td>
						<td>Rp {{ $var->price }}</td>
						<td>{{ $var->warehouse }}</td>
						<td>{{ $var->size_name }}</td>
						<td>{{ $var->total }}</td>
						<td>{{ $var->available }}</td>
						@if (\Auth::user()->roles != 'client')
						<td>{{ $var->adjustment }}</td>
						@endif
						<td>{{ $var->ordered }}</td>
						<td>{{ $var->reserved }}</td>
						<td>{{ $var->outbound }}</td>
						<td>{{ $var->rejected }}</td>
					</tr>
					@endif
					@endforeach
				@endif
			</tbody>
			<tfoot>
				<tr>
					<th width="5%" style="text-align:center;">&nbsp;</th>
					<th width="3%">&nbsp;</th>
					<th width="9%">Price</th>
					<th width="10%">Warehouse</th>
					<th width="5%">Size</th>
					<th width="9%">Total Quantity</th>
					<th width="9%">Available Quantity</th>
					@if (\Auth::user()->roles != 'client')
					<th width="9%">Adjustment Quantity</th>
					@endif
					<th width="9%">Ordered Quantity</th>
					<th width="9%">Processing Quantity</th>
					<th width="9%">Shipped Quantity</th>
					<th width="9%">Rejected Quantity</th>
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

    $('.delete-btn').click(function(){
    	var $this = $(this);
    	$('#m_modal_1').find('form').attr('action','/product/delete/'+$this.attr('data-id')).end().modal('show');

    });
</script>
<!--end::Page Resources -->
@endsection