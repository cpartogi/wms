<?php

namespace App\Http\Controllers\Callback;

use App\Http\Controllers\Controller;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JNEController extends Controller
{
    
    public function __construct()
    {
    }
    
    public function postJobBooked(Request $request)
    {
        $reason = 'request not valid!';
        
        Log::info("Callback POST submitProduct Start");
        Log::info("Request : " . print_r($request->all(), true));
        
        if ($request->input('job') && $request->input('awb')) {
            if ($o = Order::where('job_jne', $request->input('job'))->first()) {
                Order::where('job_jne', $request->input('job'))->update(['no_resi' => $request->input('awb')]);
                
                return ['status' => true];
            }
            
            $reason = 'Job not found!';
        }
        
        Log::info("Callback POST submitProduct End");
        
        return ['reason' => $reason, 'status' => true];
    }
}
