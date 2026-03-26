<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAcademics extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'previous_school_name',
        'previous_school_npsn',
        'previous_school_status',
        'registration_number',
        'session_id',
        'entry_date',
        'payment_status',
        'has_scholarship',
        'scholarship_name',
        'achievements',
        'recommendation_status',
        'graduation_status',
        'notes',
        'nationality',
        'foreign_origin'
    ];

    protected $casts = [
        'has_scholarship' => 'boolean',
        'entry_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
