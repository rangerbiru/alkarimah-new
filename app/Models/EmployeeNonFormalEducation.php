<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeNonFormalEducation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'program_name',
        'organizer',
        'start_date',
        'end_date',
        'has_certificate',
        'certificate_path',
    ];

    protected $casts = [
        'has_certificate' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
