<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ActivityPlan extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'activity_plans';

    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];

    protected function casts(): array
    {
        return [
            'is_proof_required' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn () => Crypt::encrypt($this->id)
        );
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->created_by = Auth::user()->employee->id;

            return $model;
        });

        self::updating(function ($model) {
            $model->updated_by = Auth::user()->employee->id;

            return $model;
        });

        self::deleting(function ($model) {
            self::whereId($model->id)->update(['deleted_by' => Auth::user()->employee->id]);

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
            ->setDescriptionForEvent(fn (string $eventName) => "Activity Plan has been {$eventName}")
            ->logOnly([
                'name', 'year_id', 'employee_id', 'unit', 'frequency', 'status',
            ]);
    }

    public function year()
    {
        return $this->belongsTo(Year::class, 'year_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function realizations()
    {
        return $this->hasMany(ActivityRealization::class, 'activity_plan_id');
    }
}
