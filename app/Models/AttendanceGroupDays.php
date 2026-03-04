<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceGroupDays extends Model
{
    protected $table = 'attendance_group_days';
    protected $fillable = [
        'attendance_group_id',
        'day_name',
        'check_in_time',
        'tolerance_in',
        'check_out_time',
        'tolerance_out',
        'shift_id',
        'created_at',
        'updated_at',
    ];

    public function shift()
    {
        return $this->hasMany(AttendanceShifts::class, 'attendance_group_id');
    }
}
