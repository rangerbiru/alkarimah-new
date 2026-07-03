<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'region';
    protected $fillable = ['id', 'id_parent', 'employee_id',  'code', 'name', 'flag'];

    public function parent()
    {
        return $this->belongsTo(Region::class, 'id_parent');
    }

    public function scopeProvince($query)
    {
        return $query->whereFlag(1);
    }
}
