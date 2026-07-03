<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPreviousSchool extends Model
{
    protected $fillable = [
        'student_id',
        'school_name',
        'school_type',
        'school_status',
        'school_city',
        'npsn',
        'un_participant_number',
        'skhu_number',
        'ijazah_number',
        'skhu_date',
    ];
}
