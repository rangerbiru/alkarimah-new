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

    /**
     * Batasi ke pelanggaran siswa di kelas tertentu. null = tanpa batas.
     */
    public function scopeInClasses($query, ?array $classIds)
    {
        return $query->when($classIds !== null, fn ($q) => $q->whereIn(
            'student_id',
            Student::withTrashed()->select('id')->whereIn('id_class', $classIds)
        ));
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
