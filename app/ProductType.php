<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    public $timestamps = false;
    
    protected $table = 'product_type';
    
    public function saveIfNameNotExist($name)
    {
        if ($exist = $this->where('name', $name)->where('active', 1)->first()) {
            return $exist;
        } else {
            $this->name   = $name;
            $this->active = 1;
            $this->save();
            
            return $this;
        }
    }
}
