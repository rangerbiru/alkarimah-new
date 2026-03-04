<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSatuanBaris extends Model
{
    protected $table = 'data_satuan_baris';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connection = 'mysql_second';


    protected $fillable = [
        'id',
        'id_surat',
        'nama_lembaga',
        'surat',
        'juz',
        'halaman',
        'baris',
        'keterangan',
        'ayat'
    ];
}
