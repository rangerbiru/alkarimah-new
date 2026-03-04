<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceSchedule extends Model
{
    use HasFactory;
    protected $table = 'attendance_schedule';
    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'check_in_time',
        'check_out_time',
        'tolerance_minutes',
    ];

    // Relasi ke model Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
