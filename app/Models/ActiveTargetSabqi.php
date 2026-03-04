<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveTargetSabqi extends Model
{
    protected $table = "active_target_murojaah";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connection = 'mysql_second';


    protected $fillable = [
        'id',
        'id_target_murojaah',
        'id_kaldik',
        'hari',
        'target_baris',
        'target_murojaah',
        'tanggal',
        'keterangan',
    ];
}
