<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentViolations extends Model
{
    protected $table = 'student_violations';

    protected $fillable = [
        'student_id',
        'violation_id',
        'employee_id',
        'date',
        'time',
        'location',
        'notes',
        'proof',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function violation()
    {
        return $this->belongsTo(ViolationTypes::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
