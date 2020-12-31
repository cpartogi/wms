<?php

namespace App\Http\Controllers;

use App\Adjustment;
use App\ApiToken;
use App\Http\Services\Jubelio\Product;
use App\Http\Services\ThirdPartyLogistic\Jne;
use App\InboundBatch;
use App\InboundLocation;
use App\Order;
use App\OrderHistory;
use App\OrderDetail;
use App\Shelf;
use Auth;
use Cache;
use Config;
use DB;
use Exception;
use Log;
use Illuminate\Http\Request;
use Mail;
use Session;
use GuzzleHttp\Client;

use Carbon\Carbon;

class ApiController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\Response
     */

    public function login(Request $request)
    {
        $responseMessage = 'Login Failed';
        $responseData    = '';
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());

        if (isset($input->version) && $input->version >= env('ANDROID_VERSION')) {
            if (Auth::attempt(array('email' => $input->email, 'password' => $input->password))) {
                $responseMessage = 'Login Success';
                $responseData    = \App\User::where('email', $input->email)->first();
                
                $token = str_random(60);
                
                $apiTokenObj = new ApiToken;
                
                $apiTokenObj->token    = $token;
                $apiTokenObj->user_id  = $responseData->id;
                $apiTokenObj->platform = 'D';
                $apiTokenObj->status   = 'A';
                $apiTokenObj->save();

                $warehouse_name = $responseData->warehouse_id != null ? DB::table('warehouse')
                    ->find($responseData->warehouse_id)->name : null;

                $responseData->setAttribute('warehouse_name', $warehouse_name);
                
                if ($apiTokenObj->status == 'A' && $apiTokenObj->id > 0) {
                    $responseData->setAttribute('token', $token);
                    $responseCode = '00';
                } else {
                    $responseData    = null;
                    $responseMessage = "Failed Generate Token";
                }
            }
        } else {
            $responseMessage = "Mohon perbaharui aplikasi Pakdé Anda.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function logout(Request $request)
    {
        $responseMessage = 'Logout Failed';
        $responseData    = '';
        $responseCode    = '01';
        
        if ($request->tokenObj != null) {
            DB::table('api_token')
                ->where('token', $request->tokenObj->token)
                ->update(['status' => 'D', 'updated_at' => Carbon::now()]);
            
            $responseCode    = '00';
            $responseMessage = 'Logout Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getWarehouse(Request $request)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select('select * from warehouse where is_active = 1');
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getRackByWarehouseId(Request $request, $id)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select('select * from rack where warehouse_id = ' . $id);
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getShelfInsideByRackId(Request $request, $id)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select('select id,code,(select count(code) FROM inbound_detail_location where date_outbounded is null and shelf_id is not null and shelf_id =shlf.id) as shelf_inside_count from shelf as shlf where rack_id = ' . $id);
        
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getScannedProduct(Request $request, $code)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select("SELECT code,name,color,sku,(select name from product where id = inbound_detail.product_id) as product_name,(select name from product_type_size where id = inbound_detail.product_id) as product_type_size FROM inbound_detail where code = '" . $code . "'");
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getInboundList(Request $request)
    {
        $responseMessage = 'Get Inbound List Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('inbound_detail_location')
            ->select('inbound_detail_location.id as inbound_location_id', 'product.name as product_name', 'inbound_detail_location.date_stored', 'shelf.code as shelf_code')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->whereNull('inbound_detail_location.date_outbounded')
            ->whereNotNull('inbound_detail_location.shelf_id')
            ->whereNotNull('inbound_detail_location.date_stored')
            ->orderBy('inbound_detail_location.date_stored', 'desc')
            ->take(100)
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Inbound List Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOutboundList(Request $request)
    {
        $responseMessage = 'Get Outbound List Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('inbound_detail_location')
            ->select('inbound_detail_location.id as inbound_location_id', 'product.name as product_name', 'inbound_detail_location.date_outbounded', 'shelf.code as shelf_code')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->whereNotNull('inbound_detail_location.date_outbounded')
            ->orderBy('inbound_detail_location.date_outbounded', 'desc')
            ->take(100)
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Outbound List Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function updateInboundLocation(Request $request)
    {
        $responseMessage = 'Update inbound location failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());

        if (isset($input)) {
            if ($input->shelf != null && count($input->products) > 0) {
                $shelf = DB::table('shelf')
                    ->select('shelf.id as shelf_id')
                    ->join('rack', 'rack.id', 'shelf.rack_id')
                    ->join('warehouse', 'warehouse.id', 'rack.warehouse_id')
                    ->where('shelf.code', $input->shelf)
                    ->where('warehouse.id', '=', $input->warehouse);

                if ($shelf->count() > 0) {
                    $shelf_id = $shelf->first()->shelf_id;
                    foreach ($input->products as $product_code) {
                        $llocation = InboundLocation::where('code', $product_code);
                        if ($llocation->count() > 0) {
                            $llocdata              = $llocation->first();
                            $llocdata->shelf_id    = $shelf_id;
                            $llocdata->date_stored = date('Y-m-d H:i:s');
                            $llocdata->officer_id  = $request->user->id;
                            $llocdata->save();
                        } else {
                            $responseMessage = 'Inbound with QR Code ' . $product_code . ' is not registered yet.';
                            
                            return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
                        }
                    }
                    
                    $responseCode    = '00';
                    $responseMessage = "Update inbound location success!";
                } else {
                    $responseMessage = "Uh oh, the shelf barcode is not registered yet. Please contact warehouse manager.";
                }
            } else {
                $responseMessage = "Please make sure your targeted shelf and at least one product is scanned.";
            }
        } else {
            $responseMessage = "Shelf parameter and product list is required.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getProductDetail(Request $request, $id)
    {
        $responseMessage = 'Get Product Detail Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('pinbound_detail_location')
            ->select('client.name as brand_name', 'product.name as product_name', 'product_type.name as product_type_name', 'inbound_detail.color', 'inbound_detail.price', 'inbound_detail.sku', 'product_type_size.name as product_type_size', 'inbound_detail_location.date_stored', 'shelf.name as shelf_name', 'rack.name as rack_name', 'warehouse.name as warehouse_name')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->join('rack', 'rack.id', '=', 'shelf.rack_id')
            ->join('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
            ->join('client', 'client.id', '=', 'product.client_id')
            ->where('inbound_detail_location', '=', $id)
            ->first();
        
        if ($datas != null) {
            $responseMessage = 'Get Product Detail Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getBatchInboundList(Request $request)
    {
        $responseMessage = 'Get Inbound Batch List Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('inbound_batch')
            ->join('client', 'client.id', '=', 'inbound_batch.client_id')
            ->select('inbound_batch.id as batch_id', 'client.name as client_name', 'inbound_batch.arrival_date', 'inbound_batch.status', 'inbound_batch.is_done')
            ->where('inbound_batch.is_done', '=', 0)
            ->orderBy('inbound_batch.id', 'desc')
            ->get();
        
        if (count($datas)) {
            foreach ($datas as $key => $val) {
                $datas[$key]->pretty_batch_id = '#' . str_pad($datas[$key]->batch_id, 5, '0', STR_PAD_LEFT);
                $total                        = DB::table('inbound_detail_location')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
                    ->where('inbound_batch.id', '=', $datas[$key]->batch_id)
                    ->count();
                $completed                    = DB::table('inbound_detail_location')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
                    ->where('inbound_batch.id', '=', $datas[$key]->batch_id)
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->count();
                $datas[$key]->count           = $completed . " / " . $total;
            }
            
            $responseMessage = 'Get Inbound Batch List Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getBatchLocations(Request $request, $id)
    {
        $responseMessage = 'Get Inbound Location List Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('inbound_detail_location')
            ->select('inbound_detail_location.id', 'product.name as product_name', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'product_type_size.name as size_name', 'inbound_detail.color', 'inbound_detail_location.date_rejected')
            ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
            ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('product_type', 'product_type.id', '=', 'inbound.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->where('inbound_batch.id', '=', $id)
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Inbound Location List Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function inboundDone(Request $request)
    {
        $responseMessage = 'Set Inbound Done failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->batch_id != null) {
            $report_no = "PKD/" . date('Ymd') . "/" . str_pad($input->batch_id, 5, '0', STR_PAD_LEFT);
            $batch     = DB::table('inbound_batch')
                ->join('client', 'client.id', '=', 'inbound_batch.client_id')
                ->select('inbound_batch.*', 'client.name as client_name', 'client.address as client_address', 'client.email as client_email')
                ->where('inbound_batch.id', '=', $input->batch_id)
                ->first();
            
            if ($batch->is_done == 0) {
                $variants = array();
                
                $products = DB::table('inbound_detail_location')
                    ->select('product.name as product_name', 'inbound.product_id', 'inbound_detail.id as inbound_detail_id', 'inbound_detail.product_type_size_id', 'product_type_size.name as size_name', 'inbound_detail.stated_qty', 'inbound_detail_location.shelf_id', 'inbound_detail_location.date_rejected')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->where('inbound.batch_id', '=', $input->batch_id)
                    ->get();
                
                foreach ($products as $key => $val) {
                    if (!array_key_exists($val->inbound_detail_id, $variants)) {
                        $variants[$val->inbound_detail_id] = array(
                            "product_name" => $val->product_name,
                            "size_name"    => $val->size_name,
                            "stated"       => $val->stated_qty,
                            "actual"       => 0,
                            "reject"       => 0,
                        );
                    }
                    
                    if ($val->shelf_id != null) {
                        $variants[$val->inbound_detail_id]["actual"] += 1;
                    }
                    
                    if ($val->date_rejected != null) {
                        $variants[$val->inbound_detail_id]["reject"] += 1;
                    }
                }
                
                $responseData = $variants;
                
                $mpdf = new \Mpdf\Mpdf([
                    'mode'              => 'utf-8',
                    'format'            => [215, 280],
                    'orientation'       => 'P',
                    'setAutoTopMargin'  => 'stretch',
                    'autoMarginPadding' => 5
                ]);
                
                $mpdf->SetHTMLFooter(view('dashboard.pdf.footer')->render());
                
                $mpdf->WriteHTML(view('dashboard.pdf.inbound-report', [
                    'variants'  => $responseData,
                    'batch'     => $batch,
                    'report_no' => $report_no
                ])->render());
                
                $pdf_path = public_path() . "/format/";
                
                $mpdf->Output($pdf_path . 'inbound-report.pdf', 'F');
                
                $cc   = array('kezia@clientname.co.id', 'tanaya@clientname.co.id', 'nicko.batubara@clientname.co.id', 'prawedhi.s@clientname.co.id', 'vicky.siregar@clientname.co.id', 'robi@clientname.co.id');
                $head = DB::table('shelf')
                    ->join('rack', 'rack.id', '=', 'shelf.rack_id')
                    ->join('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
                    ->join('users', 'users.id', '=', 'warehouse.head_id')
                    ->select('users.email')
                    ->where('shelf.id', '=', $products[0]->shelf_id)
                    ->first();
                
                if ($head != null) {
                    array_push($cc, $head->email);
                }
                
                Mail::send('emails.inbound-report', ['client' => $batch->client_name, 'arrival' => date('d M Y', strtotime($batch->arrival_date))], function ($message) use ($pdf_path, $batch, $cc) {
                    $message->to($batch->client_email);
                    $message->cc($cc);
                    $message->subject('Inbound Report - ' . $batch->client_name . ' - ' . date('d M Y'));
                    $message->from('inbound@clientname.co.id');
                    $message->attach(
                        $pdf_path . 'inbound-report.pdf',
                        array(
                            'as'   => 'inbound-report.pdf',
                            'mime' => 'application/pdf')
                    );
                });
                
                unlink($pdf_path . 'inbound-report.pdf');
                
                $inbound_batch          = InboundBatch::find($input->batch_id);
                $inbound_batch->is_done = 1;
                $inbound_batch->save();
                
                $responseCode    = '00';
                $responseMessage = "Set inbound done success!";
            } else {
                $responseCode    = '00';
                $responseMessage = "Inbound report already sent, please check it.";
            }
        } else {
            $responseMessage = "Parameter batch id is needed.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function inboundReject(Request $request)
    {
        $responseMessage = 'Update Inbound Reject Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->inbound_location_id != null) {
            $ilocation                = InboundLocation::find($input->inbound_location_id);
            $ilocation->date_rejected = date('Y-m-d H:i:s');
            $ilocation->save();
            
            $responseCode    = '00';
            $responseMessage = "Set Inbound Location Reject Success!";
        } else {
            $responseMessage = "Parameter inbound location id is needed.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOutboundPending(Request $request)
    {
        $responseMessage = 'Get Outbound Pending Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = Order::where('status', 'PENDING')->whereNull('picked_status')->limit(100)->orderBy('created_at', 'DESC')->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Outbound Pending Success';
            $responseData    = $datas;
            $responseCode    = '00';
        } else {
            $responseMessage = 'No Outbound Pending';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOutboundPendingByWarehouseId(Request $request, $warehouse_id)
    {
        $responseMessage = 'Get Outbound Pending Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $data = DB::select("SELECT order_batch.id, order_batch.updated_at FROM order_batch INNER JOIN orders ON order_batch.id = orders.batch_id WHERE orders.status = 'PENDING' and orders.picked_status IS NULL AND (orders.warehouse_id = '" . $warehouse_id . "' OR orders.warehouse_id IS NULL) GROUP BY order_batch.id ORDER BY orders.created_at DESC");
        $response = array();

        foreach($data as $key=>$orderBatch) {
            $orderBatch->batch_number = '#' . str_pad($orderBatch->id, 5, '0', STR_PAD_LEFT);
            array_push($response, $orderBatch);
        }

        $unBatchedOrder = DB::select("SELECT orders.id FROM orders WHERE status = 'PENDING' AND picked_status IS NULL AND (warehouse_id = '" . $warehouse_id . "' OR warehouse_id IS NULL) AND batch_id IS NULL LIMIT 10");
        if(count($unBatchedOrder) > 0) {
            $orderBatch = new \stdClass;
            $orderBatch->id = null;
            $orderBatch->updated_at = date('Y-m-d H:i:s');
            $orderBatch->batch_number = '#NotBatched';
            array_push($response, $orderBatch);
        }

        if (count($response)) {
            $responseMessage = 'Get Outbound Pending Success';
            $responseData    = $response;
            $responseCode    = '00';
        } else {
            $responseMessage = 'No Outbound Pending';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getOrderListByBatchID(Request $request, $batch_id, $warehouse_id) {
        $statusCode = "01";
        $responseData = null;
        $responseMessage = "Failed To Get List Order";

        $order = DB::table('orders')
            ->where('batch_id', $batch_id)
            ->select('order_number', 'courier', 'updated_at')
            ->get();

        //handling unbatch order    
        if (strcmp($batch_id, 'null') == 0) {
            //limit maximum 10 order for performance reason
            $order = DB::select("SELECT * FROM orders WHERE status = 'PENDING' AND picked_status IS NULL AND (warehouse_id = '" . $warehouse_id . "' OR warehouse_id IS NULL) AND batch_id IS NULL LIMIT 10");
        }

        if (count($order) > 0) {
            $statusCode = "00";
            $responseData = $order;
            $responseMessage = "Get List Order Success";
        } else {
            $responseMessage = "There Is No Order";
        }

        return response()
            ->json(['code' => $statusCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOutboundReady(Request $request)
    {
        $responseMessage = 'Get Outbound Ready Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = Order::where('status', 'READY_FOR_OUTBOUND')->whereNull('picked_status')->limit(100)->orderBy('created_at', 'DESC')->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Outbound Ready Success';
            $responseData    = $datas;
            $responseCode    = '00';
        } else {
            $responseMessage = 'No Outbound Ready';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOutboundLocations(Request $request, $id)
    {
        $responseMessage = 'Get Outbound Location Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('order_detail')
            ->select('order_detail.orders_id', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked')
            ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
            ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->leftJoin('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
            ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->where('order_detail.orders_id', '=', $id)
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Outbound Location Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOutboundLocationsByQr(Request $request)
    {
        $responseMessage = 'Get Outbound Location Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if (isset($input)) {
            if ($input->code != null) {
                $datas = DB::table('orders')
                    ->select('orders.id', 'order_detail.id', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'order_detail.inbound_detail_id', '=', 'inbound_detail.id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->leftJoin('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
                    ->leftJoin('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                    ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('orders.order_number', '=', $input->code)
                    ->get();
                
                if (count($datas)) {
                    $responseMessage = 'Get Outbound Location Success';
                    $responseData    = $datas;
                    $responseCode    = '00';
                }
            } else {
                $responseMessage = "QR Code is required.";
            }
        } else {
            $responseMessage = "QR Code is required.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function updateOutboundLocation(Request $request)
    {
        $responseMessage = 'Update Outbound Location Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if (isset($input)) {
            if ($input->order_id != null && count($input->products) > 0) {
                foreach ($input->products as $product_code) {
                    $llocation = InboundLocation::where('inbound_detail_location.code', $product_code)->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')->select('inbound_detail_location.*', 'inbound_detail.product_type_size_id', 'inbound_detail.color', 'inbound_detail.product_id');
                    $odetail   = OrderDetail::where('orders_id', $input->order_id)->where('inbound_detail_id', $llocation->first()->inbound_detail_id);
                    if ($llocation->count() > 0) {
                        if ($odetail->count() > 0) {
                            $llocdata = $llocation->first();
                            
                            // Reset the inbound location that reserved on order
                            DB::table('inbound_detail_location')
                                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                                ->where('inbound_detail.product_type_size_id', '=', $llocdata->product_type_size_id)
                                ->where('inbound_detail.color', '=', $llocdata->color)
                                ->where('inbound_detail.product_id', '=', $llocdata->product_id)
                                ->whereNull('inbound_detail_location.date_picked')
                                ->whereNull('inbound_detail_location.date_outbounded')
                                ->whereNotNull('inbound_detail_location.date_ordered')
                                ->whereNotNull('inbound_detail_location.order_detail_id')
                                ->update([
                                    'inbound_detail_location.date_ordered'    => null,
                                    'inbound_detail_location.order_detail_id' => null,
                                    'updated_at' => Carbon::now(),
                                ]);
                            
                            // Using picked item physically
                            $llocdata->order_detail_id = $odetail->first()->id;
                            $llocdata->date_picked     = date('Y-m-d H:i:s');
                            $llocdata->date_ordered    = $odetail->first()->created_at;
                            $llocdata->save();
                            
                            $odetaild = $odetail->first();
                            if ($odetaild->inbound_detail_location_id != $llocdata->id) {
                                $llocdata2                  = InboundLocation::find($llocdata->id);
                                $llocdata2->date_ordered    = null;
                                $llocdata2->date_picked     = null;
                                $llocdata2->order_detail_id = null;
                                $llocdata2->save();
                                
                                $odetaild->inbound_detail_location_id = $llocdata->id;
                                $odetaild->save();
                            }
                        } else {
                            $product         = DB::table('product')
                                ->join('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                                ->where('inbound_detail.id', '=', $plocation->first()->inbound_detail_id)
                                ->select('product.name', 'inbound_detail.color', 'product_type_size.name as size_name')
                                ->first();
                            $responseMessage = $product->name . ' ' . $product->color . ' (' . $product->size_name . ') is not listed in outbound list, return to shelf and pick the right item.';
                            
                            return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
                        }
                    } else {
                        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
                        break;
                    }
                }
                
                $responseData    = DB::table('order_detail')
                    ->select('inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
                    ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('order_detail.orders_id', '=', $input->order_id)
                    ->get();
                $responseCode    = '00';
                $responseMessage = "Update outbound location success!";
                
                
            //act missing data
                /*if($input->missing_ids != ""){
                    $missing_ids_arr = explode("|", $input->missing_ids);
                    foreach($missing_ids_arr as $missing_id) {
                        DB::select("update inbound_detail_location set shelf_id = null where id = ". $missing_id);
                        DB::select("INSERT INTO adjustment (inbound_detail_location_id, type_id) VALUES (".$missing_id.",2)");
                        $i++;
                    }

                    if($i == count($ids_arr)){
                        $responseMessage = 'Stock Opname Success';
                        $responseData = $datas;
                        $responseCode = '00';
                    }
                }*/
            } else {
                $responseMessage = "Please make sure you provide the order id and at least one product is scanned.";
            }
        } else {
            $responseMessage = "Order id and product list is required.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function setOutboundReady(Request $request)
    {
        $responseMessage = 'Set Outbound Ready Failed';
        $responseData    = null;
        $responseCode    = '01';

        $shipmentApproved = new \stdClass;
        $shipmentApproved->data = new \stdClass;
        $shipmentApproved->data->order = new \stdClass;
        $shipmentApproved->data->order->order_ids = array();
        
        $input = json_decode($request->getContent());
        
        if ($input->code != null) {
            $orders = DB::table('order_detail')
                ->join('orders', 'orders.id', '=', 'order_detail.orders_id')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                ->where('orders.order_number', '=', $input->code)
                ->select('inbound_detail_location.date_picked', 'orders.status')
                ->get();

            $orderStatus = DB::table('order_detail')
            ->join('orders', 'orders.id', '=', 'order_detail.orders_id')
            ->where('orders.order_number', '=', $input->code)
            ->select('orders.status')
            ->get();
            
            if(count($orderStatus) > 0) {
                if(strcmp($orderStatus[0]->status, Config::get('constants.pakde_order_status.READY_FOR_OUTBOUND')) != 0){
                    $responseMessage = "Invalid Status For Order " . $input->code . ", Expect READY_FOR_OUTBOUND While Got " .$orderStatus[0]->status. ".";
                    return response()
                        ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => null, 'data-request' => $request->getContent()]);
                }
            }
            
            $partial_force = false;
            $is_head       = ($request->user->roles == 'head');
            
            foreach ($orders as $key => $order) {
                if ($order->date_picked == null) {
                    $partial_force = true;
                }
            }
            
            if (($partial_force && $is_head) || (!$partial_force)) {
                $ord                = Order::where('order_number', $input->code)->first();
                $ord->status        = 'READY_TO_PACK';
                $ord->picked_status = ($partial_force) ? 'PARTIAL' : 'FULL';
                if ($partial_force) {
                    $ord->force_picked_date = date('Y-m-d H:i:s');
                    $ord->forcer_id         = $request->user->id;
                }
                $ord->save();

                $hist = new OrderHistory;
                $hist->order_id = $ord->id;
                $hist->status = 'READY_TO_PACK';
                $hist->user_id = $request->user->id;
                $hist->save();
                
                if ($input->missing_od_ids != null && $input->missing_od_ids != "") {
                    $missing_ids = explode("|", $input->missing_od_ids);
                    DB::table('order_detail')
                        ->whereIn('id', $missing_ids)
                        ->update([
                            'is_missed' => 1,
                            'updated_at' => Carbon::now(),
                        ]);
                }
                
                //save the awb of ready to pack order
                array_push($shipmentApproved->data->order->order_ids, $ord->id);
                if (count($shipmentApproved->data->order->order_ids) > 0) {
                    $awb_response = json_decode($this->saveMarketplaceAWB($shipmentApproved)->getBody());
                    if ($awb_response->metadata->status_code != 200) {
                        $response->message = $awb_response->metadata->message;
                        $response->data     = null;
                        $response->response_code    = strval($awb_response->metadata->status_code);
                        return $response;
                    }
                }

                $responseCode    = '00';
                $responseMessage = "Congratulations, this order is ready to pack!";
            } else {
                $responseMessage = "You are not allowed to force outbound partially. Please contact your warehouse head.";
            }
        } else {
            $responseMessage = "Parameter order id is needed.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getReadyPack(Request $request)
    {
        $responseMessage = 'Get Pack Ready Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = Order::where('status', 'READY_TO_PACK')->whereNull('packed_date')->orderBy('created_at', 'DESC')->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Pack Ready Success';
            $responseData    = $datas;
            $responseCode    = '00';
        } else {
            $responseMessage = 'No Pack Ready';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getPackLocations(Request $request, $id)
    {
        $responseMessage = 'Get Pack Location Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('order_detail')
            ->select('order_detail.orders_id', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked')
            ->leftJoin('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
            ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->leftJoin('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
            ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->where('order_detail.orders_id', '=', $id)
            ->whereNotNull('inbound_detail_location.date_picked')
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Pack Location Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getPackLocationsByQr(Request $request)
    {
        $responseMessage = 'Get Pack Location Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if (isset($input)) {
            if ($input->code != null) {
                $query_by_order_number = DB::table('orders')
                    ->select('orders.id', 'orders.status as orders_status', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked', 'customer.name as customer_name', 'orders.client_id as client_id')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->leftJoin('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->leftJoin('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
                    ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('orders.order_number', '=', $input->code)
                    ->whereNotNull('inbound_detail_location.date_picked');

                $query_by_receipt_number = DB::table('orders')
                ->select('orders.id', 'orders.status as orders_status', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked', 'customer.name as customer_name', 'orders.client_id as client_id')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->leftJoin('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->leftJoin('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
                ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->where('orders.no_resi', '=', $input->code)
                ->whereNotNull('inbound_detail_location.date_picked');

                $datas = $query_by_order_number->get();
                if(count($datas) <= 0 ) {
                    $datas = $query_by_receipt_number->get();
                }
                
                if (count($datas)) {
                    $responseMessage = 'Get Pack Location Success';
                    $responseData    = $datas;
                    $responseCode    = '00';
                }
            } else {
                $responseMessage = "QR Code is required.";
            }
        } else {
            $responseMessage = "QR Code is required.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function updatePackingLocation(Request $request)
    {
        $jproduct        = new Product();
        $responseMessage = 'Update Packing Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if (isset($input)) {
            if ($input->order_id != null && count($input->products) > 0) {
                foreach ($input->products as $product_code) {
                    $llocation = InboundLocation::where('code', $product_code);
                    $odetail   = OrderDetail::where('orders_id', $input->order_id)->where('inbound_detail_location_id', $llocation->first()->id);
                    if ($llocation->count() > 0) {
                        if ($odetail->count() > 0) {
                            $llocdata                  = $llocation->first();
                            $llocdata->date_outbounded = date('Y-m-d H:i:s');
                            $llocdata->save();
                            
                            $inboundDetail = DB::table('product')
                                ->join('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                                ->join('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                                ->where('inbound_detail_location.id', '=', $llocdata->id)
                                ->select('product.client_id as client_id', 'inbound_detail.sku as inbound_detail_sku')
                                ->first();
                            
                            if ($inboundDetail) {
                                $jproduct->updateStockToJubelio($inboundDetail->inbound_detail_sku, $inboundDetail->client_id, -1);
                            }
                        } else {
                            $product         = DB::table('product')
                                ->join('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                                ->where('inbound_detail.id', '=', $plocation->first()->inbound_detail_id)
                                ->select('product.name', 'inbound_detail.color', 'product_type_size.name as size_name')
                                ->first();
                            $responseMessage = $product->name . ' ' . $product->color . ' (' . $product->size_name . ') is not listed in packing list, return to shelf and pick the right item.';
                            
                            return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
                        }
                    } else {
                        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
                        break;
                    }
                }
                
                $responseData    = DB::table('order_detail')
                    ->select('inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
                    ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('order_detail.orders_id', '=', $input->order_id)
                    ->whereNotNull('inbound_detail_location.date_picked')
                    ->get();
                $responseCode    = '00';
                $responseMessage = "Update packing location success!";
            } else {
                $responseMessage = "Please make sure you provide the order id and at least one product is scanned.";
            }
        } else {
            $responseMessage = "Order id and product list is required.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function setPackingReady(Request $request)
    {
        $responseMessage = 'Set Packing Ready Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->order_id != null) {
            $orders = DB::table('order_detail')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                ->where('order_detail.orders_id', '=', $input->order_id)
                ->select('inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked')
                ->get();

            $order_status = DB::table('orders')
                ->where("id", $input->order_id)
                ->select("status", "order_number")
                ->get();

            if(count($order_status) > 0) {
                if(strcmp($order_status[0]->status, Config::get('constants.pakde_order_status.READY_TO_PACK')) != 0) {
                    $responseMessage =  "Invalid Status For Order " . $order_status[0]->order_number . ", Expect READY_TO_PACK While Got " .$order_status[0]->status. ".";
                    return response()
                        ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
                }
            }
            
            $is_ready = true;
            
            foreach ($orders as $key => $order) {
                if ($order->date_outbounded == null || $order->date_picked == null) {
                    $is_ready = false;
                    break;
                }
            }
            
            if ($is_ready) {
                $ord              = Order::find($input->order_id);
                $ord->status      = 'AWAITING_FOR_SHIPMENT';
                $ord->packed_date = date('Y-m-d H:i:s');
                $ord->packer_id   = $request->user->id;
                $ord->save();

                $hist = new OrderHistory;
                $hist->order_id = $ord->id;
                $hist->status = 'AWAITING_FOR_SHIPMENT';
                $hist->user_id = $request->user->id;
                $hist->save();
                
                $responseCode    = '00';
                $responseMessage = "Yippie, this order is ready to ship!";
            } else {
                $responseMessage = "All products need to be scanned before packing.";
            }
        } else {
            $responseMessage = "Parameter order id is needed.";
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getItemsInsideByShelfCode(Request $request)
    {
        $responseMessage = 'Get Inbound Location List Failed';
        $responseData    = null;
        $responseShelf   = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->code != null) {
            
            //$saves = DB::select("SELECT saves FROM shelf where code = '".$input->code."'");
            $shelf_attr = DB::table('shelf')->select('*')->where('code', $input->code)->first();
            
            $datas = DB::select('SELECT inbound_detail_location.id as inbound_detail_location_id,inbound_detail_location.shelf_id,inbound_detail.name,inbound_detail_location.code,inbound_detail.color,(SELECT name FROM product_type_size where id = inbound_detail.product_type_size_id) as size, shelf.code as shelf_code, inbound_detail_location.code as item_code, shelf.date_opnamed  
                FROM inbound_detail_location 
                INNER JOIN inbound_detail on inbound_detail_location.inbound_detail_id = inbound_detail.id  INNER JOIN shelf ON shelf.id = inbound_detail_location.shelf_id where inbound_detail_location.date_outbounded is null and inbound_detail_location.date_picked is null and shelf.code = "' . $input->code . '"');
            
            if (count($datas)) {
                $responseData = $datas;
            }
            
            if ($shelf_attr != null) {
                $responseShelf = $shelf_attr;
            }
            
            $responseMessage = 'Get Success';
            $responseCode    = '00';
        } else {
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'shelf' => $responseShelf, 'data-request' => $request->getContent()]);
    }
    
    public function getShelfByWarehouseId(Request $request, $id)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select('SELECT shelf.id,shelf.code,shelf.col,shelf.row,shelf.name,rack.warehouse_id,shelf.date_opnamed,(SELECT count(id) FROM inbound_detail_location where shelf_id = shelf.id and date_outbounded is null and date_picked is null) as items FROM rack INNER JOIN shelf ON rack.id = shelf.rack_id where warehouse_id = ' . $id . ' order by shelf.date_opnamed desc LIMIT 100');
        
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function fetchScanOutboundPreviewByCode(Request $request)
    {
        $responseMessage = 'Fetch Failed';
        $responseData    = null;
        $responseCode    = '01';

        $shelves = [];
        
        $input = json_decode($request->getContent());
        if ($input->code != null) {
            $query_by_order_number = DB::table('orders')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->leftJoin(DB::raw('inbound_detail_location idl'), 'idl.id', 'order_detail.inbound_detail_location_id')
                ->where('orders.order_number', '=', strtoupper($input->code))
                ->select('orders.id as orders_id', 'orders.order_number as orders_code', 'order_detail.id as order_detail_id',
                    'orders.status as orders_status', 'inbound_detail.id as inbound_detail_id', 'inbound.product_id',
                    'product.name as product_name', 'inbound_detail.color as product_color',
                    'product_type_size.id as product_type_size_id', 'product_type_size.name as product_size',
                    'order_detail.inbound_detail_location_id', 'customer.name as customer_name', 'orders.client_id as client_id',
                    'idl.code', 'idl.date_picked');

            $query_by_receipt_number = DB::table('orders')
            ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->join('customer', 'customer.id', '=', 'orders.customer_id')
            ->leftJoin(DB::raw('inbound_detail_location idl'), 'idl.id', 'order_detail.inbound_detail_location_id')
            ->where('orders.no_resi', '=', strtoupper($input->code))
            ->select('orders.id as orders_id', 'orders.order_number as orders_code', 'order_detail.id as order_detail_id',
                'orders.status as orders_status', 'inbound_detail.id as inbound_detail_id', 'inbound.product_id',
                'product.name as product_name', 'inbound_detail.color as product_color',
                'product_type_size.id as product_type_size_id', 'product_type_size.name as product_size',
                'order_detail.inbound_detail_location_id', 'customer.name as customer_name', 'orders.client_id as client_id',
                'idl.code', 'idl.date_picked');

            $datas = $query_by_order_number->get();
            if (count($datas) <= 0) {
                $datas = $query_by_receipt_number->get();
            }

            foreach ($datas as $key => $data) {

                $index = $data->product_id . '-' . $data->product_type_size_id;
                if (!array_key_exists($index, $shelves)) {
                    $datas[$key]->shelves = collect(
                        DB::select("select distinct shelf.name
                        from inbound_detail_location
                        inner join inbound_detail on inbound_detail.id = inbound_detail_location.inbound_detail_id inner
                        join shelf on shelf.id = inbound_detail_location.shelf_id where
                        inbound_detail_location.date_outbounded is NULL and
                        inbound_detail_location.date_picked is NULL and
                        inbound_detail.product_id = " . $data->product_id . " and inbound_detail.product_type_size_id = 
                        " . $data->product_type_size_id))->map(function ($x) {
                        return $x->name;
                    })->toArray();

                    $shelves[$index] = $datas[$key]->shelves;
                } else {
                    $datas[$key]->shelves = $shelves[$index];
                }
                
                // $datas[$key]->inbound_detail_location_code = DB::table('inbound_detail_location')
                //     ->where('id', $data->inbound_detail_location_id)
                //     ->select('code', 'date_picked')
                //     ->first();
            }
        }
        
        if (count($datas)) {
            $responseMessage = 'Fetch Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getAdjustmentList(Request $request)
    {
        $responseMessage = 'Sorry, no adjustment at this time';
        $responseData    = null;
        $responseCode    = '01';
        
        $status = (($request->input('status') != null) ? $request->input('status') : 0);
        
        $datas = DB::table('adjustment')
            ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
            ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
            ->join('client', 'client.id', '=', 'inbound.client_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->select('adjustment.id as id', 'adjustment.inbound_detail_location_id as inbound_detail_location_id', 'inbound_detail.name as product_name', 'inbound_detail.color as color', 'product_type_size.name as size', 'inbound_detail_location.code', 'client.name as client', 'adjustment.updated_at as updated_at', 'shelf.name as shelf_name', 'inbound_detail_location.code as product_code')
            ->where('adjustment.status', '=', $status)
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Adjustment Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function addAdjustment(Request $request)
    {
        $responseMessage = 'Opname Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if (isset($input)) {
            if ($input->shelf_code != null) {
                DB::select("delete adjustment.* from adjustment inner join inbound_detail_location on adjustment.inbound_detail_location_id = inbound_detail_location.id inner join shelf on inbound_detail_location.shelf_id = shelf.id where shelf.code = '" . $input->shelf_code . "' and adjustment.status = 0 and inbound_detail_location.date_outbounded is null and inbound_detail_location.date_picked is null");
                
                if ($input->inbound_detail_location_ids != null && $input->inbound_detail_location_ids != "") {
                    $locations = explode("|", $input->inbound_detail_location_ids);
                    $params    = array();
                    foreach ($locations as $value) {
                        if (Adjustment::where('inbound_detail_location_id', $value)->count() == 0) {
                            $params[] = array(
                                'inbound_detail_location_id' => $value,
                                'reporter_id'                => $request->user->id
                            );
                        }
                    }
                    Adjustment::insert($params);
                }
                
                $shelf = Shelf::where('code', $input->shelf_code)->first();
                $shelf->date_opnamed = date('Y-m-d H:i:s');
                if ($input->saves_codes != null && $input->saves_codes != "") {
                    $shelf->saves = $input->saves_codes;
                } else {
                    $shelf->saves = null;
                }
                $shelf->save();
                $responseCode    = '00';
                $responseMessage = 'Opname Success!';
            }
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function updateAdjustmentStock(Request $request)
    {
        $responseMessage = 'Update Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->id != null) {
            DB::select("update adjustment set status = 1, approver_id = " . $request->user->id . ", updated_at = '".Carbon::now()."' where inbound_detail_location_id = " . $input->id);
            
            DB::select("update inbound_detail_location set shelf_id = null, updated_at = '".Carbon::now()."', order_detail_id = 0,date_picked = '" . date('Y-m-d H:i:s') . "', date_outbounded = '" . date('Y-m-d H:i:s') . "',date_adjustment = '" . date('Y-m-d H:i:s') . "' where id = " . $input->id);
            
            $responseMessage = 'Update Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function cancelOutboundByCode(Request $request)
    {
        $responseMessage = 'Canceling Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->code != null) {
            DB::select("update inbound_detail_location idl INNER JOIN order_detail od ON idl.order_detail_id = od.id SET idl.updated_at = '".Carbon::now()."', idl.order_detail_id = NULL,idl.date_ordered = NULL,idl.date_picked = NULL,idl.date_adjustment = NULL,idl.date_outbounded = NULL where od.orders_id = (SELECT id FROM orders where order_number = '" . $input->code . "')");

            $ord = Order::where('order_number', '=', $input->code)->first();
            $ord->status = 'CANCELED';
            $ord->updated_at = Carbon::now();
            $ord->save();

            $hist = new OrderHistory;
            $hist->order_id = $ord->id;
            $hist->status = 'CANCELED';
            $hist->user_id = $request->user->id;
            $hist->save();

            $responseCode    = '00';
            $responseMessage = 'Canceling Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function moveProductToShelf(Request $request)
    {
        $responseMessage = 'Moving Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->shelf_code != null && $input->product_code != null) {
            DB::select("update inbound_detail_location set updated_at = '".Carbon::now()."', shelf_id = (SELECT id FROM shelf where code = '" . $input->shelf_code . "')  where code = '" . $input->product_code . "'");
            
            $responseMessage = 'Moving Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getMissedOutbound(Request $request)
    {
        $responseMessage = 'No Data';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select("select orders.id as 'orders_id',orders.order_number as 'orders_code',order_detail.id as 'order_detail_id',inbound_detail.color as 'product_color', (SELECT name FROM inbound WHERE id = inbound_detail.inbound_id) AS 'product_name',
            (SELECT name FROM product_type_size WHERE id = inbound_detail.product_type_size_id) AS 'product_size',
            (SELECT GROUP_CONCAT(DISTINCT shelf.name SEPARATOR '|') FROM inbound_detail_location inner join shelf on inbound_detail_location.shelf_id = shelf.id WHERE inbound_detail_id = inbound_detail.id) AS 'shelves', 
                (SELECT GROUP_CONCAT(DISTINCT shelf.code SEPARATOR '|') FROM inbound_detail_location inner join shelf on inbound_detail_location.shelf_id = shelf.id WHERE inbound_detail_id = inbound_detail.id) AS 'shelves_code', order_detail.updated_at as updated_at,
                (SELECT name FROM client WHERE id = (SELECT client_id FROM inbound WHERE id = inbound_detail.inbound_id)) as client
                from orders inner join order_detail on order_detail.orders_id = orders.id INNER JOIN inbound_detail on inbound_detail.id =order_detail.inbound_detail_id where is_missed = 1");
        
        if (count($datas)) {
            $responseMessage = 'Get Data Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function clearOutboundOrderByCode(Request $request)
    {
        $responseMessage = 'Clearing Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->code != null) {
            
            //DB::select("update order_detail set inbound_detail_location_id = NULL where orders_id = (SELECT id FROM orders where code = '".$input->code."')");

            // Order --> Order Detail --> inbound detail location
            // 1. select order sm order detail --> input code
            // 2. update inbund detail location based on no. 1 data

            $order = DB::table('orders')
            ->where('order_number', '=', strtoupper($input->code))
            ->first();

            $details = DB::table('order_detail')
            ->where('orders_id', '=', $order->id)
            ->pluck('id')->toArray();
            
            DB::table('inbound_detail_location')
            ->whereIn('inbound_detail_location.order_detail_id', $details)
            ->update([
                'inbound_detail_location.order_detail_id' => null,
                'inbound_detail_location.date_ordered'    => null,
                'inbound_detail_location.date_picked'     => null,
                'inbound_detail_location.date_outbounded' => null,
                'updated_at' => Carbon::now(),
            ]);
            
            DB::table('order_detail')
            ->where('orders_id', '=', $order->id)
            ->update([
                'inbound_detail_location_id' => null,
                'updated_at' => Carbon::now(),
            ]);
            
            // DB::select("update inbound_detail_location idl INNER JOIN order_detail od ON idl.order_detail_id = od.id SET idl.order_detail_id = NULL,date_picked=NULL,date_ordered=NULL, od.inbound_detail_location_id = NULL where od.orders_id = (SELECT id FROM orders where code = '".$input->code."') and idl.date_outbounded != NULL");
            
            $responseCode    = '00';
            $responseMessage = 'Clearing Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function scanOutbound(Request $request) {
        $responseMessage = 'Update Failed';
        $responseData    = "";
        $responseCode    = '01';

        $input = json_decode($request->getContent());
        if ($input->order_id != null && $input->product_code != null) {
            $orders = DB::table(DB::raw('orders o'))
                ->select('od.id as order_detail_id', 'idt.product_type_size_id', 'idt.product_id',
                    'od.inbound_detail_location_id','idl.date_ordered')
                ->join(DB::raw('order_detail od'), 'od.orders_id', '=', 'o.id')
                ->join(DB::raw('inbound_detail idt'), 'idt.id', '=', 'od.inbound_detail_id')
                ->leftJoin(DB::raw('inbound_detail_location idl'), 'idl.id', '=', 'od.inbound_detail_location_id')
                ->where('o.id', '=', $input->order_id)
                ->whereNull('idl.date_picked')
                ->get();   
            
            $inbound_detail_location = DB::table(DB::raw('inbound_detail_location idl'))
                ->join(DB::raw('inbound_detail idt'), 'idl.inbound_detail_id', '=', 'idt.id')
                ->select('idl.id', 'idl.inbound_detail_id', 'idl.order_detail_id', 'idt.product_type_size_id', 'idt.product_id', 'idl.date_ordered')
                ->where('idl.code', '=', $input->product_code)
                ->whereNull('idl.date_picked')
                ->whereNotNull('idl.date_stored')
                ->first();
   
            Log::info("Inbound");
            Log::info(print_r($inbound_detail_location, true));
            if ($inbound_detail_location != null) {
                foreach ($orders as $order) {

                    Log::info("Order");
                    Log::info(print_r($order, true));
                    if ($order->product_type_size_id == $inbound_detail_location->product_type_size_id
                        && $order->product_id == $inbound_detail_location->product_id) {
                        DB::table('outbound_scan_log')
                            ->insert([
                                "order_detail_id" => $order->order_detail_id,
                                "inbound_location_id" => $order->inbound_detail_location_id,
                                "new_inbound_location_id" => $inbound_detail_location->id,
                                "created_at" => Carbon::now()
                            ]);

                        DB::beginTransaction();
                        try {
                            DB::table('inbound_detail_location')
                                ->where('id', $order->inbound_detail_location_id)
                                ->update([
                                    'updated_at' => Carbon::now(),
                                    'order_detail_id' => $inbound_detail_location->order_detail_id,
                                    'date_ordered' => $inbound_detail_location->date_ordered,
                                    'date_picked' => null,
                                    'picker_id' => null
                                ]);
        
                            if ($inbound_detail_location->order_detail_id != null) {
                                DB::table('order_detail')
                                    ->where('id', '=', $inbound_detail_location->order_detail_id)
                                    ->update([
                                        'inbound_detail_location_id' => $order->inbound_detail_location_id,
                                        'updated_at' => Carbon::now()
                                    ]);
                            }

                            DB::table('inbound_detail_location')
                                ->where('code', '=', $input->product_code)
                                ->update([
                                    'date_ordered' => $order->date_ordered,
                                    'date_picked' => Carbon::now(),
                                    'order_detail_id' => $order->order_detail_id,
                                    'updated_at' => Carbon::now(),
                                    'picker_id' => $request->user->id 
                                ]);

                            DB::table('order_detail')
                                ->where('id', '=', $order->order_detail_id)
                                ->update([
                                    'inbound_detail_id' => $inbound_detail_location->inbound_detail_id,
                                    'inbound_detail_location_id' => $inbound_detail_location->id,
                                    'updated_at' => Carbon::now()
                                ]);
                            
                            DB::commit();

                            $responseCode    = '00';
                            $responseMessage = 'Update Success';
                            $responseData    = [
                                "order_detail_id" => $order->order_detail_id,
                                "inbound_detail_location_id" => $inbound_detail_location->id,
                                "inbound_detail_location_code" => $input->product_code
                            ];
                        } catch (Exception $e) {
                            DB::rollback();
                            Log::error($e);
                        }
                        
                        break;
                    }
                }
            } else {
                $responseMessage = 'Item already taken';
            }
        }

        // Log::info('Debugbar');
        // Log::info(app('debugbar')->getData());

        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData,
            'data-request' => $request->getContent()]);
    }
    
    public function approveAdjustmentStockIds(Request $request)
    {
        $responseMessage = 'Approve Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());        
        if ($input->ids != null) {
            $ids_arr = explode("|", $input->ids);
            foreach ($ids_arr as $id) {
                DB::select("update adjustment set status = 1, updated_at = '".Carbon::now()."', 
                            approver_id = " . $request->user->id . " 
                            where inbound_detail_location_id = " . $id);
                DB::select("update inbound_detail_location set updated_at = '".Carbon::now()."', 
                            order_detail_id = 0,date_picked = '" . date('Y-m-d H:i:s') . "', 
                            date_outbounded = '" . date('Y-m-d H:i:s') . "',
                            date_adjustment = '" . date('Y-m-d H:i:s') . "' 
                            where id = " . $id);
            }
            $responseCode    = '00';
            $responseMessage = 'Approve Success';
        }
        
        $this->adjustStock($request);

        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getItemsSummary(Request $request)
    {
        $responseMessage = 'Fetch Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->val_codes != null && $input->val_codes != "") {
            $str_wheres     = "WHERE inbound_dl.code = " . str_replace("|", " or inbound_dl.code = ", $input->val_codes);
            $str_wheres_sub = "and (sub_inbound_dl.code = " . str_replace("|", " or sub_inbound_dl.code = ", $input->val_codes) . ")";
            
            //$queryy = "SELECT distinct inbound_d.name as the_name,inbound_d.color as the_color,(SELECT name FROM product_type_size where id = inbound_d.product_type_size_id) as the_size,(SELECT count(sub_inbound_dl.id) FROM inbound_detail as sub_inbound_d inner join inbound_detail_location as sub_inbound_dl on sub_inbound_d.id = sub_inbound_dl.inbound_detail_id WHERE sub_inbound_d.name = inbound_d.name and  sub_inbound_d.color = inbound_d.color and sub_inbound_d.product_type_size_id = inbound_d.product_type_size_id ".$str_wheres_sub.") as the_qty FROM inbound_detail as inbound_d inner join inbound_detail_location as inbound_dl on inbound_d.id = inbound_dl.inbound_detail_id ".$str_wheres;
            
            $responseData = DB::select("SELECT distinct product.name as the_name,inbound_d.color as the_color,(SELECT name FROM product_type_size where id = inbound_d.product_type_size_id) as the_size,(SELECT count(sub_inbound_dl.id) FROM inbound_detail as sub_inbound_d inner join inbound_detail_location as sub_inbound_dl on sub_inbound_d.id = sub_inbound_dl.inbound_detail_id WHERE sub_inbound_d.name = inbound_d.name and  sub_inbound_d.color = inbound_d.color and sub_inbound_d.product_type_size_id = inbound_d.product_type_size_id " . $str_wheres_sub . ") as the_qty FROM inbound_detail as inbound_d inner join inbound_detail_location as inbound_dl on inbound_d.id = inbound_dl.inbound_detail_id inner join product on inbound_d.product_id=product.id " . $str_wheres);
            
            $responseCode    = '00';
            $responseMessage = 'Fetch Success';
        }
        
        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    
    public function movingProductToShelf(Request $request)
    {
        $responseMessage = 'Moving Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->codes != null && $input->shelf_code != null) {
            $shelf_id = DB::table('shelf')->where('code', $input->shelf_code)->value('id');
            
            if ($shelf_id != null) {
                $moved     = 0;
                $loss      = 0;
                $codes_arr = explode("|", $input->codes);
                foreach ($codes_arr as $code) {
                    $data_scalar = DB::select("SELECT shelf_id from inbound_detail_location where code = '" . $code . "' and shelf_id is not null");
                    if ($data_scalar) {
                        DB::select("update inbound_detail_location set shelf_id = " . $shelf_id . ", updated_at = '".Carbon::now()."'  where code = '" . $code . "'");
                        $moved++;
                    } else {
                        $loss++;
                    }
                }
                $responseCode    = '00';
                $responseMessage = "Moving Success : " . $moved . ", Failed: " . $loss;
            } else {
                $responseMessage = 'Moving Failed, Shelf is not found!';
            }
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function cancelMultiOutboundByIds(Request $request)
    {
        $responseMessage = 'Canceling Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->ids != null) {
            $ids_arr = explode("|", $input->ids);
            foreach ($ids_arr as $id) {

                $orders = DB::table('orders')
                ->where('batch_id', '=', $id)
                ->select('id')
                ->get();

                foreach($orders as $key => $order) {
                    DB::select("update inbound_detail_location idl INNER JOIN order_detail od ON idl.order_detail_id = od.id SET idl.updated_at = '".Carbon::now()."', idl.order_detail_id = NULL,idl.date_ordered = NULL,idl.date_picked = NULL,idl.date_adjustment = NULL,idl.date_outbounded = NULL where od.orders_id = " . $order->id);
                    DB::select("update orders set status = 'CANCELED', updated_at = '".Carbon::now()."' where id = " . $order->id);
    
                    $hist = new OrderHistory;
                    $hist->order_id = $order->id;
                    $hist->status = 'CANCELED';
                    $hist->user_id = $request->user->id;
                    $hist->save();
                } 
            }
            
            $responseCode    = '00';
            $responseMessage = 'Canceling Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function fetchOrderReadingByCode(Request $request)
    {
        $responseMessage = 'Fetch Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        if ($input->code != null) {
            $datas = DB::table('orders')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->where('orders.order_number', '=', strtoupper($input->code))
                ->select(
                    'orders.id as orders_id',
                    'orders.order_number as orders_code',
                    'orders.status as orders_status',
                    'order_detail.id as order_detail_id',
                    'inbound_detail.id as inbound_detail_id',
                    'inbound.product_id',
                    'product.name as product_name',
                    'inbound_detail.color as product_color',
                    'product_type_size.id as product_type_size_id',
                    'product_type_size.name as product_size',
                    'order_detail.inbound_detail_location_id',
                    'customer.name as customer_name'
                )
                ->get();
            
            foreach ($datas as $key => $data) {
                $datas[$key]->product_codes = DB::table('inbound_detail_location')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->where('inbound_detail.product_id', '=', $data->product_id)
                    ->where('inbound_detail.product_type_size_id', '=', $data->product_type_size_id)
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->whereNull('inbound_detail_location.order_detail_id')
                    ->whereNull('inbound_detail_location.date_picked')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->count();
                
                $datas[$key]->product_uniques = DB::table('order_detail')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->where('order_detail.id', '=', $data->order_detail_id)
                    ->where('inbound_detail.product_id', '=', $data->product_id)
                    ->where('inbound_detail.product_type_size_id', '=', $data->product_type_size_id)
                    ->count();
            }
        }
        
        if (count($datas)) {
            $responseMessage = 'Fetch Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function approveMultiOutbound(Request $request, $warehouse_id)
    {
        $count_approved  = 0;
        $count_failed    = 0;
        $raws      = array();
        //response container
        $response = new \stdClass;
        $response->response_code = "00";
        $response->data = null;
        $input = json_decode($request->getContent());

        //shipping label param container
        $labelParams = [
            "data" => [
                "label" => []
            ]
        ];

        if ($input->order_batch_ids != null) {
            //get list of order number
            $batch_ids = explode("|", $input->order_batch_ids);

            //iterate the order batch
            foreach($batch_ids as $key=>$batch_id) {
                $order_number = DB::table('orders')
                    ->where('batch_id', $batch_id)
                    ->pluck('order_number')->toArray();
                //handling unbatchorder
                //limit 10 related to performance issue
                if(strcmp($batch_id, "null") == 0) {
                    $order_temps = DB::select("SELECT * FROM orders WHERE status = 'PENDING' AND picked_status IS NULL AND (warehouse_id = '" . $warehouse_id . "' OR warehouse_id IS NULL) AND batch_id IS NULL LIMIT 10");
                    foreach($order_temps as $key=>$order_temp){
                        array_push($order_number, $order_temp->order_number);
                    }
                }
            
                if(count($order_number) <= 0){
                    continue;
                }

                $response = $this->approveMultiOutboundByCodes($order_number, $request);
                if($response->response_code != '200') {
                    return response()
                        ->json(['code' => $response->response_code, 'message' => $response->message, 'data' => $response->data, 'data-request' => $request->getContent(), 'raw-result' => $raws]);
                }

                //aggregate the response
                $count_approved  = $count_approved + $response->count_approved;
                $count_failed    = $count_failed + $response->count_failed;

                //update the rest of order status in the same batch as insufficient_stock
                // $this->updateOrder($batch_id, $request->user->id);
                $count_order = $this->countOrderByBatchID($batch_id);

                //generate the approved shipping label
                $label = $this->generateLabelType();
                $this->createTemplate($label);
                $complete_orders = $this->getCompleteOrder($response->approved_order_number);
                $label_params = $this->generateLabelParam($label, $complete_orders);
                $labelParams['data']['label'] = $label_params;

                if(strcmp($batch_id, "null") == 0) {
                        // make there is at least one approved order
                        if (count($labelParams['data']['label']) > 0) {
                        $shipping_label = $this->generateLabel($labelParams);
                        $batch_id =  DB::table('order_batch')
                                        ->insertGetId([
                                            'template_url' => $shipping_label->data->url,
                                            'count_order' => $count_order,
                                            'count_success' => $response->count_approved,
                                            'is_printed' => 0,
                                            'is_deleted' => 0,
                                            'created_at' => Carbon::now(),
                                            'updated_at' => Carbon::now(),
                                            'client_id' => $complete_orders[0]->client_id,
                                            'user_id' => $request->user->id
                                        ]);

                            //update batch_id orders
                            $this->fillBatchId($order_number, $batch_id);

                            //re count order after batch is filled 
                            $count_order = $this->countOrderByBatchID($batch_id);
                            DB::table('order_batch')
                            ->where('id', '=', $batch_id)
                            ->update([
                                'count_order' => $count_order
                            ]);
                        } else {
                            $complete_orders = $this->getCompleteOrder(array_slice($order_number, 0, 1));
                            $batch_id = DB::table('order_batch')
                                        ->insertGetId([
                                            'template_url' => '',
                                            'count_order' => $count_order,
                                            'count_success' => 0,
                                            'is_printed' => 0,
                                            'is_deleted' => 0,
                                            'created_at' => Carbon::now(),
                                            'updated_at' => Carbon::now(),
                                            'client_id' => $complete_orders[0]->client_id,
                                            'user_id' => $request->user->id
                                        ]);            

                            //update batch_id orders
                            $this->fillBatchId($order_number, $batch_id);

                            //re count order after batch is filled 
                            $count_order = $this->countOrderByBatchID($batch_id);
                            DB::table('order_batch')
                            ->where('id', '=', $batch_id)
                            ->update([
                                'count_order' => $count_order
                            ]);
                        }
                } else {
                        // make there is at least one approved order
                        if (count($labelParams['data']['label']) > 0) {
                            $shipping_label = $this->generateLabel($labelParams);
                            //updated order batch
                            $this->updateOrderBatchByID($batch_id, $response, $shipping_label->data->url, $count_order, $complete_orders[0]->client_id, $request->user->id);
                        } else {
                            $complete_orders = $this->getCompleteOrder(array_slice($order_number, 0, 1));
                            $this->updateOrderBatchByID($batch_id, $response, '', $count_order, $complete_orders[0]->client_id, $request->user->id);
                        }
                }
            }
        }

        $response->message = "Orders Approved: " . $count_approved . ", Orders Failed to Approve: " . $count_failed;
        return response()
            ->json(['code' => $response->response_code, 'message' => $response->message, 'data' => $response->data, 'data-request' => $request->getContent()]);
    }

    public function fillBatchId($order_numbers, $batch_id) {
        foreach($order_numbers as $key => $order_number) {
            DB::table('orders')
            ->where('order_number', '=', $order_number)
            ->update([
                'batch_id' => $batch_id
            ]);
        }
    }

    public function updateOrderBatchByID($batch_id, $response, $template_url, $count_order, $client_id, $user_id) {
        DB::table('order_batch')
            ->where('id', '=', $batch_id)
            ->update([
                'template_url' => $template_url,
                'count_order' => $count_order,
                'count_success' => $response->count_approved,
                'client_id' => $client_id,
                'user_id' => $user_id,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function getCompleteOrder($order_number) 
    {
        $complete_order = DB::table('orders as o')
            ->join('client as cl', 'o.client_id', '=', 'cl.id')
            ->join('customer as c', 'o.customer_id', '=', 'c.id')
            ->whereIn('o.order_number',  $order_number)
            ->select('o.order_number', 'c.address', DB::raw('c.name as customer_name'), 'c.phone',
                'c.zip_code', DB::raw('cl.name as client_name'), 'o.courier', 'cl.logo_url', 'o.no_resi',
                'o.order_number', 'o.notes', 'o.client_id') 
            ->get();

        return $complete_order;
    }

    public function updateOrder($batch_id, $user_id)
    {
        DB::table('orders')
        ->where('batch_id', '=', $batch_id)
        ->where('status', '=', 'PENDING')
        ->update([
            'status' => 'INSUFFICIENT_STOCK',
            'updated_at' => Carbon::now(),
        ]);

        //record the order history
        $orders = DB::table('orders')
        ->where('batch_id', '=', $batch_id)
        ->where('status', '=', 'INSUFFICIENT_STOCK')
        ->get();
        
        foreach($orders as $key => $order) {
            $hist = new OrderHistory;
            $hist->order_id = $order->id;
            $hist->status = 'INSUFFICIENT_STOCK';
            $hist->user_id = $user_id;
            $hist->save();
        }
    }

    public function countOrderByBatchID($batch_id)
    {
        $order_count = DB::table('orders')
            ->where('batch_id', '=', $batch_id)
            ->count();

        return $order_count;
    }

    public function generateLabelType()
    {
        $clientHttp = app(Client::class);
        $label = json_decode(json_encode(DB::table('label')
        ->where('name', '=', Config::get('constants.label.ORDER'))
        ->first()), true);
        if ($label == null) {
            try {
                $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL_TYPE');
                $body = [];
                $body['data'] = [
                    'labeltype' => [
                        'name' => 'WMS Order Label',
                        'type' => 'LBL'
                    ]
                ];
    
                $response = $clientHttp->request("POST", $url, ['body' => json_encode($body)]);
                $response = json_decode($response->getBody()->getContents());
                $label = [];
                $label['label_id'] = $response->data->labeltype->id;
                DB::table('label')
                ->insert([
                    'name' => Config::get('constants.label.ORDER'),
                    'label_id' => $label['label_id']
                ]);
            } catch (\Exception $exception) {
                Log::error("Failed to call API to create label type: ". $exception);
                throw $exception;
            }
        }

        return $label;
    }

    public function createTemplate($label) {
        $clientHttp = app(Client::class);

        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_TEMPLATE');
            $html = view('dashboard.pdf.order')->render();
            $body = [];
            $body['data'] = [
                'template' => [
                    "description"=> "Label for wms orders",
                    "title"=> "WMS Order Template",
                    "type" => [
                        "id" => $label['label_id']
                    ],
                    "version"=> [
                        "html" => $html,
                        "number" => "1.0.0"
                    ]
                ]
            ];

            $response = $clientHttp->request("POST", $url, ['body' => json_encode($body)]);

        } catch (\Exception $exception) {
            Log::error("Failed to call API to create template: ". $exception);
            throw $exception;
        }
    }

    public function generateLabelParam($label, $order_completes) {
        $label_params = array();
        foreach($order_completes as $key => $order_complete) {
            $pdf = [];
            $pdf['page_size'] = Config::get('constants.label.PAGE_SIZE_ORDER');
            $pdf['page_margin'] = [
                'top' => 4,
                'right' => 4,
                'bottom' => 4,
                'left' => 4
            ];
            $pdf['logo'] = Config::get('constants.label.LOGO');
            $pdf['type'] = $label['label_id'];
            $pdf['params'] = [];
    
            $pdf['params']['consignee'] = [];
            $pdf['params']['consignee']['address'] = $order_complete->address . '';
            $pdf['params']['consignee']['name'] = $order_complete->customer_name . '';
            $pdf['params']['consignee']['phone'] = $order_complete->phone . '';
            $pdf['params']['consignee']['postcode'] = $order_complete->zip_code . '';
    
            $pdf['params']['consigner']['name'] = $order_complete->client_name . '';
    
            $pdf['params']['courier']['name'] = $order_complete->courier;
    
            $order_complete->logo_url != null ? $pdf['params']['merchant']['image_path'] = "https://s3-ap-southeast-1.amazonaws.com/static-pakde/$order_complete->logo_url" : null;
    
            $pdf['params']['order']['awb_number'] = [];
            $pdf['params']['order']['awb_number']['value'] = $order_complete->no_resi . '';
    
            $pdf['params']['order']['creation_date'] = date('d/m/Y');
    
            $pdf['params']['order']['order_id'] = [];
            $pdf['params']['order']['order_id']['value'] = $order_complete->order_number . '';
    
            $pdf['params']['order']['notes'] = $order_complete->notes . '';
    
            $pdf['params']['show_barcode']['barcode_1']['title'] = 'Order ID';
            $pdf['params']['show_barcode']['barcode_1']['type'] = 'orderID';
    
            $pdf['params']['show_barcode']['barcode_2']['title'] = 'AWB Number';
            $pdf['params']['show_barcode']['barcode_2']['type'] = 'awb';
    
            $pdf['params']['show_barcode']['barcode_type'] = 'code128';
    
            array_push($label_params, $pdf);
        }

        return $label_params;
    }

    public function generateLabel($labelParams) {
        $clientHttp = app(Client::class);

        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL');

            Log::info(json_encode($labelParams));

            $response = $clientHttp->request("POST", $url, ['body' => json_encode($labelParams)]);
            $response = json_decode($response->getBody()->getContents());

            return $response;
        } catch (\Exception $exception) {
            Log::error("Failed to call API to create label: ". $exception);
            throw $exception;
        }
    }
    
    public function approveMultiOutboundByCodes($codes_arr, $request)
    { 
        $responseMessage = 'Approval Failed';
        $responseData    = null;
        $responseCode    = '01';
        $count_approved  = 0;
        $count_failed    = 0;
        $approved_order_number = array();
        $raws      = array();
        $status_failed_reason = array();

        $shipmentApproved = new \stdClass;
        $shipmentApproved->data = new \stdClass;
        $shipmentApproved->data->order = new \stdClass;
        $shipmentApproved->data->order->order_ids = array();

        $pendingApproved = new \stdClass;
        $pendingApproved->order_ids = array();

        $jubelio = Config::get('constants.partners.jubelio');

        $httpClient = app(Client::class);

        $orderNumberID = [];

        foreach ($codes_arr as $code) {
            $isBreaking = false;
            // Get order detail data
            $datas = DB::table('orders')
                ->where('orders.order_number', '=', strtoupper($code))
                ->select(
                    'orders.id as orders_id',
                    'orders.order_number as orders_code',
                    'orders.status'
                )
                ->get();
            
            //the status berfore must be pending
            if (count($datas) > 0) {
                if(strcmp($datas[0]->status,Config::get('constants.pakde_order_status.PENDING')) != 0) {
                    $raws[$code]['status_reason'] = "Invalid Status For Order" . $code . ", Expect PENDING While Got " .$datas[0]->status. ".";
                    continue;
                }
            }

            foreach ($datas as $key => $data) {
                array_push($pendingApproved->order_ids, $data->orders_id);
                $orderNumberID[$data->orders_code] = $data->orders_id;
            }
        }

        $data = array();
        $data["data"]["order"] = $pendingApproved;
        $url = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_APPROVE_PENDING_ORDER'];
        try{ 
            $response = $httpClient->request("POST", $url, ['body' => json_encode($data)]);
            $result = json_decode($response->getBody(true));
            $approved_order_number = $result->data->order->order_numbers;

            $count_approved = count($approved_order_number);
            $count_failed = count($pendingApproved->order_ids) - $count_approved;

        } catch(\Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        foreach ($approved_order_number as $order_number) {
            // Call API to create invoice in Jubelio
            // $jubelio = Config::get('constants.partners.jubelio');
            // $url  = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_UPDATE_PICKLIST_STATUS'] . $jubelio['ID'] . "/" . DB::table('orders')->find($orderNumberID[$order_number])->client_id;
            // $body['order_id'] = $orderNumberID[$order_number];

            // try {
            //     $response = $httpClient->request("POST", $url, ['body' => json_encode($body)]);
            // } catch (\Exception $e) {
            //     Log::error("Failed to call API to update jubelio picklist status in omnichannelsvc: ". $e);
            // }

            array_push($shipmentApproved->data->order->order_ids, $orderNumberID[$order_number]);
        }
                
        //response container
        $response = new \stdClass;

        if (count($shipmentApproved->data->order->order_ids) > 0) {
            $awb_response = json_decode($this->saveMarketplaceAWB($shipmentApproved)->getBody());
            if ($awb_response->metadata->status_code != 200) {
                $response->message = $awb_response->metadata->message;
                $response->data     = null;
                $response->response_code    = strval($awb_response->metadata->status_code);
                return $response;
            }
        }

        $response->response_code = '200';
        $response->message = $responseMessage;
        $response->data = $responseData;
        $response->count_approved = $count_approved;
        $response->count_failed = $count_failed;
        $response->approved_order_number = $approved_order_number;

        return $response;
    }
    
    public function rewindPackingById(Request $request)
    {
        $responseMessage = 'Rewinding Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->id != null) {
            //DB::select("update inbound_detail_location idl INNER JOIN order_detail od ON idl.order_detail_id = od.id SET idl.order_detail_id = NULL,idl.date_ordered = NULL,idl.date_picked = NULL,idl.date_adjustment = NULL,idl.date_outbounded = NULL where od.orders_id = ".$input->id);
            
            DB::table('inbound_detail_location')
                ->join('order_detail', 'order_detail.id', '=', 'inbound_detail_location.order_detail_id')
                ->join('orders', 'orders.id', '=', 'order_detail.orders_id')
                ->where('orders.id', '=', $input->id)
                ->update([
                    'inbound_detail_location.updated_at'=> Carbon::now(),
                    'inbound_detail_location.order_detail_id' => null,
                    'inbound_detail_location.date_ordered'    => null,
                    'order_detail.inbound_detail_location_id' => null,
                    'inbound_detail_location.date_picked'     => null,
                    'inbound_detail_location.date_outbounded' => null
                ]);
            
            DB::table('order_detail')
                ->join('orders', 'orders.id', '=', 'order_detail.orders_id')
                ->where('orders.id', '=', $input->id)
                ->update([
                    'order_detail.inbound_detail_location_id' => null,
                    'order_detail.updated_at' => Carbon::now(),
                ]);
            
            
            DB::select("update orders set status = 'PENDING', picked_status = NULL, updated_at = '".Carbon::now()."' where id = " . $input->id);

            $hist = new OrderHistory;
            $hist->order_id = $input->id;
            $hist->status = 'PENDING';
            $hist->user_id = $request->user->id;
            $hist->save();

            $responseCode    = '00';
            $responseMessage = 'Rewinding Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function pushNotification($issue_id, $type_id, $msg_text)
    {
        DB::select("INSERT INTO notification(issue_id, type_id, msg_text) VALUES (" . $issue_id . "," . $type_id . ",'" . $msg_text . "')");
    }
    
    public function getNotification(Request $request)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select("select id,msg_text,issue_id,type_id,date, case when type_id = 1 then 'New Order' else 'Unknown' end as notification_type from notification order by date desc LIMIT 50");
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function updateWarehouseByEmail(Request $request)
    {
        $responseMessage = 'Update Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->warehouse_id != null && $input->email != null) {
            DB::select("update users set warehouse_id = " . $input->warehouse_id . ", updated_at = '".Carbon::now()."' where email = '" . $input->email . "'");
            $responseCode    = '00';
            $responseMessage = 'Update Success';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function finishingPackingShippingByCode(Request $request)
    {
        $responseMessage = 'Update Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->code != null) {
            $query_by_order_number = DB::table('orders')
                ->where('status', '=', 'AWAITING_FOR_SHIPMENT')
                ->where('order_number', '=', $input->code)
                ->select('*');
            $query_by_receipt_number = DB::table('orders')
                ->where('status', '=', 'AWAITING_FOR_SHIPMENT')
                ->where('no_resi', '=', $input->code)
                ->select('*');
            
            $datas = $query_by_order_number->get();
            if (count($datas) <= 0) {
                $datas = $query_by_receipt_number->get();
            }
            
            if (count($datas)) {
                $ord = Order::where('order_number', '=', $input->code)->first();
                if(is_null($ord)){
                    $ord = Order::where('no_resi', '=', $input->code)->first();
                }
                $ord->status = 'SHIPPED';
                $ord->shipment_date = date('Y-m-d H:i:s');
                $ord->shipper_id = $request->user->id;
                $ord->updated_at = Carbon::now();
                $ord->save();
                
                $hist = new OrderHistory;
                $hist->order_id = $ord->id;
                $hist->status = 'SHIPPED';
                $hist->user_id = $request->user->id;
                $hist->save();

                $responseCode    = '00';
                $responseMessage = 'Update Success';
                
                // Check if this is order from our partner then we do extra step
                // Call API to create invoice in Jubelio
                $jubelio = Config::get('constants.partners.jubelio');

                $client = app(Client::class);
                $url  = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_CREATE_INVOICE'] . $jubelio['ID'] . "/" . $ord->client_id;
                $body['order_id'] = $ord->id;

                try {
                    $response = $client->request("POST", $url, ['body' => json_encode($body)]);
                } catch (\Exception $exception) {
                    Log::error("Failed to call API to create invoice in omnichannelsvc: ". $exception);
                }
                
                $jorder = new \App\Http\Services\Jubelio\Order();
                $jorder->setOrderAsComplete($ord->order_number);
            } else {
                $responseMessage = 'This Order status is not in Shipment Awaits';
            }
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOrdersByStatusAndWarehouseId(Request $request)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        $sort            = "shipment_date";
        
        $status = (($request->input('status') != null) ? $request->input('status') : 0);
        
        if ($status == "AWAITING_FOR_SHIPMENT") {
            $sort = "packed_date";
        }
        
        $datas = DB::select("select id,order_number,packed_date,shipment_date,packer_id,(select name from users where id = ords.packer_id) as packer_name,shipper_id,(select name from users where id = ords.shipper_id) as shipper_name from orders as ords where status = '" . $status . "' order by " . $sort . " desc LIMIT 100");
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function getOrderChecking(Request $request)
    {
        $responseMessage  = 'Get Failed';
        $responseData     = null;
        $responseCode     = '01';
        $responseProducts = null;
        
        $code = (($request->input("code") != null) ? $request->input("code") : "");
        
        $datas = DB::select("select id,order_number,packed_date,shipment_date,packer_id,(select name from users where id = ords.packer_id) as packer_name,shipper_id,(select name from users where id = ords.shipper_id) as shipper_name,status,courier,no_resi,created_at,client_id,(select name from client where id = ords.client_id) as client_name,customer_id,(select name from customer where id = ords.customer_id) as customer_name,warehouse_id,(select name from warehouse where id = ords.warehouse_id) as warehouse_name,picked_status,client_pricing_order,client_pricing_qty,total,shipping_cost,notes from orders as ords where order_number = '" . $code . "'");
        
        $products = DB::select("SELECT inbound_detail_id,
                    (select name from inbound_detail where id = order_detail.inbound_detail_id) as inbound_detail_name ,
                    (select color from inbound_detail where id = order_detail.inbound_detail_id) as inbound_detail_color,
                    (SELECT name FROM product_type_size where id = (select product_type_size_id from inbound_detail where id = order_detail.inbound_detail_id)) as inbound_detail_size,
                    inbound_detail_location_id,
                    (select code from inbound_detail_location where id = order_detail.inbound_detail_location_id) as inbound_detail_location_code FROM orders inner join order_detail on orders.id = order_detail.orders_id 
                    where orders.order_number = '" . $code . "'");
        
        
        if (count($datas)) {
            $responseMessage  = 'Get Success';
            $responseData     = $datas;
            $responseProducts = $products;
            $responseCode     = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'products' => $responseProducts, 'data-request' => $request->getContent()]);
    }
    
    public function getProductChecking(Request $request)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $code = (($request->input("code") != null) ? $request->input("code") : "");
        
        $datas = DB::select("SELECT inbound_detail_location.id as id,inbound_detail_location.order_detail_id as order_detail_id,inbound_detail_location.code as code,product.name as inbound_name, inbound_detail.color as inbound_detail_color,(select name from product_type_size where id = inbound_detail.product_type_size_id) as size, (select code from shelf where id = inbound_detail_location.shelf_id) as shelf_name,(select order_number from orders where id = (select orders_id from order_detail where id = inbound_detail_location.order_detail_id)) as order_no,(select status from orders where id = (select orders_id from order_detail where id = inbound_detail_location.order_detail_id)) as order_status, inbound_detail.sku as inbound_detail_sku FROM inbound_detail_location inner join inbound_detail on inbound_detail.id = inbound_detail_location.inbound_detail_id inner join inbound on inbound.id = inbound_detail.inbound_id inner join product on product.id = inbound_detail.product_id where inbound_detail_location.code = '" . $code . "'");
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function fetchUsersCFU(Request $request)
    {
        $responseMessage = 'Fetch Failed';
        $responseData    = null;
        $responseCode    = '01';
        $input           = json_decode($request->getContent());
        
        if ($input->name != null) {
            $datas = DB::select("select id,name,(select name from client where id = users.cfu_id) as cfu_name from users where name LIKE '%" . $input->name . "%'");
            if (count($datas)) {
                $responseData    = $datas;
                $responseCode    = '00';
                $responseMessage = 'Fetch Success';
            }
        } else {
            $datas = DB::select("select id,name,(select name from client where id = users.cfu_id) as cfu_name from users");
            if (count($datas)) {
                $responseData    = $datas;
                $responseCode    = '00';
                $responseMessage = 'Fetch Success';
            }
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function fetchClients(Request $request)
    {
        $responseMessage = "Update Failed";
        $responseData    = null;
        $responseCode    = "01";
        $input           = json_decode($request->getContent());
        
        if ($input->name != null && $input->name != "") {
            $datas = DB::select("select * from client where name LIKE '%" . $input->name . "%'");
            if (count($datas)) {
                $responseData    = $datas;
                $responseCode    = '00';
                $responseMessage = 'Update Success';
            }
        } else {
            $datas = DB::select("select * from client");
            if (count($datas)) {
                $responseData    = $datas;
                $responseCode    = '00';
                $responseMessage = 'Update Success';
            }
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function updateUsersCFUIdByUserId(Request $request)
    {
        $responseMessage = 'Update Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $input = json_decode($request->getContent());
        
        if ($input->client_id != null && $input->user_id != null) {
            DB::select("update users set cfu_id = " . $input->client_id . ", updated_at = '".Carbon::now()."' where id = '" . $input->user_id . "'");
            $responseCode    = '00';
            $responseMessage = 'Update Success, Selected User need to Re-Login to takes the effect!';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function approveSingleOutboundByCode(Request $request)
    {
        $responseMessage = 'Approval Failed';
        $responseData = null;
        $responseCode = '01';
        $count_approved = 0;
        $count_failed = 0;

        $input = json_decode($request->getContent());

        if ($input->code != null) {
            $raws = array();
           
            $code = $input->code;
            $isBreaking = false;
            // Get order detail data
            $datas = DB::connection('read_replica')
                ->table('orders')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->where(DB::raw('LOWER(orders.order_number)'), '=', strtolower($code))
                ->select('orders.id as orders_id', 'orders.order_number as orders_code', 'order_detail.id as order_detail_id', 'inbound_detail.id as inbound_detail_id', 'inbound.product_id', 'product.name as product_name', 'inbound_detail.color as product_color', 'product_type_size.id as product_type_size_id', 'product_type_size.name as product_size', 'order_detail.inbound_detail_location_id')
                ->get();

            $failed = array();
            $raws[$code] = array('datas' => $datas, 'reason' => '');
            $prodArray = array();

            if (count($datas) > 0) {
                foreach ($datas as $key => $data) {
                    $datas[$key]->product_codes = DB::connection('read_replica')
                        ->table('inbound_detail_location')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                        ->where('inbound_detail.product_id', '=', $data->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $data->product_type_size_id)
                        ->whereNotNull('inbound_detail_location.shelf_id')
                        ->whereNull('inbound_detail_location.order_detail_id')
                        ->whereNull('inbound_detail_location.date_picked')
                        ->whereNull('inbound_detail_location.date_outbounded')
                        ->count();

                    $datas[$key]->product_uniques  = DB::connection('read_replica')
                        ->table('order_detail')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                        ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                        ->where('order_detail.id', '=', $data->order_detail_id)
                        ->where('inbound_detail.product_id', '=', $data->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $data->product_type_size_id)
                        ->count();

                    if ($datas[$key]->product_codes >= $datas[$key]->product_uniques) {
                        $tempQuery = DB::connection('read_replica')
                            ->table('inbound_detail_location')
                            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                            ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                            ->join('rack', 'rack.id', '=', 'shelf.rack_id')
                            ->join('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
                            ->whereNotNull('inbound_detail_location.shelf_id')
                            ->whereNull('inbound_detail_location.date_picked')
                            ->whereNull('inbound_detail_location.order_detail_id')
                            ->where('inbound_detail.product_id', '=', $data->product_id)
                            ->where('inbound_detail.product_type_size_id', '=', $data->product_type_size_id)
                            ->select('inbound_detail_location.id as inbound_detail_location_id', 'warehouse.id as warehouse_id');

                            // Take product if havent only available
                            if (count($prodArray) > 0) {
                                $tempQuery->whereNotIn('inbound_detail_location.id', array_column($prodArray, 'inbound_detail_location_id'));
                            }
                                
                            $inboundDetailLocation = $tempQuery->first();

                            // Another validation did the product is found or not
                            if ($inboundDetailLocation != null) {
                                $prodArray[] = array(
                                    "inbound_detail_location_id" => $inboundDetailLocation->inbound_detail_location_id,
                                    "order_detail_id"            => $data->order_detail_id,
                                    "warehouse_id"               => $inboundDetailLocation->warehouse_id
                                );
                            } else {
                                $isBreaking = true;
                                array_push($failed, $data->product_name);
                                $prodArray[] = array(
                                    "inbound_detail_location_id" => null,
                                    "order_detail_id"            => $data->order_detail_id,
                                    "warehouse_id"               => null
                                );
                            }
                    } else {
                        $isBreaking = true;
                        array_push($failed, $data->product_name);
                    }
                }
            }

            //DETERMINE IF APPROVED or NOT
            if (!$isBreaking) {
                foreach ($prodArray as $prod) {
                    DB::table('inbound_detail_location')
                        ->where('id', '=', $prod['inbound_detail_location_id'])
                        ->update([
                            'updated_at' => Carbon::now(),
                            'date_ordered' => date('Y-m-d H:i:s'),
                            'order_detail_id' => $prod['order_detail_id']
                        ]);

                    $order_detail = OrderDetail::find($prod['order_detail_id']);
                    $order_detail->inbound_detail_location_id = $prod['inbound_detail_location_id'];
                    $order_detail->warehouse_id = $prod['warehouse_id'];
                    $order_detail->updated_at = Carbon::now();
                    $order_detail->save();
                }

                $ord = Order::where('order_number', '=', $input->code)->first();
                $ord->updated_at = Carbon::now();
                $ord->status = 'READY_FOR_OUTBOUND';
                $ord->save();

                $hist = new OrderHistory;
                $hist->order_id = $ord->id;
                $hist->status = 'READY_FOR_OUTBOUND';
                $hist->user_id = $request->user->id;
                $hist->save();

                $count_approved++;

                // Check if this is order from our partner then we do extra step

                // Call API to create invoice in Jubelio
                $jubelio = Config::get('constants.partners.jubelio');
                $client = app(Client::class);
                $url  = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_UPDATE_PICKLIST_STATUS'] . $jubelio['ID'] . "/" . $ord->client_id;
                $body['order_id']   = $ord->id;

                try {
                    $response = $client->request("POST", $url, ['body' => json_encode($body)]);
                } catch (\Exception $exception) {
                    Log::error("Failed to call API to update jubelio picklist status in omnichannelsvc: ". $exception);
                }
            } else {
                $count_failed++;
                $raws[$code]['reason'] = "Stock is not available for ".implode(", ", $failed).".";
            }
        
            $responseCode = '00';
            $responseMessage = "Orders Approved: ".$count_approved.", Orders Failed to Approve: ".$count_failed;
        }

        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent(), 'raw-result' =>  $raws]);
    }

    public function getOutboundPaging(Request $request, $start, $limit)
    {
        $responseMessage = 'Get Outbound Ready Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = Order::where('status', 'READY_FOR_OUTBOUND')->whereNull('picked_status')->offset($start)->limit($limit)->orderBy('created_at', 'DESC')->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Outbound Ready Success';
            $responseData    = $datas;
            $responseCode    = '00';
        } else {
            $responseMessage = 'No Outbound Ready';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getInboundPaging(Request $request, $start, $limit)
    {
        $responseMessage = 'Get Inbound Batch List Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('inbound_batch')
            ->join('client', 'client.id', '=', 'inbound_batch.client_id')
            ->select('inbound_batch.id as batch_id', 'client.name as client_name', 'inbound_batch.arrival_date', 'inbound_batch.status', 'inbound_batch.is_done', 'inbound_batch.inbound_partner_id')
            ->where('inbound_batch.is_done', '=', 0)
            ->offset($start)
            ->limit($limit)
            ->orderBy('inbound_batch.id', 'desc')
            ->get();
        
        if (count($datas)) {
            foreach ($datas as $key => $val) {
                $datas[$key]->pretty_batch_id = '#' . str_pad($datas[$key]->batch_id, 5, '0', STR_PAD_LEFT);
                $total = DB::table('inbound_detail_location')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
                    ->where('inbound_batch.id', '=', $datas[$key]->batch_id)
                    ->count();
                $completed                    = DB::table('inbound_detail_location')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
                    ->where('inbound_batch.id', '=', $datas[$key]->batch_id)
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->count();
                $datas[$key]->count = $completed . " / " . $total;
            }
            
            $responseMessage = 'Get Inbound Batch List Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getPackingPaging(Request $request, $start, $limit)
    {
        $responseMessage = 'Get Pack Ready Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::table('orders')
            ->where('status', 'READY_TO_PACK')
            ->whereNull('packed_date')
            ->offset($start)
            ->limit($limit)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        if (count($datas)) {
            $responseMessage = 'Get Pack Ready Success';
            $responseData    = $datas;
            $responseCode    = '00';
        } else {
            $responseMessage = 'No Pack Ready';
        }
        
        return response()
            ->json([
                'code' => $responseCode,
                'message' => $responseMessage,
                'data' => $responseData,
                'data-request' => $request->getContent()
            ]);
    }

    public function getShippingPaging(Request $request, $start, $limit)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        $sort            = "shipment_date";
        
        $status = (($request->input('status') != null) ? $request->input('status') : 0);
        
        if ($status == "AWAITING_FOR_SHIPMENT") {
            $sort = "packed_date";
        }
        
        $datas = DB::select("select id,order_number,packed_date,shipment_date,packer_id,(select name from users where id = ords.packer_id) as packer_name,shipper_id,(select name from users where id = ords.shipper_id) as shipper_name from orders as ords where status = '" . $status . "' order by " . $sort . " desc  LIMIT ".$limit." OFFSET ".$start);
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getShelfPagingByWarehouseId(Request $request, $id, $start, $limit)
    {
        $responseMessage = 'Get Failed';
        $responseData    = null;
        $responseCode    = '01';
        
        $datas = DB::select("SELECT shelf.id,shelf.code,shelf.col,shelf.row,shelf.name,rack.warehouse_id,shelf.date_opnamed,(SELECT count(id) FROM inbound_detail_location where shelf_id = shelf.id and date_outbounded is null and date_picked is null) as items FROM rack INNER JOIN shelf ON rack.id = shelf.rack_id where warehouse_id = " . $id . " order by shelf.date_opnamed desc LIMIT ".$limit." OFFSET ".$start);
        
        if (count($datas)) {
            $responseMessage = 'Get Success';
            $responseData    = $datas;
            $responseCode    = '00';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function billInbound(Request $request) 
    {
        $response = null;
        $httpClient = app(Client::class);
        $jubelio = Config::get('constants.partners.jubelio');
        $url = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_BILL_PURCHASEORDER'];
        $input = json_decode($request->getContent());

        $bodyReq = new \stdClass;
        $bodyReq->data = [];

        foreach ($input->data as $scannedItem) {
            $scannedItemReq = new \stdClass;
            $scannedItemReq->id = $scannedItem->id;
            $scannedItemReq->date_rejected = $scannedItem->date_rejected;
            $scannedItemReq->shelf_name = $scannedItem->shelf_name;

            array_push($bodyReq->data, $scannedItemReq);
        }

        try {
            $response = $httpClient->request("POST", $url, ['body' => json_encode($bodyReq)]);
        } catch (\Exception $exception) {
            $response =  $exception->getResponse()->getBody(true);
            return response()->json(json_decode($response));
        }

        return response()->json(json_decode($response->getBody(), true));
    }

    public function adjustStock(Request $request)
    {
        $response = null;
        $httpClient = app(Client::class);
        $jubelio = Config::get('constants.partners.jubelio');
        $url = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_STOCK_ADJUSTMENT'];
        $input = json_decode($request->getContent());

        $bodyReq = new \stdClass;
        $bodyReq->data = [];

        if ($input->ids != null) {
            $ids_arr = explode("|", $input->ids);
            foreach ($ids_arr as $id) {
                $scannedItemReq = new \stdClass;
                $scannedItemReq->id = $id;

                array_push($bodyReq->data, $scannedItemReq);
            }
        }

        try {
            $response = $httpClient->request("POST", $url, ['body' => json_encode($bodyReq)]);
        } catch (\Exception $exception) {
            $response =  $exception->getResponse();
            return $response;
        }

        return $response;
    }

    public function saveMarketplaceAWB($shipmentApproved) {
        $response = null;
        $httpClient = app(Client::class);
        $jubelio = Config::get('constants.partners.jubelio');
        $url = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_SAVE_MARKET_PLACE_AWB'];

        try{
            $response = $httpClient->request("PATCH", $url, ['body' => json_encode($shipmentApproved)]);
        } catch(\Exception $exception) {
            $response = $exception->getResponse();
        }
        
        return $response;
    }
}