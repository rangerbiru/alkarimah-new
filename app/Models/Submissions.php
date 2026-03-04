<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submissions extends Model
{
    protected $table = 'submissions';

    protected $fillable = [
        'activity_name',
        'activity_type',
        'description',
        'employee_id',
        'approve1',
        'approve2',
        'last_approve',
        'status',
        'reject_reason',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function items()
    {
        return $this->belongsToMany(Items::class, 'submission_items')
            ->withPivot('quantity', 'note')
            ->withTimestamps();
    }

    public function submissionItems()
    {
        return $this->hasMany(SubmissionItem::class);
    }

    public function location()
    {
        return $this->hasMany(SubmissionLocation::class);
    }

    public function unit()
    {
        return $this->belongsTo(UnitMaster::class);
    }

    public function rejectedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'rejected_by');
    }

    public function actualSubmissionItems()
    {
        return $this->hasMany(ActualSubmissionItems::class);
    }
}
