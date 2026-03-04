<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LunchRequest extends Model
{
    protected $table = 'lunch_requests';

    protected $fillable = [
        'attendance_id',
        'attendance_group_id',
        'employee_id',
        'request',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function group()
    {
        return $this->belongsTo(AttendanceGroup::class, 'attendance_group_id');
    }
}
