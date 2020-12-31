<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductTypeSize extends Model
{
    protected $table = 'product_type_size';
    
    public function saveIfNameNotExist($dimension, $product_type_id)
    {
        if ($exist = $this->where('product_type_id', $product_type_id)->where('name', $dimension->label)->first()) {
            return $exist;
        } else {
            $this->product_type_id = $product_type_id;
            $this->name            = $dimension->label;
            $this->dimension_id    = $dimension->id;
            $this->active          = 1;
            $this->version         = 1;
            $this->save();
            
            return $this;
        }
    }
}
