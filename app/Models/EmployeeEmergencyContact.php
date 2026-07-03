<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEmergencyContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'contact_name',
        'relationship',
        'address',
        'phone',
        'mobile_phone',
        'email',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
