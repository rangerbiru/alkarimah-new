<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceReport extends Model
{
    protected $table = 'attendance';
    protected $fillable = [
        'attendance_group_id',
        'employee_id',
        'day',
        'date',
        'status',
        'check_in_time',
        'late_minutes',
        'check_out_time',
        'early_leave_minutes',
        'photo_in',
        'photo_out',
        'reason_in',
        'reason_out',
        'work_minutes',
        'shift',
        'created_at',
        'updated_at',
    ];

    public function group()
    {
        return $this->belongsTo(AttendanceGroup::class, 'attendance_group_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
