<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeActivity extends Model
{
    protected $table = 'employee_activities';

    protected $guarded = [];

    public function position()
    {
        return $this->belongsTo(Position::class, 'id_position');
    }
}
