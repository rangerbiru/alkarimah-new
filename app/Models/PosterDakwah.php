<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosterDakwah extends Model
{
    use HasFactory;

    protected $table = 'poster_dakwah';
    protected $primaryKey = 'id';

    protected $fillable = [
        'judul',
        'deskripsi',
        'gambar',
    ];
}
