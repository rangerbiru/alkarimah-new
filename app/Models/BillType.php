<?php

namespace App\Models;

use App\Constants\EducationLevel;
use App\Enums\BillPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillType extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'bill_type';
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
            'period' => BillPeriod::class,
            // 'spp' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function periodName(): Attribute
    {
        return Attribute::make(
            get: function() {
                switch ($this->period->value) {
                    case 1:
                    $period = __('label.one_time');
                    break;

                    case 2:
                    $period = __('label.monthly');
                    break;

                    default:
                    $period = __('label.semiannual');
                }

                return $period;
            }
        );
    }

    protected function isPeriodOnetime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->period->value == BillPeriod::OneTime->value
        );
    }

    protected function isPeriodMonthly(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->period->value == BillPeriod::Monthly->value
        );
    }

    protected function isPeriodSemiannual(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->period->value == BillPeriod::Semiannual->value
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

        self::created(function ($model) {
            $years = Year::select('id')->get();
            $classes = EducationLevel::Classes;

            foreach ($years as $y) {
                foreach ($classes as $c) {
                    ReportBill::create([
                        'id_year' => $y->id,
                        'id_type' => $model->id,
                        'level' => $c,
                        'branch_id' => $model->branch_id
                    ]);
                }
            }

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
                'name',
                'period',
                'spp',
                'branch_id',
            ]);
    }
}
