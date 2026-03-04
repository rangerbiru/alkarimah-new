<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetManzil extends Model
{
    protected $table = 'target_murojaah_manzil';
    protected $connection = 'mysql_second';
    protected $primaryKey = 'id';
    public $timestamps = false;

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
        'total_target',
        'akhir_target_juz',
        'akhir_target_halaman',
    ];

    public function proses()
    {
        return $this->hasOne(TahfidzProcess::class, 'id_target_murojaah_manzil', 'id');
    }

    public function activeTargetManzil()
    {
        return $this->hasOne(ActiveTargetManzil::class, 'id_target_murojaah');
    }
}
