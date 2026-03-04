<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveAbsensiKbmTahfidz extends Model
{
    protected $table = 'active_absensi_kbm_tahfidz';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connection = 'mysql_second';

    protected $fillable = [
        'id',
        'id_absensi',
        'id_santri',
        'id_halaqoh',
        'nama_halaqoh',
        'pertemuan',
        'kehadiran'
    ];
}
