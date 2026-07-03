<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LetterCategory extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'letter_categories';

    // Melindungi kolom auto-generated agar tidak bisa diisi manual (Mass Assignment)
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
     * Boot method untuk otomatisasi data saat Create, Update, dan Delete
     */
    public static function boot()
    {
        parent::boot();

        // Menerapkan Global Scope Cabang
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

        self::deleting(function ($model) {
            $model->deleted_by = Auth::id();
            $model->save();

            OutgoingLetter::where('letter_category_id', $model->id)->delete();
            IncomingLetter::where('letter_category_id', $model->id)->delete();
        });
    }

    /**
     * Konfigurasi Spatie Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn (string $eventName) => "Letter category model has been {$eventName}")
            ->logOnly([
                'code',
                'name',
                'level',
                'type',
                'branch_id',
            ]);
    }

    protected $appends = ['label_level', 'label_type'];

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

    protected function labelType(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                'masuk' => 'Surat Masuk',
                'keluar' => 'Surat Keluar',
                default => $this->type,
            }
        );
    }
}
