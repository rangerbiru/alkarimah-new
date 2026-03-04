<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentPermitGroup extends Model
{
    use HasFactory;

    protected $table = 'student_permit_groups';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ustadz_id',
        'group_id',
        'group_name',
        'student_id',
        'student_name',
        'description',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }


    /**
     * Get the ustadz that owns the StudentPermitGroup.
     */
    public function ustadz(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'ustadz_id');
    }

    /**
     * Get the student permits for the StudentPermitGroup.
     */
    public function studentPermits(): HasMany
    {
        return $this->hasMany(StudentPermit::class);
    }
}
