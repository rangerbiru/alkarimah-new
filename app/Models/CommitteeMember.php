<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeMember extends Model
{
    protected $fillable = ['committee_activity_id', 'employee_id'];

    public function activity()
    {
        return $this->belongsTo(CommitteeActivity::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
