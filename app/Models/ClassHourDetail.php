<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassHourDetail extends Model
{
    protected $table = 'class_hour_details';

    protected $fillable = [
        'id_class_hour',
        'day',
        'jp_number',
        'label',
        'start_time',
        'end_time',
        'id_subject',
        'id_employee'
    ];

    public function classHour()
    {
        return $this->belongsTo(ClassHours::class, 'id_class_hour');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_subject');
    }

    public function teacher()
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function attendance()
    {
        return $this->hasMany(AttendanceStudents::class, 'id_class_hour_details');
    }
}
