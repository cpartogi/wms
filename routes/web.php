<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('login');
});

Auth::routes();

Route::group(['middleware' => 'custom.auth:admin'], function () {

	Route::get('/', 'HomeController@index');
	Route::post('/list', 'HomeController@get_list')->name('stock-list');
	Route::post('/tops', 'HomeController@get_tops')->name('top-list');
	Route::post('/ajax_orders', 'ReportController@get_orders')->name('order-report-list');
	Route::post('/ajax_orders_status', 'ReportController@getOrdersStatus')->name('order-status-report');
	Route::post('/ajax_orders_pending', 'ReportController@getPendingOrders')->name('order-pending-report');
	Route::get('/logout', 'HomeController@logout');

	Route::group(['prefix' => 'profile'], function() {
		Route::get('/', 'UserController@profile');
		Route::post('/update', 'UserController@updateProfile');
	});

	Route::group(['prefix' => 'user'], function() {
		Route::get('/', 'UserController@index');
		Route::get('/edit/{id}', 'UserController@edit');
		Route::post('/delete/{id}', 'UserController@delete');
		Route::post('/update/{id}', 'UserController@update');
		Route::get('/add', 'UserController@add');
		Route::post('/create', 'UserController@create');
	});

	Route::group(['prefix' => 'package'], function() {
		Route::get('/', 'ToolController@indexPackage');
		Route::get('/list', 'ToolController@get_list')->name('package-list');
		Route::get('/edit/{id}', 'ToolController@editPackage');
		Route::post('/delete/{id}', 'ToolController@deletePackage');
		Route::post('/update/{id}', 'ToolController@updatePackage');
		Route::get('/add', 'ToolController@addPackage');
		Route::post('/create', 'ToolController@createPackage');
	});

	Route::group(['prefix' => 'client'], function() {
		Route::get('/', 'ClientController@index');
		Route::get('/list', 'ClientController@get_list')->name('client-list');
		Route::get('/edit/{id}', 'ClientController@edit');
		Route::post('/delete/{id}', 'ClientController@delete');
		Route::post('/update/{id}', 'ClientController@update');
		Route::get('/add', 'ClientController@add');
		Route::post('/create', 'ClientController@create');
		Route::get('/product/{client_id}/list', 'ClientController@product');
	});

	Route::group(['prefix' => 'warehouse'], function() {
		Route::get('/', 'WarehouseController@index');
		Route::get('/list', 'WarehouseController@get_list')->name('warehouse-list');
		Route::get('/add', 'WarehouseController@add');
		Route::post('/create', 'WarehouseController@create');
		Route::get('/edit/{id}', 'WarehouseController@edit');
		Route::post('/update/{id}', 'WarehouseController@update');
		Route::post('/delete/{id}', 'WarehouseController@delete');
		Route::post('/bulk', 'WarehouseController@bulkUpload');
	});

	Route::group(['prefix' => 'rack'], function() {
		Route::get('/{id}', 'RackController@index');
		Route::get('/list/{id}', 'RackController@get_list');
		Route::get('/add/{id}', 'RackController@add');
		Route::post('/create', 'RackController@create');
		Route::get('/edit/{id}', 'RackController@edit');
		Route::post('/update/{id}', 'RackController@update');
		Route::post('/delete/{id}', 'RackController@delete');
	});

	Route::group(['prefix' => 'shelf'], function() {
		Route::get('/{id}', 'ShelfController@index');
		Route::get('/list/{id}', 'ShelfController@get_list');
		Route::get('/add/{id}', 'ShelfController@add');
		Route::post('/create', 'ShelfController@create');
		Route::get('/edit/{id}', 'ShelfController@edit');
		Route::post('/update/{id}', 'ShelfController@update');
		Route::post('/delete/{id}', 'ShelfController@delete');
    	Route::get('/barcode/bulk/{id}', 'ShelfController@bulkPrint');
    	Route::get('/barcode/single/{id}', 'ShelfController@singlePrint');
	});

	Route::group(['prefix' => 'productType'], function() {
		Route::get('/', 'ProductTypeController@index');
		Route::get('/edit/{id}', 'ProductTypeController@edit');
		Route::post('/delete/{id}', 'ProductTypeController@delete');
		Route::post('/update/{id}', 'ProductTypeController@update');
		Route::get('/add', 'ProductTypeController@add');
		Route::post('/create', 'ProductTypeController@create');

		Route::get('/size/{type_id}', 'ProductTypeController@indexSize');
		Route::get('/size/{type_id}/edit/{id}', 'ProductTypeController@editSize');
		Route::post('/size/{type_id}/delete/{id}', 'ProductTypeController@deleteSize');
		Route::post('/size/{type_id}/update/{id}', 'ProductTypeController@updateSize');
		Route::get('/size/{type_id}/add', 'ProductTypeController@addSize');
		Route::post('/size/{type_id}/create', 'ProductTypeController@createSize');
	});

	Route::group(['prefix' => 'product'], function() {
		Route::get('/', 'ProductController@index');
		Route::post('/list', 'ProductController@get_list')->name('product-list');
		Route::get('/edit/{id}', 'ProductController@edit');
		Route::post('/delete', 'ProductController@delete');
		Route::post('/update/{id}', 'ProductController@update');
		Route::get('/add', 'ProductController@add');
		Route::post('/create', 'ProductController@create');
		Route::get('/location/{id}', 'ProductController@indexLocation');
		Route::post('/bulk', 'ProductController@bulkUpload');
		Route::get('/variants/{id}', 'ProductController@indexVariant')->name('product.variants');
		Route::get('/variants/{id}/add', 'ProductController@addVariant');
        Route::post('/variants/{id}/create', 'ProductController@createVariant');
        Route::get('/variants/{id}/edit-sku-jubelio/{detail_id}', 'ProductController@editJubelioSKU')->name('product.variants.edit-sku-jubelio');
        Route::post('/variants/{id}/edit-sku-jubelio/{detail_id}', 'ProductController@processEditJubelioSKU');
        Route::get('/variants/{id}/edit/{detail_id}', 'ProductController@editVariant');
		Route::post('/variants/{id}/update/{detail_id}', 'ProductController@updateVariant');
		Route::get('/variants/{id}/location/{detail_id}', 'ProductController@indexLocation');
		Route::get('/download', 'ProductController@downloadExcel');
		Route::get('/adjustment', 'ProductController@adjustmentList');
		Route::get('/adjustment/{id}', 'ProductController@indexAdjustment');
		Route::post('/adjust', 'ProductController@adjust_list')->name('adjustment-list');
	});

	Route::group(['prefix' => 'order'], function() {
		Route::get('/', 'OrderController@index');
		Route::post('/list', 'OrderController@get_list')->name('order-list');
		Route::get('/ready', 'OrderController@ready');
		Route::post('/ajax-ready', 'OrderController@get_ready_list')->name('ready-list');
		Route::get('/canceled', 'OrderController@canceled');
		Route::post('/ajax-cancel', 'OrderController@get_cancel_list')->name('cancel-list');
		Route::get('/edit/{id}', 'OrderController@edit');
		Route::post('/delete', 'OrderController@delete');
		Route::post('/update/{id}', 'OrderController@update');
		Route::get('/add', 'OrderController@add');
		Route::post('/create', 'OrderController@create');
		Route::post('/ajax/product','OrderController@ajaxProduct');
		Route::post('/ajax/warehouse','OrderController@ajaxCheckWarehouse');
        Route::get('/location/{id}', 'OrderController@location');
        Route::get('/tpl/history/{id}', 'OrderController@tplHistory');
        Route::get('/barcode/{id}', 'OrderController@printLabel');
		Route::post('/barcode', 'OrderController@printBulkLabel');
		Route::post('/bulk', 'OrderController@bulkUpload');
		Route::get('/report/{id}', 'OrderController@printReport');
		Route::get('/download', 'OrderController@downloadExcel');
		Route::get('/airwaybill/edit', 'OrderController@editAirwaybill');
		Route::post('/airwaybill/update', 'OrderController@updateAirwaybill');
		Route::post('/airwaybill/bulk', 'OrderController@bulkUpdateAwb');
		Route::get('/shipping/label', 'OrderController@shippingLabel');
		Route::get('/shipping/label/list', 'OrderController@shippingLabelList');
		Route::get('/shipping/label/regenerate/{client_id}/{batch_id}', 'OrderController@regenerateShippinglabel');
		Route::get('/shippinglabel/download/{batch_id}/{client_id}', 'OrderController@downloadShippingLabel');
		Route::get('/issue/list/index', 'OrderController@orderIssueListIndex')->name('order-issue-detail-index');
		Route::get('/issue/list', 'OrderController@getListOrderIssue')->name('order-issue-list');
		Route::post('/revalidate', 'OrderController@revalidateOrders');
		Route::get('/pending/list', 'OrderController@pendingOrder');
		Route::post('/pending', 'OrderController@pendingOrderList')->name('pending-order-list');	
		Route::post('/pending/submit', 'OrderController@pendingOrderSubmit');	
		Route::get('/issue/detail/{request_id}/{created_at}', 'OrderController@orderIssueDetail');
		Route::get('/detail/revalidate/{request_id}', 'OrderController@revalidateOrder');
		Route::post('/download/bulk', 'OrderController@downloadBatchShippingLabel');
	});

	Route::group(['prefix' => 'outbound'], function() {
		Route::get('/', 'OutboundController@index');
		Route::post('/ajax-outbound', 'OrderController@get_outbound_list')->name('outbound-list');
		Route::get('/shipment', 'OutboundController@shipment');
		Route::post('/ajax-shipment', 'OrderController@get_shipment_list')->name('shipment-list');
		Route::get('/done', 'OutboundController@done');
		Route::post('/ajax-done', 'OrderController@get_done_list')->name('done-list');
		Route::get('/print/{id}', 'OutboundController@singlePrint');
		Route::post('/report', 'OutboundController@sendReport');
		Route::post('/check/packing', 'OutboundController@courierCheckPacking');
		Route::post('/check/shipped', 'OutboundController@courierCheckShipped');
	});

	Route::group(['prefix' => 'inbound'], function() {
		Route::get('/', 'InboundController@index');
    	Route::get('/copy', 'InboundController@copyNotExist');
		Route::post('/list', 'InboundController@get_list')->name('inbound-list');
		Route::get('/add', 'InboundController@add');
		Route::post('/create', 'InboundController@create');
		Route::post('/delete', 'InboundController@delete');
		Route::get('/edit/{id}', 'InboundController@edit');
		Route::get('/location/{id}', 'InboundController@location');
		Route::post('/update/{id}', 'InboundController@update');
		Route::get('/variants/{id}', 'InboundController@variants');
		Route::get('/get_product/{id}', 'InboundController@get_product');
		Route::get('/get_variance/{id}', 'InboundController@get_variance');
		Route::post('/bulk', 'InboundController@bulkUpload');
    	Route::get('/preview', 'InboundController@previewPdf');
    	Route::get('/barcode/bulk/{id}', 'InboundController@bulkPrint');
    	Route::get('/barcode/single/{id}', 'InboundController@singlePrint');
    	Route::get('/download', 'InboundController@downloadExcel');
	});

	Route::group(['prefix' => 'report'], function() {
		Route::get('/', 'ReportController@index');
		Route::get('/trace', 'ReportController@trace');
		Route::get('/monitor', 'ReportController@getPerformanceMonitor')->name('report-performance');
		Route::post('/list', 'ReportController@get_list')->name('report-list');
		Route::get('/stocks', 'ReportController@downloadStockMovement');
		Route::get('/pending_order/{date}', 'ReportController@pendingOrderPage');
		Route::get('/outbound', 'ReportController@outboundAnalytic');
	});

	Route::group(['prefix' => 'integration'], function() {
		Route::get('/', 'IntegrationController@index');
		Route::get('/{partner_id}', 'IntegrationController@partner');
		Route::post('/ajax/get_all_product', 'IntegrationController@get_all_product');
		Route::post('/ajax/jubelio', 'IntegrationController@jubelio_integration');
	});

	Route::group(['prefix' => 'session'], function() {
		Route::get('/get', function() {
			Session::flash('success', Session::get('success'));
			Session::flash('error', Session::get('error'));

			return Session::get('order_progress');
		})->name('order-progress');
	});
});

Route::group(['prefix' => 'api', 'middleware' => 'custom.auth:api-guest'], function () {

	/*
	|--------------------------------------------------------------------------
	| Web Routes API (guest) -> under '/api'
	|--------------------------------------------------------------------------
	|   - Dashboard (/)
	|
	*/

	Route::post('/product/reset', 'ProductController@reset');
	Route::get('/order/reset', 'OrderController@reset');

    Route::get('/', function () {
        return response()->json(['code' => '00', 'message' => 'Welcome API']);
    });
    
    Route::post('/login', 'ApiController@login');

    // External access API
    Route::get('/token', 'ExternalController@get_token');
    Route::get('/auth', 'ExternalController@get_login');
    Route::get('/deauth', 'ExternalController@detach');

    Route::get('/tpl/synchronize', 'OrderController@tplSynchronize');
    Route::get('/jbl/synchronize-stock', 'ProductController@synchronizeStockJubelio');
    
    Route::group(['prefix' => '/callback'], function () {
        Route::group(['prefix' => '/jubelio'], function () {
            Route::post('/submit_product', 'Callback\JubelioController@postSubmitProduct');
            Route::post('/submit_order', 'Callback\JubelioController@postSubmitOrder');
        });
        Route::group(['prefix' => '/jne'], function () {
            Route::post('/job-booked', 'Callback\JNEController@postJobBooked');
        });
	});

	Route::get('/healthz', function() {
		return 1;
	});
	
	Route::get('/_ah/health', function() {
		return DB::table('dual')
			->select(DB::raw(1))
			->get();
	});

	// Route::get('/shark/{number}', function(\Illuminate\Http\Request $request, $number) {
	// 	return App::call('\App\Http\Controllers\SharkController@shark'.$number);
	// });
	
	// Route::post('shark/post/{number}', function($number) {
	// 	return App::call('\App\Http\Controllers\SharkController@shark'.$number);
	// });
});

Route::group(['prefix' => 'api', 'middleware' => 'custom.auth:api'], function () {

	/*
	|--------------------------------------------------------------------------
	| Web Routes API -> under '/api'
	|--------------------------------------------------------------------------
	| 	- logout /logout
	|
	*/

    Route::post('/logout', 'ApiController@logout');

    Route::get('/warehouse', 'ApiController@getWarehouse');
    Route::get('/rack/{id}', 'ApiController@getRackByWarehouseId');
    Route::get('/shelf/{id}', 'ApiController@getShelfInsideByRackId');
    Route::get('/scan/product/{code}', 'ApiController@getScannedProduct');
    Route::get('/warehouse/{id}/shelf', 'ApiController@getShelfByWarehouseId');
    Route::post('/shelf/items', 'ApiController@getItemsInsideByShelfCode');
    Route::post('/shelf/opname', 'ApiController@updateShelfDateOpname');
    Route::post('/shelf/move', 'ApiController@moveProductToShelf');

    // Inbound & Outbound functions
    Route::get('/inbound/list', 'ApiController@getInboundList');
    Route::get('/outbound/list', 'ApiController@getOutboundList');
    Route::get('/product/edit/{id}' ,'ApiController@getProductDetail');
    Route::post('/inbound/insert' ,'ApiController@updateInboundLocation');
    Route::get('/inbound/batch', 'ApiController@getBatchInboundList');
    Route::get('/inbound/batch/{id}', 'ApiController@getBatchLocations');
    Route::post('/inbound/done', 'ApiController@inboundDone');
	Route::post('/inbound/reject', 'ApiController@inboundReject');
	Route::post('/inbound/bill', 'ApiController@billInbound');

    Route::get('/order/ready', 'ApiController@getOutboundReady');
    Route::get('/order/edit/{id}', 'ApiController@getOutboundLocations');
    Route::post('/order/code', 'ApiController@getOutboundLocationsByQr');
    Route::get('/order/pending', 'ApiController@getOutboundPending');
	Route::get('/order/pending/{warehouse_id}', 'ApiController@getOutboundPendingByWarehouseId');
	Route::get('/order/list/{batch_id}/{warehouse_id}', 'ApiController@getOrderListByBatchID');
    Route::post('/order/pending/approve/{warehouse_id}', 'ApiController@approveMultiOutbound');
    Route::post('/order/pending/approve/single', 'ApiController@approveSingleOutboundByCode');
	Route::post('/order/pending/cancel', 'ApiController@cancelMultiOutboundByIds');
	Route::post('/order/scan', 'ApiController@scanOutbound');
    Route::post('/outbound/ready', 'ApiController@setOutboundReady');
    Route::post('/outbound/scanning', 'ApiController@fetchScanOutboundPreviewByCode'); //this
   	Route::get('/outbound/missed', 'ApiController@getMissedOutbound');
   	Route::post('/order/reading', 'ApiController@fetchOrderReadingByCode');


    Route::get('/outbound/pack', 'ApiController@getReadyPack');
    Route::get('/outbound/edit/{id}', 'ApiController@getPackLocations');
    Route::post('/outbound/code', 'ApiController@getPackLocationsByQr'); //this
    Route::post('/outbound/check' ,'ApiController@updatePackingLocation');
    Route::post('/outbound/confirm', 'ApiController@setPackingReady');
    Route::post('/outbound/cancel', 'ApiController@cancelOutboundByCode');
    Route::post('/outbound/clear', 'ApiController@clearOutboundOrderByCode');

    Route::get('/adjustment/list', 'ApiController@getAdjustmentList');
    Route::post('/adjustment/add', 'ApiController@addAdjustment');
    Route::post('/adjustment/update', 'ApiController@updateAdjustmentStock');
	Route::post('/adjustment/approve', 'ApiController@approveAdjustmentStockIds');
    Route::post('/items/summary', 'ApiController@getItemsSummary');
    Route::post('/shelf/moving', 'ApiController@movingProductToShelf');
    Route::post('/packing/rewind', 'ApiController@rewindPackingById');
    Route::post('/packing/finishing', 'ApiController@finishingPackingShippingByCode');

    Route::get('/notification', 'ApiController@getNotification');
    Route::post('/notification/read', 'ApiController@readNotification');
    Route::post('/warehouse/choose', 'ApiController@updateWarehouseByEmail');
    Route::get('/orders/list', 'ApiController@getOrdersByStatusAndWarehouseId');
    Route::get('/order/check', 'ApiController@getOrderChecking');
    Route::get('/product/check', 'ApiController@getProductChecking');

    Route::post('/users/cfu', 'ApiController@fetchUsersCFU');
    Route::post('/clients', 'ApiController@fetchClients');
    Route::post('/users/cfu/update', 'ApiController@updateUsersCFUIdByUserId');

    //paging
	Route::get('/order/paging/{start}/{limit}', 'ApiController@getOutboundPaging');
	Route::get('/inbound/paging/{start}/{limit}', 'ApiController@getInboundPaging');
	Route::get('/packing/paging/{start}/{limit}', 'ApiController@getPackingPaging');
	Route::get('/shipping/paging/{start}/{limit}', 'ApiController@getShippingPaging');
	Route::get('/warehouse/{id}/shelf/paging/{start}/{limit}', 'ApiController@getShelfPagingByWarehouseId');
});

Route::group(['prefix' => 'oauth', 'middleware' => 'custom.auth:oauth'], function () {

	Route::get('/products', 'ExternalController@getProductList');
	Route::get('/products/{id}', 'ExternalController@getProductStocks');
	Route::get('/stocks', 'ExternalController@getTotalStocks');

	Route::post('/createOrder', 'ExternalController@createOrder');

});

Route::group(['prefix' => 'api', 'middleware' => 'custom.auth:internal'], function () {

	Route::post('/add-client', 'InternalController@create_client');
	Route::get('/client/{client_id}', 'InternalController@get_client');

});