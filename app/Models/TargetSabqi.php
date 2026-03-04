<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetSabqi extends Model
{
    protected $table = 'target_murojaah';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $connection = 'mysql_second';


    protected $fillable = [
        'nama_kaldik',
        'id_pengampu',
        'id_santri',
        'nama_santri',
        'nama_lembaga',
        'periode_kaldik',
        'jenis_kaldik',
        'jeda_pertemuan',
        'jenis_target',
        'target_perhari',
        'mulai_target_juz',
        'mulai_target_halaman',
        'mulai_target_baris',
        'total_target',
        'akhir_target_juz',
        'akhir_target_halaman',
        'akhir_target_baris',
    ];

    public function proses()
    {
        return $this->hasOne(TahfidzProcess::class, 'id_target_murojaah', 'id');
    }

    public function activeTargetSabqi()
    {
        return $this->hasOne(ActiveTargetSabqi::class, 'id_target_murojaah');
    }
}
