<?php

namespace App\Http\Services\ThirdPartyLogistic;

use App\Customer;
use App\Order;
use App\OrderTrackingHistory;
use App\Warehouse;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class Jne
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
            $body['courier']         = 'jne';
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
                            $history->courier         = "jne";
                            
                            $history->notes       = $detail->manifest_description;
                            $history->tracking_at = $detail->manifest_date . " " . $detail->manifest_time;
                            
                            $histories[] = $history;
                        }
                    }
                    
                    if ($data->rajaongkir->result->delivery_status->status == 'DELIVERED') {
                        $history = new OrderTrackingHistory();
                        
                        $history->order_id        = $order->id;
                        $history->tracking_number = $order->no_resi;
                        $history->courier         = "jne";
                        
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
    
    public function setJOB($order_number)
    {
        $book_code = null;
        $url = config('pakde.jne_gentiket_url');

        if (empty($url))
            return $book_code;
        
        if ($order = Order::where('order_number', $order_number)->whereNull('job_jne')->first()) {
            if (str_contains(strtolower($order->courier), 'jne')) {
                $order_number_splited = substr($order_number, -12);
                $book_code            = str_pad($order_number_splited, 12, "0", STR_PAD_LEFT);
                $book_code            = "PKD-" . $book_code;
                
                $warehouse_id = $order->warehouse_id ?: 1;
                
                $warehouse = new Warehouse();
                $customer  = new Customer();
                $w         = $warehouse->find($warehouse_id);
                $c         = $customer->find($order->customer_id);
                
                $client = app(Client::class);
                
                $body['username']  = config('pakde.jne_api_username');
                $body['api_key']   = config('pakde.jne_api_key');
                $body['BOOK_CODE'] = $book_code;
                
                $body['SHIPPER_NAME']    = "clientname.co.id";
                $body['SHIPPER_ADDR1']   = substr($w->address, 0, 30);
                $body['SHIPPER_ADDR2']   = substr($w->address, 30, 60);
                $body['SHIPPER_ADDR3']   = substr($w->address, 60, 90);
                $body['SHIPPER_CITY']    = 'Jakarta';
                $body['SHIPPER_ZIP']     = $w->zip_code;
                $body['SHIPPER_REGION']  = '-';
                $body['SHIPPER_COUNTRY'] = 'INDONESIA';
                $body['SHIPPER_CONTACT'] = 'Steffan Daniel';
                $body['SHIPPER_PHONE']   = '-';
                
                $body['RECEIVER_NAME']    = substr($c->name, 0, 15);
                $body['RECEIVER_ADDR1']   = substr($c->address, 0, 30);
                $body['RECEIVER_ADDR2']   = substr($c->address, 30, 60);
                $body['RECEIVER_ADDR3']   = substr($c->address, 60, 90);
                $body['RECEIVER_CITY']    = '-';
                $body['RECEIVER_ZIP']     = $c->zip_code ?: '-';
                $body['RECEIVER_REGION']  = '-';
                $body['RECEIVER_COUNTRY'] = 'INDONESIA';
                $body['RECEIVER_CONTACT'] = substr($c->name, 0, 15);
                $body['RECEIVER_PHONE']   = $c->phone ?: '-';
                $body['ORIGIN_DESC']      = '-';
                $body['ORIGIN_CODE']      = '-';
                $body['DESTINATION_DESC'] = '-';
                $body['DESTINATION_CODE'] = '-';
                $body['SERVICE_CODE']     = 'REG';
                $body['WEIGHT']           = '1';
                $body['QTY']              = '1';
                $body['GOODS_DESC']       = 'Barang Pakde';
                $body['DELIVERY_PRICE']   = '0';

                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $headers['Accept']       = 'application/json';
                
                try {
                    $response = $client->request("POST", $url, ['form_params' => $body, 'headers' => $headers]);
                    
                    if ($response->getStatusCode() == 200) {
                        $content = $response->getBody()->getContents();
                        $data    = json_decode($content);
                        
                        if (isset($data->status) && $data->status == 'Sukses') {
                            $book_code = $data->no_tiket;
                        }
                        
                        DB::select("update orders set job_jne = '" . $book_code . "' where order_number = '" . $order_number . "'");
                    }
                }
                catch (\Exception $e) {
                }
            }
        }
        
        return $book_code;
    }
}
