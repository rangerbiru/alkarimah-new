<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPermit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'id_parent',
        'student_permit_group_id',
        'permit_start_date',
        'permit_end_date',
        'purpose',
        'destination',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'permission_note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permit_start_date' => 'datetime',
        'permit_end_date' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the student that owns the StudentPermit.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the student permit group that owns the StudentPermit.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(StudentPermitGroup::class, 'student_permit_group_id', 'group_id');
    }

    public function ustadz(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the ustadz who approved the StudentPermit.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
