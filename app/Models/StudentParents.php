<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentParents extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'father_name',
        'father_id_number',
        'father_status',
        'father_education',
        'father_occupation',
        'father_phone',
        'mother_name',
        'mother_id_number',
        'mother_status',
        'mother_education',
        'mother_occupation',
        'mother_phone',
        'guardian_name',
        'guardian_id_number',
        'guardian_occupation',
        'guardian_email',
        'guardian_phone',
        'guardian_income',
        'family_card_number',
        'family_income',
        'child_order',
        'siblings_count',
        'step_siblings_count',
        'adopted_siblings_count',
        'family_members_count',
        'orphan_status',
        'guardian_notes',
        'approval_status'
    ];

    protected $casts = [
        'child_order' => 'integer',
        'siblings_count' => 'integer',
        'has_scholarship' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
