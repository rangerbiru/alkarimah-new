<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    protected $table = 'teaching_journals';
    protected $guarded = [];

    // Relasi ke ClassHourDetail
    public function classHourDetail()
    {
        return $this->belongsTo(ClassHourDetail::class, 'id_class_hour_details');
    }
}
