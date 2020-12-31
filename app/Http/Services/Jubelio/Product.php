<?php

namespace App\Http\Services\Jubelio;

use App\Customer;
use App\JubelioOrder;
use App\JubelioRawProduct;
use App\JubelioSyncProduct;
use App\Order;
use App\OrderDetail;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Product
{
    private $client;
    
    private $url;
    
    public function __construct()
    {
        $this->client = new Client();
    }
    
    public function getItem($client_id, $item_id)
    {
        $auth      = new JAuth();
        $this->url = 'http://api.jubelio.com/inventory/items/';
        $token     = $auth->login($client_id);
        
        if ($item_id) {
            $headers['Content-Type']  = 'application/x-www-form-urlencoded';
            $headers['authorization'] = $token;
            
            $response = $this->client->request("GET", $this->url . $item_id, ['headers' => $headers]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $item    = json_decode($content);
                if ($item->item_category_id != 0) {
                    $item->category = $this->getCategory($client_id, $item->item_category_id);
                } else {
                    $item->category                = new \stdClass();
                    $item->category->category_name = 'default';
                }
                
                return $item;
            }
        }
        
        return null;
    }
    
    public function getCategory($client_id, $category_id)
    {
        $auth      = new JAuth();
        $this->url = 'http://api.jubelio.com/inventory/categories/item-categories/information/';
        $token     = $auth->login($client_id);
        
        if ($category_id && $token) {
            $headers['Content-Type']  = 'application/x-www-form-urlencoded';
            $headers['authorization'] = $token;
            
            $response = $this->client->request("GET", $this->url . "$category_id/", ['headers' => $headers]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $data    = json_decode($content);
                
                if (is_array($data)) {
                    $category = $data[0];
                }
                
                return $category;
            }
        }
        
        return null;
    }
    
    public function synchronizeSalesOrder($sales_order, $client_id)
    {
        $jo                = new JubelioOrder();
        $jo->salesorder_id = $sales_order->salesorder_id;
        $jo->client_id     = $sales_order->client_id;
        $isSkip            = $jo->saveOrSkip();
//        $isSkip      = false;
        $order_items = [];
//        $this->updateStockToJubelio("VISVAL/DEFAULT/OFRADRESS(DEFAULT)/DEFAULT/275", $client_id, -1);
        
        if (!$isSkip) {
            $skus = [];
            foreach ($sales_order->items as $soi) {
                $order_items[$soi->item_code] = $soi->qty_in_base;
                
                foreach ($soi->item->product_skus as $key => $product_sku) {
                    $soi->item->product_skus[$key]->item_category_name = $soi->item->category->category_name;
                    $soi->item->product_skus[$key]->item_group_name    = $soi->item->item_group_name;
                    $soi->item->product_skus[$key]->weight             = $soi->item->package_weight / 1000;
                    $soi->item->product_skus[$key]->description        = $soi->item->description;
                    $soi->item->product_skus[$key]->notes              = $soi->item->notes;
                    $soi->item->product_skus[$key]->item_group_id      = $soi->item->item_group_id;
                }
                
                $skus = array_merge($skus, $soi->item->product_skus);
            }
            
            $skus = collect($skus);
            $skus = $skus->keyBy('item_id')->values();
            
            $synchedSku = $this->synchronizeSku($skus, $client_id);
            $synchedSku = collect($synchedSku);
            $details    = [];
            
            foreach ($order_items as $sku => $qty_needed) {
                if ($detail = $synchedSku->where('product.jubelio_variant_item_code', $sku)->first()) {
                    $detail['product']->qty_needed = (int) $qty_needed;
                    $details[]                     = $detail;
                }
            }
            
            $this->createOrder($details, $sales_order, $client_id);
        }
    }
    
    /**
     * @param Collection $jubelio_skus
     * @param            $client_id
     * @return array|bool
     */
    public function synchronizeSku(Collection $jubelio_skus, $client_id)
    {
        $details = [];
        
        foreach ($jubelio_skus as $sku) {
            $jubelio_raw_product                     = new JubelioRawProduct();
            $jubelio_raw_product->client_id          = $client_id;
            $jubelio_raw_product->item_category_name = $sku->item_category_name;
            $jubelio_raw_product->item_group_name    = $sku->item_group_name;
            $jubelio_raw_product->weight             = $sku->weight;
            $jubelio_raw_product->item_id            = $sku->item_id;
            $jubelio_raw_product->item_group_id      = $sku->item_group_id;
            $jubelio_raw_product->item_group_name    = $sku->item_group_name;
            $jubelio_raw_product->description        = $sku->description;
            $jubelio_raw_product->notes              = $sku->notes;
            
            $jubelio_raw_product->variant_item_code = $sku->item_code;
            $jubelio_raw_product->variant_barcode   = $sku->barcode ?: '';
            
            foreach ($sku->variation_values as $variant) {
                if ($variant->label == 'Ukuran' || $variant->label == 'Size') {
                    $jubelio_raw_product->variant_ukuran = $variant->value;
                } else if ($variant->label == 'Warna' || $variant->label == 'Colour') {
                    $jubelio_raw_product->variant_warna = $variant->value;
                }
            }
            
            if ($jubelio_raw_product->variant_warna == null) {
                $jubelio_raw_product->variant_warna = 'default';
            }
            
            $detail = $jubelio_raw_product->sync();
            
            $details[] = $detail;
        }
        
        return $details;
    }
    
    private function createOrder($details, $sales_order, $client_id)
    {
        $order  = new Order();
        $client = new \App\Client();
        $c      = $client->where('id', $client_id)->first();
        
        $order->version      = 1;
        $ocount              = count(DB::select("SELECT * FROM `orders` WHERE DATE(created_at) = '" . date('Y-m-d') . "'"));
        $order_number        = $c->acronym . date('Ymd') . str_pad($ocount + 1, 6, '0', STR_PAD_LEFT);
        $order->order_number = $order_number;
        $order->order_type   = 'NEW';
        
        $order->client_pricing_order = 0;
        $order->client_pricing_qty   = 0;
        $order->code                 = 'ORD' . str_pad(mt_rand(intval(1000000000), intval(9999999999)), 10, "0", STR_PAD_LEFT);
        $order->status               = 'PENDING';
        $order->shipping_cost        = 0;
        $order->total                = $sales_order->grand_total;
        $order->notes                = "ORDER JUBELIO : " . $sales_order->salesorder_no;
        
        $ncustomer           = new Customer;
        $ncustomer->version  = 1;
        $ncustomer->address  = $sales_order->shipping_address . ", " . $sales_order->shipping_area . ", " . $sales_order->shipping_city . ", " . $sales_order->shipping_province . ", " . $sales_order->shipping_country;
        $ncustomer->name     = $sales_order->shipping_full_name;
        $ncustomer->phone    = $sales_order->shipping_phone ?: '-';
        $ncustomer->zip_code = $sales_order->shipping_post_code ?: '-';
        $ncustomer->save();
        
        $order->customer_id = $ncustomer->id;
        $order->client_id   = $c->id;
        $order->save();
        
        // Check if there's a copied order number
        while (count(DB::select("SELECT * FROM `orders` WHERE `order_number` = '" . $order_number . "'")) > 1) {
            $ocount              = count(DB::select("SELECT * FROM `orders` WHERE DATE(created_at) = '" . date('Y-m-d') . "'"));
            $order_number        = $client->acronym . date('Ymd') . str_pad($ocount + 1, 6, '0', STR_PAD_LEFT);
            $order->order_number = $order_number;
            $order->save();
        }
        
        DB::select("update jubelio_order set order_number = '" . $order->order_number . "' where salesorder_id = '" . $sales_order->salesorder_id . "'");
        
        // Create order detail
        foreach ($details as $key => $detail) {
            $product = $detail['product'];
            
            for ($x = 0; $x < $product->qty_needed; $x++) {
                $order_detail                    = new OrderDetail;
                $order_detail->version           = 0;
                $order_detail->orders_id         = $order->id;
                $order_detail->inbound_detail_id = $product->inbound_detail->id;
                $order_detail->save();
            }
        }
    }
    
    public function updateStockToJubelio($pakde_inbound_detail_sku, $client_id, $quantity_adjustment, $quantity_final = -1)
    {
        $jauth  = new JAuth();
        $jtoken = $jauth->login($client_id);
        
        $url     = 'http://api.jubelio.com/inventory/adjustments/';
        $item_id = null;
        
        $jraw  = new JubelioRawProduct();
        $jsync = new JubelioSyncProduct();
        if ($js = $jsync->where('pakde_inbound_detail_sku', $pakde_inbound_detail_sku)->first()) {
            if ($jr = $jraw->where('variant_item_code', $js->jubelio_variant_item_code)->first()) {
                $item_id = $jr->item_id;
            }
        }
        
        if ($item_id && $jtoken) {
            $headers['authorization'] = $jtoken;
            
            $body['item_adj_id']        = 0;
            $body['item_adj_no']        = "[auto]";
            $body['location_id']        = -1;
            $body['is_opening_balance'] = false;
            $body['transaction_date']   = Carbon::now()->format('Y-m-d\TH:i:s.u\Z');
            
            $items         = array(
                [
                    'qty_in_base' => (int) $quantity_adjustment,
                    'account_id'  => 75,
                    'item_id'     => $item_id,
                    'cost'        => 0,
                    'amount'      => 0,
                    'location_id' => -1,
                    'unit'        => "Buah",
                    'description' => "Penyesuaian stock $pakde_inbound_detail_sku (sku clientname.co.id). Trigger: Complete order",
                ]
            );
            $body['items'] = $items;
            
            $headers['Content-Type'] = 'application/json';
            $response                = $this->client->post($url, ['headers' => $headers, 'body' => json_encode($body)]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $data    = json_decode($content);
                
                if ($data->status == "ok") {
                    return $data->id;
                }
                
                return false;
            }
        }
    }
    
    public function synchronizeStockToJubelio($jubelio_sku, $pakde_inbound_detail_sku, $client_id, $quantity_final = 0)
    {
        if ($jubelio_item = $this->getItemBySku($client_id, $jubelio_sku)) {
            foreach ($jubelio_item->product_skus as $key => $product_sku) {
                if ($jubelio_sku == $product_sku->item_code) {
                    $quantity_actual_jubelio = (int) $product_sku->end_qty;
                }
            }
        }
        
        if ($quantity_final == 0) {
            return;
        } else if ($quantity_final > $quantity_actual_jubelio) {
            $quantity_adjustment = $quantity_final - $quantity_actual_jubelio;
        } else if ($quantity_final < $quantity_actual_jubelio) {
            $quantity_adjustment = $quantity_actual_jubelio - $quantity_final;
            $quantity_adjustment = -1 * $quantity_adjustment;
        } else {
            return;
        }
        
        $jauth   = new JAuth();
        $jtoken  = $jauth->login($client_id);
        $url     = 'http://api.jubelio.com/inventory/adjustments/';
        $item_id = null;
        
        $jraw  = new JubelioRawProduct();
        $jsync = new JubelioSyncProduct();
        if ($js = $jsync->where('pakde_inbound_detail_sku', $pakde_inbound_detail_sku)->first()) {
            if ($jr = $jraw->where('variant_item_code', $js->jubelio_variant_item_code)->first()) {
                $item_id = $jr->item_id;
            }
        }
        
        if ($item_id && $jtoken) {
            $headers['authorization'] = $jtoken;
            
            $body['item_adj_id']        = 0;
            $body['item_adj_no']        = "[auto]";
            $body['location_id']        = -1;
            $body['is_opening_balance'] = false;
            $body['transaction_date']   = Carbon::now()->format('Y-m-d\TH:i:s.u\Z');
            
            $items = array(
                [
                    'qty_in_base' => (int) $quantity_adjustment,
                    'account_id'  => 75,
                    'item_id'     => (int) $item_id,
                    'cost'        => 0,
                    'amount'      => 0,
                    'location_id' => -1,
                    'unit'        => "Buah",
                    'description' => "Penyesuaian stock $pakde_inbound_detail_sku (sku clientname.co.id). Trigger: Cron",
                ]
            );
            
            $body['items'] = $items;
            $headers['Content-Type'] = 'application/json';
            $response                = $this->client->post($url, ['headers' => $headers, 'body' => json_encode($body)]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $data    = json_decode($content);
                
                if ($data->status == "ok") {
                    return $data->id;
                }
                
                return false;
            }
        }
    }
    
    public function getItemBySku($client_id, $jubelio_sku)
    {
        $auth  = new JAuth();
        $url   = 'http://api.jubelio.com/inventory/items/by-sku/';
        $token = $auth->login($client_id);
        
        if ($token) {
            $headers['Content-Type']  = 'application/x-www-form-urlencoded';
            $headers['authorization'] = $token;
            
            $response = $this->client->request("GET", $url . $jubelio_sku, ['headers' => $headers]);
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $item    = json_decode($content);
                if ($item->item_category_id != 0) {
                    $item->category = $this->getCategory($client_id, $item->item_category_id);
                } else {
                    $item->category                = new \stdClass();
                    $item->category->category_name = 'default';
                }
                
                return $item;
            }
        }
        
        return null;
    }
}
