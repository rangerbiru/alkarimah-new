<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePreviousCompany extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'reference_name',
        'reference_position',
        'reference_phone',
        'reference_email',
        'relationship',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
