<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    protected $fillable = [
        'source_name',
        'client_id',
        'client_secret',
        'redirect_uri',
        'code',
        'access_token',
        'refresh_token',
    ];
}
