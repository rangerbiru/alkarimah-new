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

    public function isMudir(): bool
    {
        return str_contains(strtolower($this->position), 'mudir');
    }

    public function isWadir(): bool
    {
        return str_contains(strtolower($this->position), 'wadir');
    }

    public function isLogistik(): bool
    {
        return str_contains(strtolower($this->position), 'logistik');
    }

    public function isBendahara(): bool
    {
        return str_contains(strtolower($this->position), 'bendahara');
    }

    public function canApprovePermit(): bool
    {
        return $this->isMudir() || $this->isWadir();
    }
}
