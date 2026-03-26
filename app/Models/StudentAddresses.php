<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAddresses extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'home_address',
        'home_district',
        'home_regency',
        'home_province',
        'postal_code',
        'previous_school_address',
        'previous_school_district',
        'previous_school_regency',
        'previous_school_province',
        'distance_to_school'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
