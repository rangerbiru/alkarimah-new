<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLocation extends Model
{
    protected $table = 'attendance_group_location';
    protected $fillable = [
        'attendance_group_id',
        'location_name',
        'coordinate',
        'attendance_location',
        'attendance_radius',
        'qrcode',
        'created_at',
        'updated_at',
    ];

    public function group()
    {
        return $this->belongsTo(AttendanceGroup::class, 'attendance_group_id');
    }
}
