<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActualSubmissionItems extends Model
{
    protected $table = 'actual_submission_items';

    protected $fillable = [
        'submissions_id',
        'items_id',
        'quantity',
        'price',
        'note',
    ];

    public function submissions()
    {
        return $this->belongsTo(Submissions::class);
    }

    public function items()
    {
        return $this->belongsTo(Items::class);
    }
}
