<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowedSubmissionEmployee extends Model
{
    protected $table = 'allowed_submission_employees';

    public $fillable = ['employee_id', 'position'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
