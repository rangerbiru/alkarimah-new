<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHealthRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'disease_type',
        'illness_start_date',
        'illness_end_date',
        'medical_treatment',
        'notes',
    ];

    protected $casts = [
        'illness_start_date' => 'date',
        'illness_end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
