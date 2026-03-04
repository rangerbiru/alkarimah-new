<?php

namespace App\Models;

use App\Helpers\Common;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Year extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'year';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function yearName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->start_year . ' - ' . $this->end_year
        );
    }

    protected function monthRange(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->start_year == $this->end_year) ? Common::monthFormat($this->start_month) . ' ' . $this->start_year : Common::monthFormat($this->start_month) . ' ' . $this->start_year . ' - ' . Common::monthFormat($this->end_month) . ' ' . $this->end_year
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status) ? __('label.active') : __('label.not_active')
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status) ? '<span class="badge badge-success">' . __('label.active') . '</span>' : '<span class="badge badge-success">' . __('label.not_active') . '</span>'
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
            ->setDescriptionForEvent(fn (string $eventName) => "This model has been {$eventName}")
            ->logOnly([
                'start_year', 'start_month', 'end_year', 'end_month', 'status', 'branch_id',
            ]);
    }

    public function scopeActive($query)
    {
        return $query->whereStatus(true);
    }
}
