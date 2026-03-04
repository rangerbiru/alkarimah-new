<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataJenisKaldik extends Model
{
    protected $connection = 'mysql_second';
    protected $table = 'jenis_kaldik';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nama_kaldik'
    ];
}
