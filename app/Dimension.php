<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    public    $timestamps = false;
    protected $table      = "dimension";
    
    public function saveIfNameNotExist($label)
    {
        $label = $label ?: 'default';
        
        if ($exist = $this->where('label', $label)->first()) {
            return $exist;
        } else {
            $this->label    = $label;
            $this->version  = 0;
            $this->ordering = 0;
            $this->save();
            
            return $this;
        }
    }
}
