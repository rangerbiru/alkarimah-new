<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveTargetManzil extends Model
{
    protected $table = "active_target_murojaah_manzil";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connection = 'mysql_second';


    protected $fillable = [
        'id',
        'id_target_murojaah',
        'id_kaldik',
        'hari',
        'target_halaman',
        'target_murojaah',
        'tanggal',
        'keterangan',
    ];
}
