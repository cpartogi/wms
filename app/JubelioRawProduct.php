<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JubelioRawProduct extends Model
{
    protected $table = 'jubelio_raw_product';
    
    public function sync()
    {
        $this->save();
        $jsp = new JubelioSyncProduct();
        
        $productType     = new ProductType();
        $productTypeSize = new ProductTypeSize();
        $dimension       = new Dimension();
        $client          = new Client();
        $c               = $client->where('id', $this->client_id)->first();
        $d               = $dimension->saveIfNameNotExist($this->variant_ukuran);
        $pt              = $productType->saveIfNameNotExist($this->item_category_name);
        $variant         = $productTypeSize->saveIfNameNotExist($d, $pt->id);
        $synced          = $jsp->where('jubelio_item_group_id', $this->item_group_id)->where('jubelio_variant_color', $this->variant_warna)->first();
        $manualy_synced  = $jsp->where('jubelio_variant_item_code', $this->variant_item_code)->where('pakde_product_id', '!=', 0)->first();
        
        if ($manualy_synced) {
            $product = Product::find($manualy_synced->pakde_product_id);
            JubelioSyncProduct::where('jubelio_variant_item_code', $this->variant_item_code)->where('pakde_product_id', '!=', 0)->update(
                [
                    'jubelio_item_group_id' => $this->item_group_id,
                    'jubelio_variant_color' => $this->variant_warna,
                ]
            );
        } else if (!$synced) {
            $product                  = new Product;
            $product->version         = 1;
            $product->client_id       = $this->client_id;
            $product->product_type_id = $pt->id;
            $product->name            = $this->item_group_name;
            
            if ($this->variant_warna) {
                $product->name = $product->name . " (" . $this->variant_warna . ")";
            }
            
            $product->price                = 0;
            $product->product_price_sizing = "S";
            $product->color                = $this->variant_warna;
            $product->weight               = (float) $this->weight;
            $product->qc_point             = 0;
            $product->status               = 'DRAFT';
            $product->save();
        } else {
            $product = Product::find($synced->pakde_product_id);
        }
        
        $variant_code  = str_replace(" ", "", strtoupper($c->name)) . "/" . str_replace(" ", "", strtoupper($this->item_category_name)) . "/" . str_replace(" ", "", strtoupper($product->name)) . "/" . str_replace(" ", "", strtoupper($this->variant_warna)) . "/" . $variant->id;
        $variant_exist = $jsp->where('jubelio_variant_item_code', $this->variant_item_code)->first();
        
        if (!$variant_exist) {
            $jsp                            = new JubelioSyncProduct();
            $jsp->jubelio_item_group_id     = $this->item_group_id;
            $jsp->jubelio_variant_item_code = $this->variant_item_code;
            $jsp->pakde_product_id          = $product->id;
            $jsp->pakde_inbound_detail_sku  = $variant_code;
            $jsp->client_id                 = $this->client_id;
            $jsp->jubelio_variant_color     = $this->variant_warna;
            $jsp->save();
        } else {
//            JubelioSyncProduct::where('jubelio_variant_item_code', $this->variant_item_code)
//                ->update([
//                    'jubelio_item_group_id' => $this->item_group_id
//                ]);
        }
        
        $product_count = DB::table('inbound_detail_location')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
            ->where('inbound_detail.product_id', '=', $product->id)
            ->where('inbound_detail.product_type_size_id', $variant->id)
            ->whereNotNull('inbound_detail_location.shelf_id')
            ->whereNull('inbound_detail_location.order_detail_id')
            ->whereNull('inbound_detail_location.date_picked')
            ->whereNull('inbound_detail_location.date_outbounded')
            ->count();
        
        if ($product_count > 0) {
            $id             = new Inbound_detail();
            $inbound_detail = $id->where('product_id', $product->id)->where('product_type_size_id', $variant->id)->where('actual_qty', '>', 0)->first();
        } else {
            $batch               = new InboundBatch;
            $batch->client_id    = $this->client_id;
            $batch->arrival_date = date('Y-m-d H:i:s');
            $batch->status       = 'REGISTER';
            $batch->save();
            
            $inbound                  = new Inbound;
            $inbound->client_id       = $this->client_id;
            $inbound->batch_id        = $batch->id;
            $inbound->name            = $product->name;
            $inbound->product_id      = $product->id;
            $inbound->product_type_id = $pt->id;
            $inbound->created_at      = date('Y-m-d H:i:s');
            $inbound->updated_at      = date('Y-m-d H:i:s');
            $inbound->status          = 'REGISTER';
            $inbound->save();
            
            $inbound_detail                       = new Inbound_detail;
            $inbound_detail->actual_qty           = 0;
            $inbound_detail->code                 = generate_code();
            $inbound_detail->color                = $this->variant_warna;
            $inbound_detail->name                 = $product->name;
            $inbound_detail->price                = $product->price;
            $inbound_detail->product_id           = $product->id;
            $inbound_detail->inbound_id           = $inbound->id;
            $inbound_detail->product_type_size_id = $variant->id;
            $inbound_detail->sku                  = $variant_code;
            $inbound_detail->stated_qty           = 0;
            $inbound_detail->status               = 'ACTIVE';
            $inbound_detail->save();
        }
        
        $product->inbound_detail            = $inbound_detail;
        $product->jubelio_variant_item_code = $this->variant_item_code;
        
        return ['product' => $product];
    }
}
