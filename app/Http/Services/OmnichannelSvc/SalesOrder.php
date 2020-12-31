<?php

namespace App\Http\Services\OmnichannelSvc;

use GuzzleHttp\Client;
use Config;
use Auth;
use Log;

class SalesOrder
{
    private $client;
    
    private $base_url;
    
    public function __construct()
    {
        $this->http_client = app(Client::class);
        $this->base_url = env('OMNICHANNELSVC_BASE_URL');
    }
    
    public function patchUpdateAirwaybilltoPartner($partner_name, $client_id, $order_id, $courier_name, $airwaybill_num)
    {
        //Make URL by partner ID and client ID
        $omnichannel = Config::get('constants.internal_services.omnichannel');
        $partner = Config::get('constants.partners');

        //Make URL by partner ID and client ID
        $url = $this->base_url . $omnichannel['URL_SALESORDER_UPDATE_AWB'] . $partner[$partner_name]['ID'] . '/' . $client_id;
        
        //Make body request
        $body_request = new \stdClass;
        $body_request->order_id = $order_id;
        $body_request->courier_name = $courier_name;
        $body_request->airwaybill_num = $airwaybill_num;

        try {
            $response = $this->http_client->request("PATCH", $url, ['body' => json_encode($body_request)]);
        } catch (\Exception $exception) {
            $response = $exception->getResponse();

            if ($response->getStatusCode() == 404) {
                return null;
            }
            Log::error("Failed to call update airwaybill order to omnichannelsvc: ". $exception);
            Log::error($response->getBody()->getContents());
        }

        return response()->json(json_decode($response->getBody()->getContents()), $response->getStatusCode());
    }
    
    public function patchBulkUpdateAirwaybilltoPartner($partner_name, $client_id, $bulkDataRequest)
    {
        //Make URL by partner ID and client ID
        $omnichannel = Config::get('constants.internal_services.omnichannel');
        $partner = Config::get('constants.partners');

        //Make URL by partner ID and client ID
        $url = $this->base_url . sprintf($omnichannel['URL_SALESORDER_BULK_UPDATE_AWB'], $partner[$partner_name]['ID'], $client_id);
        
        //Make body request
        $body_request = new \stdClass;
        $body_request->data = $bulkDataRequest;
        try {
            $response = $this->http_client->request("PATCH", $url, ['body' => json_encode($body_request)]);
        } catch (\Exception $exception) {
            $response = $exception->getResponse(); 
            Log::error("Failed to call update airwaybill order to omnichannelsvc: ". $exception);exit;
            Log::error($response->getBody()->getContents());
        }

        return response()->json(json_decode($response->getBody()->getContents()), $response->getStatusCode());
    }
    
}
