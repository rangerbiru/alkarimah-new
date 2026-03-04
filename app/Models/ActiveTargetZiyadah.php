<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveTargetZiyadah extends Model
{
    protected $table = "active_target_ziyadah";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connection = 'mysql_second';


    protected $fillable = [
        'id',
        'id_target_ziyadah',
        'id_kaldik',
        'hari',
        'target_baris',
        'target_ziyadah',
        'tanggal',
        'keterangan',
        'baris_ayat',
        'id_satuan_baris'
    ];
}
