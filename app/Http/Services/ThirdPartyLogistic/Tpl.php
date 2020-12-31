<?php

namespace App\Http\Services\ThirdPartyLogistic;

use App\Jobs\SyncSingleTplHistory;
use App\Jobs\SyncTplHistory;
use App\Order;

class Tpl
{
    public static function synchronize($order_id)
    {
        if ($order = Order::where('id', $order_id)->where('no_resi', '!=', '')->where('no_resi', '!=', '0')->first()) {
            SyncSingleTplHistory::dispatch($order);
        }
        
        return;
    }
    
    public static function synchronizeAll()
    {
        $result = null;
        
        if ($orders = Order::where('no_resi', '!=', '')->where('no_resi', '!=', '0')->where('courier', 'SICEPAT BEST-B')->whereNotIn('status', ['CANCELED', 'SHIPPED', 'PENDING'])->whereRaw('LENGTH(no_resi) > 7')->orderByDesc('id')->get()) {
            SyncTplHistory::dispatch($orders);
        }
        
        return $result;
    }
}
