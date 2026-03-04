<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceStudents extends Model
{
    protected $table = 'attendance_students';
    protected $guarded = [];
    protected $fillable = [
        'id_student',
        'id_class_hour_details',
        'date',
        'status',
        'created_at',
        'updated_at',
    ];
    public function classHourDetail()
    {
        return $this->belongsTo(ClassHourDetail::class, 'id_class_hour_details');
    }
}
