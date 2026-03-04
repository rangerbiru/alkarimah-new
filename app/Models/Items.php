<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'barcode',
        'category_id',
        'name',
        'type',
        'merk',
        'unit',
        'price',
        'description',
        'photo',
    ];


    public function submissionItems()
    {
        return $this->hasMany(SubmissionItem::class);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class);
    }
}
