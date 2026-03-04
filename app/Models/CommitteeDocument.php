<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeDocument extends Model
{
    protected $fillable = [
        'committee_activity_id',
        'file_path',
        'file_type',
        'file_name',
        'description',
    ];

    public function activity()
    {
        return $this->belongsTo(CommitteeActivity::class);
    }

    public function committeeActivity()
    {
        return $this->belongsTo(CommitteeActivity::class, 'committee_activity_id');
    }
}
