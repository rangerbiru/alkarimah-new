<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEducation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'level',
        'institution_name',
        'city',
        'major',
        'gpa',
        'start_year',
        'end_year',
        'graduation_date',
        'certificate_path',
    ];

    protected $casts = [
        'gpa' => 'decimal:2',
        'start_year' => 'integer',
        'end_year' => 'integer',
        'graduation_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
