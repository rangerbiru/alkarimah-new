<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiKbmTahfidz extends Model
{
    protected $table = 'absensi_kbm_tahfidz';
    protected $guarded = [];
    protected $connection = 'mysql_second';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_pegawai',
        'id_halaqoh',
        'nama_lembaga',
        'periode_akademik',
        'nama_pengajar',
        'nama_halaqoh',
        'pertemuan_kbm',
        'materi_kelas',
        'jumlah_santri',
        'hadir',
        'izin',
        'sakit',
        'alpha',
        'catatan',
        'pembukaan_doa',
        'apersepsi',
        'evaluasi',
        'doa_penutup'
    ];
}
