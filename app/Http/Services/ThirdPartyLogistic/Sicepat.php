<?php

namespace App\Http\Services\ThirdPartyLogistic;

use App\Order;
use App\OrderTrackingHistory;
use GuzzleHttp\Client;

class Sicepat
{
    /**
     *  Please run this function asynchronously
     *
     * @param $order_id
     */
    public function synchronizeHistory($order_id)
    {
        if ($histories = $this->getHistory($order_id)) {
            OrderTrackingHistory::where('order_id', $order_id)->delete();
            
            foreach ($histories as $history) {
                $history->save();
            }
        }
    }
    
    public function getHistory($order_id)
    {
        $histories = [];
        
        if ($order = Order::where('id', $order_id)->where('no_resi', '!=', '')->where('no_resi', '!=', '0')->first()) {
            $client = app(Client::class);
            
            $url                     = "https://pro.rajaongkir.com/api/waybill";
            $body['waybill']         = $order->no_resi;
            $body['courier']         = 'sicepat';
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $headers['key']          = '2b9e1b732996a732309b659cc03900cb';
            
            $response = $client->request("POST", $url, ['form_params' => $body, 'headers' => $headers]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $data    = json_decode($content);
                if ($data->rajaongkir->status->code == 200) {
                    $details = $data->rajaongkir->result->manifest;
                    
                    if(isset($details)){
                        foreach ($details as $detail) {
                            $history                  = new OrderTrackingHistory();
                            $history->order_id        = $order->id;
                            $history->tracking_number = $order->no_resi;
                            $history->courier         = "sicepat";
                            
                            $history->notes       = $detail->manifest_description;
                            $history->tracking_at = $detail->manifest_date . " " . $detail->manifest_time;
                            
                            $histories[] = $history;
                        }
                    }
                    
                    if ($data->rajaongkir->result->delivery_status->status == 'DELIVERED') {
                        $history = new OrderTrackingHistory();
                        
                        $history->order_id        = $order->id;
                        $history->tracking_number = $order->no_resi;
                        $history->courier         = "sicepat";
                        
                        $history->notes       = "Sudah diterima, penerima adalah: " . $data->rajaongkir->result->delivery_status->pod_receiver;
                        $history->tracking_at = $data->rajaongkir->result->delivery_status->pod_date . " " . $data->rajaongkir->result->delivery_status->pod_time;
                        
                        $histories[] = $history;
                    }
                    
                    $histories = collect($histories);
                    $histories = $histories->sortBy('tracking_at')->values();
                }
            }
        }
        
        return $histories;
    }
}
