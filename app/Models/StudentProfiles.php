<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfiles extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'weight',
        'height',
        'blood_type',
        'medical_history',
        'physical_disabilities',
        'daily_habits',
        'personality',
        'nickname',
        'religion',
        'home_language',
        'living_with_parents',
        'photo_path'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
