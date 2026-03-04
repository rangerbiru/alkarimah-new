<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $table = 'class_schedule';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_class',
        'id_class_hour',
        'id_subject',
        'id_employee'
    ];

    public function teacher()
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_subject');
    }

    public function class()
    {
        return $this->belongsTo(Classroom::class, 'id_class');
    }

    public function classHour()
    {
        return $this->belongsTo(ClassHours::class, 'id_class_hour');
    }
}
