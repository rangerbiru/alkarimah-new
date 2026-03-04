<?php

namespace App\Models\Hadist;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tirmidzi extends Model
{
    use HasFactory;

    protected $table = 'sunan_tirmidzi';
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
