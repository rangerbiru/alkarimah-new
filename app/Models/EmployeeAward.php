<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAward extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'award_name',
        'awarded_by',
        'received_date',
        'description',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
