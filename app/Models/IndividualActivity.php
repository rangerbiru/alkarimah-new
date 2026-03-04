<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndividualActivity extends Model
{
    protected $table = 'individual_activity';
    protected $guarded = [];
    protected $fillable = [
        'id_employee',
        'id_activity',
        'description',
        'comment',
        'comment_by',
        'photo'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function activity()
    {
        return $this->belongsTo(EmployeeActivity::class, 'id_activity');
    }
}
