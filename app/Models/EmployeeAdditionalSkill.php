<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdditionalSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'skill_name',
        'field',
        'skill_level',
        'description',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
