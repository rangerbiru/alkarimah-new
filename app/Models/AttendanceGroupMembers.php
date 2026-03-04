<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceGroupMembers extends Model
{
    protected $table = 'attendance_group_members';
    protected $primaryKey = 'id';

    protected $fillable = [
        'attendance_group_id',
        'employee_id',
        'joined_at'
    ];

    public $timestamps = false;

    public function attendanceGroup()
    {
        return $this->belongsTo(AttendanceGroup::class, 'attendance_group_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
