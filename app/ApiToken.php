<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ApiToken extends Model
{
    protected $table = 'api_token';
}
