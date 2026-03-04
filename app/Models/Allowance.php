<?php

namespace App\Models;

use App\Enums\AllowanceCategory;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Allowance extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'allowance';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'category' => AllowanceCategory::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function categoryName(): Attribute
    {
        return Attribute::make(
            get: function() {
                switch ($this->category) {
                    case AllowanceCategory::Tanggungan:
                    $name = 'Tanggungan';
                    break;

                    case AllowanceCategory::Kinerja:
                    $name = 'Kinerja';
                    break;

                    default:
                    $name = 'Struktural';
                }

                return $name;
            }
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
                'name', 'category'
            ]);
    }

    public function scopeStructural($query)
    {
        return $query->whereCategory(AllowanceCategory::Struktural);
    }

    public function scopeLiability($query)
    {
        return $query->whereCategory(AllowanceCategory::Tanggungan);
    }

    public function scopePerformance($query)
    {
        return $query->whereCategory(AllowanceCategory::Kinerja);
    }
}
