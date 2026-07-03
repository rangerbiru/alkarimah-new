<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ActivityRealization extends Model
{
    use HasFactory;

    protected $table = 'activity_realizations';

    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by'];

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
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function plan()
    {
        return $this->belongsTo(ActivityPlan::class, 'activity_plan_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validated_by');
    }
}
