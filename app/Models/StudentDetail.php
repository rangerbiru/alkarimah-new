<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    protected $fillable = [
        'student_id',
        'kk_number',
        'hobby',
        'ambition',
        'sibling_count',
        'financing_by',
        'phone',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'distance_to_school',
        'transportation',
        'is_kk_submitted',
        'is_akta_submitted',
    ];
}
