<?php

namespace App\Models\Hadist;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IbnuMajah extends Model
{
    use HasFactory;

    protected $table = 'sunan_ibnu_majah';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'kitab',
        'arab',
        'terjemah'
    ];

    public $timestamps = false;

    public function getKitabAttribute($value)
    {
        return ucwords($value);
    }
}
