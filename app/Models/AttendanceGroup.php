<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceGroup extends Model
{
    protected $table = 'attendance_group';
    protected $primaryKey = 'id';

    protected $fillable = [
        'group_name',
        'position',
        'shift_work',
        'description',
        'created_at',
        'updated_at'
    ];

    public function days()
    {
        return $this->hasMany(AttendanceGroupDays::class, 'attendance_group_id');
    }

    public function shift()
    {
        return $this->hasMany(AttendanceShifts::class, 'attendance_group_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position', 'id');
    }
}
