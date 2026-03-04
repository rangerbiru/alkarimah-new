<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataKaldik extends Model
{
    protected $table = 'data_kaldik';
    protected $primaryKey = 'id_kaldik';
    public $timestamps = false;
    protected $connection = 'mysql_second';


    protected $fillable = [
        'id_kaldik',
        'nama_lembaga',
        'periode_kaldik',
        'dari_tanggal',
        'sampai_tanggal',
        'jenis_kaldik',
        'sesi',
        'hari_belajar',
        'nama_kaldik',
        'aktiv_tm',
        'jumlah_tm',
        'keterangan'
    ];
}
