<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeFamily extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'relationship',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'death_date',
        'education_last',
        'occupation',
        'address',
        'rt_rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'phone',
        'mobile_phone',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
