<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionItem extends Model
{
    protected $table = 'submission_items';

    protected $fillable = [
        'submissions_id',
        'items_id',
        'quantity',
        'location',
        'note',
    ];

    public function submission()
    {
        return $this->belongsTo(Submissions::class);
    }

    public function item()
    {
        return $this->belongsTo(Items::class);
    }
}
