<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasicData extends Model
{
    protected $table = 'basic_data';
    protected $fillable = [
        'id_teacher',
        'id_subject',
        'id_class'
    ];

    public function class()
    {
        return $this->belongsTo(Classroom::class, 'id_class');
    }

    public function teacher()
    {
        return $this->belongsTo(Employee::class, 'id_teacher');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_subject');
    }
}