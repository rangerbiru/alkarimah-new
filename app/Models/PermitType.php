<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitType extends Model
{
    protected $table = 'permit_types';

    protected $fillable = [
        'permit_type',
        'level',
        'description',
        'wage_status',
    ];
}
