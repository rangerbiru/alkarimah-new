<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionLocation extends Model
{
    protected $table = 'submission_locations';

    protected $fillable = ['submissions_id', 'unit_id'];

    public function submission()
    {
        return $this->belongsTo(Submissions::class);
    }

    public function unit()
    {
        return $this->belongsTo(UnitMaster::class, 'unit_id', 'id');
    }
}
