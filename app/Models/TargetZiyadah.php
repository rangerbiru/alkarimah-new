<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetZiyadah extends Model
{
    protected $table = 'target_ziyadah';
    protected $guarded = [];
    protected $connection = 'mysql_second';

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
        'mulai_target_baris',
        'total_target',
        'akhir_target_juz',
        'akhir_target_halaman',
        'akhir_target_baris',
    ];

    public function proses()
    {
        return $this->hasOne(TahfidzProcess::class, 'id_target_ziyadah', 'id');
    }

    public function activeTargetZiyadah()
    {
        return $this->hasOne(ActiveTargetZiyadah::class, 'id_target_ziyadah');
    }
}
