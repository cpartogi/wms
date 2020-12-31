@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					Adjustment Details
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('product/adjustment') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>
	<div class="m-portlet__body">
		<div class="form-group m-form__group row">
			<div class="col-lg-4">
				<label>Product Name:</label>
				<input type="text" class="form-control m-input" readonly value="{{ $detail->product_name }}"/>
			</div>
			<div class="col-lg-4">
				<label>Client:</label>
				<input type="text" class="form-control m-input" readonly value="{{ $detail->client_name }}"/>
			</div>
			<div class="col-lg-4">
				<label>Size:</label>
				<input type="text" class="form-control m-input" readonly value="{{ $detail->size_name }}"/>
			</div>
		</div>
		<div class="form-group m-form__group row">
			<div class="col-lg-6">
				<label>Batch:</label>
				<input type="text" class="form-control m-input" readonly value="{{ '#'.str_pad($detail->batch_id,5,'0',STR_PAD_LEFT) }}"/>
			</div>
			<div class="col-lg-6">
				<label>Status:</label>
				<input type="text" class="form-control m-input" readonly value="@if($detail->status == 1){{ 'Adjusted' }}@else{{ 'On Checking' }}@endif"/>
			</div>
		</div>
	</div>
</div>

<div class="m-portlet m-portlet--full-height">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<h3 class="m-portlet__head-text">
					{{ $detail->product_name }}'s Log
				</h3>
			</div>
		</div>
	</div>
	<div class="m-portlet__body">
		<div class="tab-content">
			<div class="tab-pane active" id="m_widget2_tab1_content" aria-expanded="true">
				<!--begin:Timeline 1-->
				<div class="m-timeline-1 m-timeline-1--fixed">
					<div class="m-timeline-1__items">
						<div class="m-timeline-1__marker"></div>
						<div class="m-timeline-1__item m-timeline-1__item--left m-timeline-1__item--first">
							<div class="m-timeline-1__item-circle">
								<div class="m--bg-danger"></div>
							</div>
							<div class="m-timeline-1__item-arrow"></div>
							<span class="m-timeline-1__item-time m--font-brand">{{ date('d M Y',strtotime($log->arrival_date)) }}<span>{{ date('H:i',strtotime($log->arrival_date)) }}</span></span>
							<div class="m-timeline-1__item-content">
								<div class="m-timeline-1__item-title">
									Item Arrived at Pakdé
								</div>
								<div class="m-timeline-1__item-body">
									Batch : {{ '#'.str_pad($log->batch_id,5,'0',STR_PAD_LEFT) }}<br>
									Sender : {{ $log->sender_name }}<br>
									Courier : {{ $log->courier }}<br>
									Receiver : {{ $log->receiver }}
								</div>
								<div class="m-timeline-1__item-actions">
									<a href="{{ url('inbound/edit/'.$log->batch_id) }}" class="btn btn-sm btn-outline-brand m-btn m-btn--pill m-btn--custom">View Details</a>
								</div>
							</div>
						</div>
						<div class="m-timeline-1__item m-timeline-1__item--right">
							<div class="m-timeline-1__item-circle">
								<div class="m--bg-danger"></div>
							</div>
							<div class="m-timeline-1__item-arrow"></div>
							<span class="m-timeline-1__item-time m--font-brand">{{ date('d M Y',strtotime($log->date_stored)) }}<span>{{ date('H:i',strtotime($log->date_stored)) }}</span></span>
							<div class="m-timeline-1__item-content">
								<div class="m-timeline-1__item-title">
									Item Stored
								</div>
								<div class="m-timeline-1__item-body">
									Warehouse : {{ $log->warehouse_name }}<br>
									Shelf : {{ $log->shelf_name }}<br>
									Crew : {{ $log->officer }}
								</div>
								<div class="m-timeline-1__item-actions">
									<a href="{{ url('inbound/location/'.$log->batch_id) }}" class="btn btn-sm btn-outline-brand m-btn m-btn--pill m-btn--custom">View Details</a>
								</div>
							</div>
						</div>
						<div class="m-timeline-1__item m-timeline-1__item--left m-timeline-1__item--first">
							<div class="m-timeline-1__item-circle">
								<div class="m--bg-danger"></div>
							</div>
							<div class="m-timeline-1__item-arrow"></div>
							<span class="m-timeline-1__item-time m--font-brand">{{ date('d M Y',strtotime($detail->created_at)) }}<span>{{ date('H:i',strtotime($detail->created_at)) }}</span></span>
							<div class="m-timeline-1__item-content">
								<div class="m-timeline-1__item-title">
									Item On Stock Opnamed
								</div>
								<div class="m-timeline-1__item-body">
									Reporter : {{ $detail->reporter }}
								</div>
							</div>
						</div>
						@if($detail->approver != null)
						<div class="m-timeline-1__item m-timeline-1__item--right">
							<div class="m-timeline-1__item-circle">
								<div class="m--bg-danger"></div>
							</div>
							<div class="m-timeline-1__item-arrow"></div>
							<span class="m-timeline-1__item-time m--font-brand">{{ date('d M Y',strtotime($detail->updated_at)) }}<span>{{ date('H:i',strtotime($detail->updated_at)) }}</span></span>
							<div class="m-timeline-1__item-content">
								<div class="m-timeline-1__item-title">
									Item Adjusted
								</div>
								<div class="m-timeline-1__item-body">
									PIC : {{ $detail->approver }}<br>
								</div>
							</div>
						</div>
						@endif
					</div>
				</div>
				<!--End:Timeline 1-->
			</div>
		</div>
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