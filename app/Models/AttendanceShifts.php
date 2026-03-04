<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceShifts extends Model
{
    protected $table = 'attendance_shifts';
    protected $fillable = [
        'attendance_group_id',
        'day_name',
        'shift1_check_in_time',
        'shift1_check_out_time',
        'shift2_check_in_time',
        'shift2_check_out_time',
        'shift3_check_in_time',
        'shift3_check_out_time',
        'created_at',
        'updated_at',
    ];
}
