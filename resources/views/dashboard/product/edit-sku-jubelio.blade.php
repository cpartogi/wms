@extends('layouts.base',[
    'page' => 'Sinkronisasi SKU'
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
                        Edit SKU Jubelio
                    </h3>
                </div>
            </div>
        </div>

        <!--begin::Form-->
        <form class="m-form" method="post" action="" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="m-portlet__body">

                @include('notif')


                <h6 class="m-portlet__head-text">
                    Client : {{ $product->client_name }}
                </h6>
                <h6 class="m-portlet__head-text">
                    Product Name : {{ $product->product_name }}
                </h6>
                <h6 class="m-portlet__head-text">
                    Variant : {{ $product->sku }}
                </h6>

                <div class="m-form__section m-form__section--first">
                    <input type="hidden" name="detail_id" value="{{ $product->inbound_detail_id }}"/>
                    <div class="form-group m-form__group">
                        <label for="name">SKU Jubelio:</label>
                        <input type="text" class="form-control m-input" placeholder="Please enter jubelio sku" name="jubelio_sku" value="{{ $jubelio_sku }}">
                        <span class="m-form__help">Please enter jubelio sku</span>
                    </div>
                </div>
            </div>
            <div class="m-portlet__foot m-portlet__foot--fit">
                <div class="m-form__actions m-form__actions">
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </div>
        </form>
        <!--end::Form-->
    </div>
    <!--end::Portlet-->
@endsection