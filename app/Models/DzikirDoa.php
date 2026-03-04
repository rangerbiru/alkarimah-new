<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DzikirDoa extends Model
{
    use HasFactory;

    protected $table = 'dzikir_pp';
    protected $primaryKey = 'id_dzikir_pp';

    protected $fillable = [
        'id_dzikir_pp',
        'user',
        'slug',
        'title',
        'arabic',
        'arti',
        'penjelasan',
        'waktu'
    ];

    public $timestamps = false;
}
