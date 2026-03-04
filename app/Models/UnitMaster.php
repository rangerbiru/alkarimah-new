<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitMaster extends Model
{
    protected $table = 'unit_masters';

    protected $fillable = [
        'location_id',
        'unit',
    ];

    public function location()
    {
        return $this->belongsTo(LocationMaster::class);
    }
}
