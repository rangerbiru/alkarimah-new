<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahfidzPresence extends Model
{
    protected $table = 'active_absensi_kbm_tahfidz';
    protected $connection = 'mysql_second';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
}
