<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLanguage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'language_name',
        'listening_skill',
        'reading_skill',
        'writing_skill',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
