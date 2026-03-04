<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProsesAbsensiKbmTahfidz extends Model
{
    protected $table = 'proses_absensi_kbm_tahfidz';
    protected $connection = 'mysql_second';
    protected $guarded = [];

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_absensi',
        'id_santri',
        'jml_target',
        'target_perhari',
        'mulai_proses_juz',
        'mulai_proses_halaman',
        'mulai_proses_baris',
        'capaian_target_juz',
        'capaian_target_halaman',
        'capaian_target_baris',
        'capaian_target',
        'tanggal',
        'pertemuan',
        'id_target_ziyadah',
        'id_target_murojaah',
        'id_target_murojaah_manzil',
    ];
}
