@extends('layouts.base',[
    'page' => 'Product'
])

@section('content')
    @if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor')
        <!--begin::Modal-->
        <div class="modal fade" id="m_modal_1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="post" action="{{ url('product/delete') }}">
                    {{ csrf_field() }}
                    <input type="hidden" id="products" name="p" value="{{ $product->id }}"/>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Delete Product</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure to delete this product?</p>
                            <div class="form-group m-form__group">
                                <label class="m-checkbox">
                                    <input type="checkbox" name="forced" value="1"/> Delete includes all related data?
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
    <!--begin::Portlet-->
    <div class="m-portlet">
        <div class="m-portlet__head">
            <div class="m-portlet__head-caption">
                <div class="m-portlet__head-title">
				<span class="m-portlet__head-icon m--hide">
					<i class="la la-gear"></i>
				</span>
                    <h3 class="m-portlet__head-text">
                        Edit Product
                    </h3>
                </div>
            </div>
            <div class="m-portlet__head-tools">
                <ul class="m-portlet__nav">
                    <li class="m-portlet__nav-item">
                        <a href="@if($client_id != null){{ url('client/product/').'/'.$client_id.'/list' }}@else{{ url('product') }}@endif" class="btn btn-secondary m-btn m-btn--custom m-btn--icon m-btn--air">
						<span>
							<i class="la la-angle-left"></i>
							<span>Back to List</span>
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
                                                <a href="{{ url('product/variants').'/'.$product->id }}" class="m-nav__link">
                                                    <i class="m-nav__link-icon la la-tags"></i>
                                                    <span class="m-nav__link-text">View Stocks</span>
                                                </a>
                                            </li>
                                            <li class="m-nav__item">
                                                <a href="{{ url('product/location').'/'.$product->id }}" class="m-nav__link">
                                                    <i class="m-nav__link-icon la la-map-pin"></i>
                                                    <span class="m-nav__link-text">View Locations</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!--begin::Form-->
        <form class="m-form" method="post" action="{{ url('product/update').'/'.$product->id }}" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="m-portlet__body">

                @include('notif')

                <div class="m-form__section">
                    @if(Auth::user()->roles == 'client')
                        <input type="hidden" name="client_id" value="{{ $product->client_id }}"/>
                    @else
                        <div class="form-group m-form__group">
                            <label for="name">Client:</label>
                            <select class="form-control m-input m-input--square m-select2" name="client_id">
                                <option value="">-- Select client --</option>
                                @foreach(\App\Client::all() as $client)
                                    <option value="{{ $client->id }}" @if($client->id == $product->client_id){{'selected'}}@endif>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            <span class="m-form__help">Please select from listed clients</span>
                        </div>
                    @endif
                    <div class="form-group m-form__group">
                        <label for="name">Product Type:</label>
                        <select class="form-control m-input m-input--square m-select2" name="product_type_id" disabled>
                            <option value="">-- Select product type --</option>
                            @foreach(\App\ProductType::where('active',1)->get() as $type)
                                <option value="{{ $type->id }}" @if($type->id == $product->product_type_id){{'selected'}}@endif>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <span class="m-form__help">Please select from listed product type</span>
                    </div>
                    <div class="form-group m-form__group">
                        <label for="name">Product Code:</label>
                        <input type="text" class="form-control m-input" name="product_code" value="{{ $product->product_code }}"/>
                        <span class="m-form__help">Your product code</span>
                    </div>
                    <div class="form-group m-form__group">
                        <label for="name">Product Name:</label>
                        <input type="text" class="form-control m-input" name="name" value="{{ $product->name }}"/>
                        <span class="m-form__help">The name of product</span>
                    </div>
                    <div class="form-group m-form__group">
                        <label for="description">Product Description:</label>
                        <textarea class="form-control m-input" id="exampleTextarea" rows="3" name="description" style="resize:none;">{{ $product->description }}</textarea>
                        <span class="m-form__help">Explanation about the product</span>
                    </div>
                    <div class="form-group m-form__group row">
                        <div class="col-sm-6">
                            <label for="product_price_sizing">Product Sizing:</label>
                            <select class="form-control m-input m-input--square m-select2" name="product_price_sizing" required>
                                <option value="S" @if($product->product_price_sizing == 'S'){{'selected'}}@endif>S</option>
                                <option value="M" @if($product->product_price_sizing == 'M'){{'selected'}}@endif>M</option>
                                <option value="L" @if($product->product_price_sizing == 'L'){{'selected'}}@endif>L</option>
                                <option value="XL" @if($product->product_price_sizing == 'XL'){{'selected'}}@endif>XL</option>
                            </select>
                            <span class="m-form__help">Sizing level of product to categorize pricing</span>
                        </div>
                        <div class="col-sm-6">
                            <label for="price">Product Price:</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon2">Rp</span>
                                </div>
                                <input type="number" class="form-control m-input" name="price" value="{{ $product->price }}" required>
                            </div>
                            <span class="m-form__help">Price of this product variant</span>
                        </div>
                    </div>
                    <div class="form-group m-form__group">
                        <label for="color">Color:</label>
                        <input type="hidden" name="old_color" value="{{ $product->color }}"/>
                        <select class="form-control m-input m-input--square m-select2" name="color" required>
                            <option value="">-- Select product color --</option>
                            @foreach($colors as $key => $color)
                                <optgroup label="{{ $key }}">
                                    @foreach($color as $v)
                                        @php
                                            $cval = $key.(($key != $v)?" ".$v:"");
                                        @endphp
                                        <option value="{{ $cval }}" @if($cval == $product->color){{'selected'}}@endif>{{ $key }} @if($key != $v){{ $v }}@endif</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <span class="m-form__help">Please select color of the product.</span>
                    </div>
                    <div class="form-group m-form__group">
                        <label for="name">Tags:</label><br>
                        <input type="text" class="form-control" data-role="tagsinput" name="tags" value="{{ $product->tags }}"/>
                        <span class="m-form__help">The tag of products, to easier the search</span>
                    </div>
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="weight">Product Weight:</label>
                            <div class="input-group">
                                <input type="text" class="form-control m-input" name="weight" value="{{ $product->weight }}"/>
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon2">Kg</span>
                                </div>
                            </div>
                            <span class="m-form__help">The name of product</span>
                        </div>
                        @php
                            $dimension = null;
                            if(isset($product->dimension)){
                                $dimension = json_decode($product->dimension);
                            }
                        @endphp
                        <div class="form-group col-sm-6">
                            <label for="dimension">Product Dimension ( W x H x D ):</label>
                            <div class="row">
                                <input type="number" min="0" class="form-control col-sm-4" name="dimension-w" placeholder="Width" value="@if(isset($dimension->w)){{ $dimension->w }}@endif"/>
                                <input type="number" min="0" class="form-control col-sm-4" name="dimension-h" placeholder="Height" value="@if(isset($dimension->h)){{ $dimension->h }}@endif"/>
                                <input type="number" min="0" class="form-control col-sm-4" name="dimension-d" placeholder="Depth" value="@if(isset($dimension->d)){{ $dimension->d }}@endif"/>
                            </div>
                            <span class="m-form__help">The name of product</span>
                        </div>
                    </div>
                    <div class="form-group m-form__group">
                        <label for="product_img">Product Images:</label><br>
                        <div id="img-container">
                            @if(count($images) > 0)
                                @foreach($images as $key => $image)
                                    <div class="img-list">
                                        <img src="https://s3-ap-southeast-1.amazonaws.com/static-pakde/{{ str_replace(' ','+',$image->s3url) }}" width="100"/>
                                        <input type="file" name="product_img[{{ $key }}]" class="product-img" accept="image/*">
                                    </div>
                                @endforeach
                            @else
                                <div class="img-list">
                                    <img src="http://13.229.209.36:3006/assets/client-image.png" width="100"/>
                                    <input type="file" name="product_img[0]" accept="image/*">
                                </div>
                            @endif
                        </div>
                        <br><br>
                        <button type="button" class="btn btn-primary" id="add-btn"><i class="fa fa-add"></i> Add Image</button>
                    </div>
                </div>
            </div>
            @if(Auth::user()->roles != 'investor')
                <div class="m-portlet__foot m-portlet__foot--fit">
                    <div class="m-form__actions m-form__actions">
                        <button type="submit" class="btn btn-success">Save</button>
                        @if(Auth::user()->roles != 'crew')
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#m_modal_1">Delete</button>@endif
                    </div>
                </div>
            @endif
        </form>
        <!--end::Form-->
    </div>
    <!--end::Portlet-->
@endsection

@section('style')
    <link href="{{ asset('css/bootstrap-tagsinput.css') }}" rel="stylesheet" type="text/css"/>
    <style>
        .bootstrap-tagsinput {
            width: 100%;
        }

        .bootstrap-tagsinput .tag {
            background-color: #36a3f7;
            border-radius: 2px;
            padding: 2px 4px;
            font-weight: bold;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/bootstrap-tagsinput.min.js') }}" type="text/javascript"></script>
    <script>
        $(function () {
            $('.m-select2').select2();
            $('#add-btn').click(function () {
                var count = $('html').find('.img-list').length;
                $('#img-container').append('<div class="img-list"><img src="http://13.229.209.36:3006/assets/client-image.png" width="100"/><input type="file" name="product_img[' + count + ']" class="product-img"></div>');
            });
        });
    </script>
@endsection