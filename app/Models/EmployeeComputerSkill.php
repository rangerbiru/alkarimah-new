<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeComputerSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'skill_category',
        'software_list',
        'has_certificate',
        'certificate_path',
    ];

    protected $casts = [
        'has_certificate' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
