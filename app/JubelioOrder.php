<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JubelioOrder extends Model
{
    protected $table = 'jubelio_order';
    
    /**
     *
     * @return bool true for skip
     */
    public function saveOrSkip()
    {
        $jo_exist = $this->where('salesorder_id', $this->salesorder_id)->first();
        
        if (!$jo_exist) {
            $this->save();
        }
        
        if (isset($jo_exist->order_number) && $jo_exist->order_number != "") {
            return true;
        }
        
        return false;
    }
}
