<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inbound extends Model
{
    protected $table = 'inbound';

    public function inbound_detail(){
    	return $this->hasMany('App\Inbound_detail');
    }

    public function inbound_image(){
    	return $this->hasMany('App\Inbound_image');	
    }
}
