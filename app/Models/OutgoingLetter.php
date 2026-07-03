<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OutgoingLetter extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'outgoing_letters';

    protected $guarded = [
        'id',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    /**
     * Relasi ke Kategori Surat
     */
    public function category()
    {
        return $this->belongsTo(LetterCategory::class, 'letter_category_id');
    }

    /**
     * Boot method untuk otomatisasi data
     */
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->branch_id = Auth::user()->branch_id ?? null;
            $model->created_by = Auth::id();
        });

        self::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        self::deleting(function ($model) {
            $model->deleted_by = Auth::id();
            $model->save();
        });
    }

    /**
     * Konfigurasi Spatie Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn (string $eventName) => "Outgoing letter model has been {$eventName}")
            ->logOnly([
                'letter_category_id',
                'level',
                'sequence_number',
                'letter_number',
                'letter_date',
                'destination',
                'subject',
                'priority',
                'signer',
                'status',
                'branch_id',
            ]);
    }

    /**
     * Accessor untuk Label Status
     */
    protected $appends = ['label_status', 'label_level', 'can_action'];

    protected function labelStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => match (strtolower($this->status)) {
                'draft' => 'Draft',
                'diajukan' => 'Diajukan',
                'disetujui' => 'Disetujui',
                'terkirim' => 'Terkirim',
                default => ucfirst($this->status),
            }
        );
    }

    protected function labelLevel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->level) {
                'madrasah_aliyah' => 'Surat Madrasah Aliyah',
                'mts' => 'Surat MTS',
                'pkpps' => 'Surat PKPPS',
                'pesantren' => 'Surat Pesantren',
                default => $this->level,
            }
        );
    }

    protected function canAction(): Attribute
    {
        return Attribute::make(
            get: function () {
                $user = Auth::user();
                if (! $user) {
                    return false;
                }

                $isAdmin = $user->role === UserRole::Admin;

                $isOwner = $this->created_by === $user->id;

                return $isAdmin || $isOwner;
            }
        );
    }
}
