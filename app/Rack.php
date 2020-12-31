<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    protected $table = 'rack';

    public function shelf() {
    	return $this->hasMany('App\Shelf');
    }

    public function delete() {
    	$this->shelf()->delete();
    	parent::delete();
    }
}
