<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeActivity extends Model
{
    protected $fillable = [
        'id_responsible_person',
        'activity_date',
        'related_field',
        'activity_type',
        'activity_name',
        'responsible_person',
        'location',
        'participant_count',
        'activity_summary',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function committeeMembers()
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'committee_members');
    }

    public function documents()
    {
        return $this->hasMany(CommitteeDocument::class);
    }

    // Optional: helper untuk jenis dokumen tertentu
    public function photos()
    {
        return $this->documents()->where('file_type', 'photo');
    }

    public function skDocuments()
    {
        return $this->documents()->where('file_type', 'sk');
    }

    public function beritaAcara()
    {
        return $this->documents()->where('file_type', 'berita_acara');
    }
}
