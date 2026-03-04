<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassHours extends Model
{
    use HasFactory;

    protected $table = 'class_hours';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_class',
        'id_branch',
        'name',
        'day_of_week',
        'jp_count',
        'jp_duration',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'id_branch');
    }

    public function class()
    {
        return $this->belongsTo(Classroom::class, 'id_class');
    }

    public function details()
    {
        return $this->hasMany(ClassHourDetail::class, 'id_class_hour');
    }

    // public function students()
    // {
    //     return $this->hasMany(Student::class, 'id_class');
    // }
}
