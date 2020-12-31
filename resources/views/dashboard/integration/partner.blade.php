@extends('layouts.base',[
    'page' => 'Integration'
])

@section('content')
<!--begin::Portlet-->
<div class="m-portlet">
	<div class="m-portlet__head">
		<div class="m-portlet__head-caption">
			<div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
				<h3 class="m-portlet__head-text">
					{{ $partner_name }}
				</h3>
			</div>
		</div>
		<div class="m-portlet__head-tools">
			<ul class="m-portlet__nav">
				<li class="m-portlet__nav-item">
					<a href="{{ url('integration') }}" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air" id="back-to-list">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
						</span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!--begin::Form-->
	<div class="integration-container">
		<div class="integration-container-left">
			<form class="m-form" id="jubelio-form">
				{{ csrf_field() }}
				<div class="m-portlet__body">
					<div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible hide hide-display" role="alert" id="jubelio-alert">
						<div class="m-alert__icon">
							<i class="flaticon-exclamation-1"></i>
							<span></span>
						</div>
						<div class="m-alert__text">
							<strong>Uh oh!</strong>
							<span id="error-message">The Authentication to Jubelio failed. <span id="error-message-detail">Please check you email/password and try again!</span></span>
						</div>
					</div>
					<div class="m-form__section m-form__section--first">
						<div class="form-group m-form__group">
							<label for="email">Email:</label>
							<input type="text" class="form-control m-input" placeholder="email" name="email" id="integration-email">
							<span class="m-form__help">Please enter email</span>
						</div>
						<div class="form-group m-form__group">
							<label for="email">Password:</label>
							<input type="password" class="form-control m-input" placeholder="password" name="password" id="integration-password">
							<span class="m-form__help">Please enter password</span>
						</div>
					</div>
				</div>
				<div class="m-portlet__foot m-portlet__foot--fit">
					<div class="m-form__actions m-form__actions">
						<button type="submit" class="btn btn-success" id="link-jubelio-button">Link Jubelio account</button>
						<div class="integration-status-container">
							<span id="link-jubelio-in-progress" class="hide-display" >Please wait ...</span>
							<span id="link-jubelio-success" class="hide-display">Authentication Success</span>
						</div>
					</div>
				</div>
			</form>
		</div>
		<div class="integration-container-right">
			<form class="m-form" id="jubelio-form-2" style="display: none;">
				<div class="m-portlet__body">
					<div class="m-form__section m-form__section--first">
						<div class="m-alert m-alert--icon m-alert--icon-solid m-alert--outline alert alert-danger alert-dismissible hide hide-display" role="alert" id="jubelio-get-product-alert">
							<div class="m-alert__icon">
								<i class="flaticon-exclamation-1"></i>
								<span></span>
							</div>
							<div class="m-alert__text">
								<strong>Uh oh!</strong>
								<span id="error-message">Get all product from Jubelio failed. <span id="error-message-detail-get-product"></span></span>
							</div>
						</div>
						<div class="form-group m-form__group">
							<label for="email">Webhook secret key:</label>
							<input type="text" class="form-control m-input" value="{{ $web_secret_key }}" name="web_hook_secret_key" disabled="disabled" id="webhook-secret-key">
						</div>
						<div class="form-group m-form__group">
							<label for="email">Callback URL On Submit Product</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/product/' . $partner_id . '/' . $client_id }}" name="callback_submit_product" disabled="disabled" id="callback-product">
						</div>
						<div class="form-group m-form__group">
							<label for="email">Callback URL On Submit Sales Order</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/salesorder/' . $partner_id . '/' . $client_id }}" name="callback_sales_order" disabled="disabled" id="callback-sales-order">
						</div>
						<div class="form-group m-form__group">
							<label for="email">Callback URL On Submit Purchase Order</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/purchaseorder/' . $partner_id . '/' . $client_id }}" name="callback_purchase_order" disabled="disabled" id="callback-purchase-order">
						</div>
						<div class="form-group m-form__group">
							<button type="submit" class="btn btn-success" id="get-all-product">Get All Products</button>
								<span id="get-product-jubelio-in-progress" class="hide-display" >Please wait ...</span>
								<span id="get-product-jubelio-success" class="hide-display">Sync Product from Jubelio Success</span>
							</div>
						</div>
						<!-- <div class="form-group m-form__group">
							<label for="email">Callback URL On Submit New Sales Return</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/salesreturn/' . $partner_id . '/' .$client_id }}" name="callback_sales_return" disabled="disabled" id="callback-sales-return">
						</div>
						<div class="form-group m-form__group">
							<label for="email">Callback URL On Submit New Stock Transfer</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/stocktransfer/' . $partner_id . '/' . $client_id }}" name="callback_stock_transfer" disabled="disabled" id="callback-stock-tranfer">
						</div>
						<div class="form-group m-form__group">
							<label for="email">Callback URL On Update Stock</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/stock/' . $partner_id . '/' . $client_id }}" name="callback_stock" disabled="disabled" id="callback-stock">
						</div>
						<div class="form-group m-form__group">
							<label for="email">Callback URL On Update Price</label>
							<input type="text" class="form-control m-input" value="{{ $host . '/v1/callback/price/' . $partner_id . '/' . $client_id }}" name="callback_price" disabled="disabled" id="callback-price">
						</div> -->
					</div>
				</div>
			</form>
		</div>
		<div class="clearfix"></div>
	</div>
    <!--end::Form-->
    
</div>
<!--end::Portlet-->
@endsection

@section('style')
<link href="{{ asset('/css/wms-web-custom.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<script type="text/javascript">
	$("#link-jubelio-button").click(function( event ) {
        event.preventDefault();
        var email = $("#integration-email").val();
        var password = $("#integration-password").val();
		$("#link-jubelio-in-progress").show();
			$("#link-jubelio-success").hide();

			$.ajax( "/integration/ajax/jubelio", {
				data: JSON.stringify({"data": 
					{
						"client_id": {{ $client_id }},
						"email": email,
						"password": password
					}
				}),
				method: "POST",
				contentType: "application/json",
				headers: {
					"X-CSRF-TOKEN": "{{ csrf_token() }}"
				},
				error: function (xhr, ajaxOptions, thrownError) {
					$("#error-message-detail").html(xhr.statusText);
                	$("#jubelio-alert").show();
					$("#link-jubelio-in-progress").hide();
				}
			})
			.done(function(xhr) {
				$("#jubelio-alert").hide();
				$("#link-jubelio-success").html(xhr.data.message)
				$("#link-jubelio-in-progress").hide();
				$("#link-jubelio-success").show();
				$("#jubelio-form-2").show();
            })
			.fail(function(xhr, status, error) {
				$("#error-message-detail").html(xhr.responseJSON.metadata.error.message);
                $("#jubelio-alert").show();
            })
            .always(function(xhr, status, error) {
                $("#link-jubelio-in-progress").hide();
            });
	}); 

	$("#get-all-product").click(function( event ) {
		event.preventDefault();

		$("#get-product-jubelio-in-progress").show();
		$("#get-product-jubelio-success").hide();

		$.ajax( "/integration/ajax/get_all_product", {
				data: JSON.stringify({"data": 
					{
						"client_id": {{ $client_id }},
					}
				}),
				method: "POST",
				contentType: "application/json",
				headers: {
					"X-CSRF-TOKEN": "{{ csrf_token() }}"
				},
				error: function (xhr, ajaxOptions, thrownError) {
					$("#error-message-detail-get-product").html(xhr.statusText);
					$("#jubelio-get-product-alert").show();
					$("#get-product-jubelio-in-progress").hide();
				}
			})
			.done(function(xhr) {
				$("#jubelio-alert").hide();
				$("#get-product-jubelio-in-progress").hide();
				$("#get-product-jubelio-success").show();
            })
			.fail(function(xhr, status, error) {
				$("#error-message-detail-get-product").html(xhr.responseJSON.metadata.error.message);
				$("#jubelio-get-product-alert").show();
            })
            .always(function(xhr, status, error) {
				$("#get-product-jubelio-in-progress").hide();
            });
	}); 
	
	@if ($is_already_login)
		$("#jubelio-form-2").show();
	@endif
</script>
@endsection