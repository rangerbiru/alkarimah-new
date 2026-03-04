<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembagianHalaqoh extends Model
{
    protected $table = 'pembagian_halaqoh';
    protected $guarded = [];
    protected $connection = 'mysql_second';

    protected $fillable = [
        'id_halaqoh',
        'id_pegawai',
        'nama_lembaga',
        'periode_kaldik',
        'jenis_kaldik',
        'siswa',
        'nama_pengampu',
        'ruangan',
        'jam_belajar'
    ];
}
