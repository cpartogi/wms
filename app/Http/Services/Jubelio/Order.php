<?php

namespace App\Http\Services\Jubelio;

use App\JubelioOrder;
use GuzzleHttp\Client;

class Order
{
    private $client;
    
    private $url;
    
    public function __construct()
    {
        $this->client = new Client();
        $this->url    = 'http://api.jubelio.com/sales/orders/';
    }
    
    public function getOrder($pakde_auth_token, $sales_order_id)
    {
        $auth      = new JAuth();
        $token     = $auth->getJTokenByPat($pakde_auth_token);
        $client_id = $auth->getClientIDByPat($pakde_auth_token);
        
        if ($token && $sales_order_id) {
            $headers['Content-Type']  = 'application/x-www-form-urlencoded';
            $headers['authorization'] = $token;
            
            $response = $this->client->request("GET", $this->url . $sales_order_id, ['headers' => $headers]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $data    = json_decode($content);
                if (isset($data->items) && count($data->items) > 0) {
                    foreach ($data->items as $key => $so_item) {
                        $product                 = new Product();
                        $data->items[$key]->item = $product->getItem($client_id, $so_item->item_id);
                    }
                }
                
                $data->client_id = $client_id;
                
                return $data;
            }
        }
        
        return null;
    }
    
    public function setOrderAsComplete($order_number)
    {
        $jauth  = new JAuth();
        $jorder = new JubelioOrder();
        $jo = $jorder->where('order_number', $order_number)->first();

        if(isset($jo)){
            $token = $jauth->login($jo->client_id);
            if ($token && $order_number) {
                
                $body['ids'] = array($jo->salesorder_id);
                
                $headers['Content-Type'] = 'application/json';
                $response = $this->client->post($this->url, ['headers' => $headers, 'body' => json_encode($body)]);
                
                if ($response->getStatusCode() == 200) {
                    $content = $response->getBody()->getContents();
                    $data    = json_decode($content);
                    
                    return $data->status;
                }
            }
        }
        
        return null;
    }
}
