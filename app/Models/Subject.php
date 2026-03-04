<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subject';
    protected $fillable = [
        'name',
        'lesson_hours',
        'level_education'
    ];
}