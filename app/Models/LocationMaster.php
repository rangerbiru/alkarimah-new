<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationMaster extends Model
{
    protected $table = 'location_masters';

    protected $fillable = [
        'name',
        'code'
    ];
}
