<?php

namespace App\Http\Controllers\Callback;

use App\Http\Controllers\Controller;
use App\Http\Services\Jubelio\JAuth;
use App\Http\Services\Jubelio\Order;
use App\Http\Services\Jubelio\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JubelioController extends Controller
{
    
    public function __construct()
    {
    }
    
    public function postSubmitProduct(Request $request, Product $product)
    {
        Log::info("Callback POST submitProduct Start");
        Log::info("Request : " . print_r($request->all(), true));
        Log::info("Callback POST submitProduct End");

//        $product->getItemGroup($request->input('item_group_id'));
        
        return ['message' => 'success', 'status' => 'ok'];
    }
    
    public function postSubmitOrder(Request $request, Order $order, JAuth $jauth)
    {
        Log::info("Callback POST submitProduct Start");
        Log::info("Request : " . print_r($request->all(), true));
    
        if ($request->input('action') == 'update-salesorder' && $request->input('status') == 'Paid' && $request->input('salesorder_id') && $request->input('pat')) {
            if ($remote_order = $order->getOrder($request->input('pat'), $request->input('salesorder_id'))) {
                $p = new Product();
                $p->synchronizeSalesOrder($remote_order, $jauth->getClientIDByPat($request->input('pat')));
    
                return ['message' => 'success', 'status' => 'ok'];
            }
        }
    
        Log::info("Callback POST submitProduct End");
        
        return ['message' => 'request not valid', 'status' => 'failed'];
    }
}
