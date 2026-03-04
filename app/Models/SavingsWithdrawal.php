<?php

namespace App\Models;

use App\Enums\SavingsWithdrawalStatus;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SavingsWithdrawal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'savings_withdrawal';
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
            'status' => SavingsWithdrawalStatus::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function dates(): Attribute
    {
        return Attribute::make(
            set: fn($value) => date('Y-m-d', strtotime($value))
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status->value == SavingsWithdrawalStatus::Processed->value) ? '<span class="badge bg-success text-uppercase"><i class="fa-solid fa-check-circle"></i>&nbsp; ' . __('label.processed') . '</span>' : '<span class="badge bg-danger text-uppercase"><i class="fa-solid fa-times-circle"></i>&nbsp; ' . __('label.unprocessed') . '</span>'
        );
    }

    protected function isProcessed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->value == SavingsWithdrawalStatus::Processed->value
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->number = self::generateNumber();
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
                'id_student',
                'number',
                'dates',
                'total',
                'status',
                'processed_at',
                'processed_by',
            ]);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_student')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'processed_by')->withTrashed();
    }

    public function scopeNotProcessed($query)
    {
        return $query->whereStatus(SavingsWithdrawalStatus::Waiting->value);
    }

    public function scopeProcessed($query)
    {
        return $query->whereStatus(SavingsWithdrawalStatus::Processed->value);
    }

    public static function generateNumber()
    {
        $count = self::whereMonth('dates', date('n'))->whereYear('dates', date('Y'))->withTrashed()->count();
        $sequence = Str::padLeft($count + 1, 4, '0');

        return 'WD' . date('Ym') . $sequence;
    }
}
