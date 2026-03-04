<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\EducationLevel;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'class';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'level_education' => EducationLevel::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->branch_id = Auth::user()->branch_id;
            $model->created_by = Auth::id();

            return $model;
        });

        self::updating(function ($model) {
            $model->updated_by = Auth::id();

            return $model;
        });

        self::deleting(function ($model) {
            self::whereId($model->id)->update(['deleted_by' => Auth::id()]);

            return $model;
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}")
            ->logOnly([
                'id_wali_kelas',
                'name',
                'level_education',
                'level_class',
                'branch_id',
            ]);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'branch_id')->whereRole(UserRole::Admin);
    }

    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'id_wali_kelas')->withTrashed();
    }

    public function students() {
        return $this->hasMany(Student::class, 'id_class')->withTrashed();
    }

}
