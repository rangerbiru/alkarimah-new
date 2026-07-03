<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkExperience extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'company_name',
        'position',
        'start_date',
        'end_date',
        'reason_for_leaving',
        'last_salary',
        'supervisor_name',
        'supervisor_contact',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
