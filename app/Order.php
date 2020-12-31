<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Config;

class Order extends Model
{
    protected $table = 'orders';

    public static function orderType()
    {
    	return array(
    		"NEW" => "New Order",
    		"EVENT" => "Event",
    		"RETURN" => "Return"
    	);
    }

    public static function orderTypeReversed()
    {
    	return array(
    		"New Order" => "NEW",
    		"Event" => "EVENT",
    		"Return" => "RETURN"
    	);
    }

    public static function orderTypeList()
    {
        return Config::get('constants.order_status');
    }
}
