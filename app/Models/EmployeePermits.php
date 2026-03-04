<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePermits extends Model
{
    protected $table = 'employee_permits';

    protected $fillable = [
        'employee_id',
        'permit_type_id',
        'department_id',
        'name',
        'permit_start_time',
        'permit_hour_total',
        'permit_day_total',
        'date',
        'reason',
        'status',
        'attachment',
        'decision_by',
        'note',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function permitType()
    {
        return $this->belongsTo(PermitType::class, 'permit_type_id');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function decisionBy()
    {
        return $this->belongsTo(Employee::class, 'decision_by');
    }
}
