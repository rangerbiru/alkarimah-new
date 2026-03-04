<?php

namespace App\Models;

use App\Casts\Json;
use App\Constants\EducationLevel;
use App\Enums\BillPeriod;
use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BillDiscount extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'bill_discount';
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
            'applies_to' => Json::class,
            'status' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status) ? '<span class="badge bg-success">' . strtoupper(__('label.active')) . '</span>' : '<span class="badge bg-danger">' . strtoupper(__('label.not_active')) . '</span>'
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);
        static::addGlobalScope(new ActiveScope);

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
                'id_student',
                'id_bill',
                'id_year',
                'nominal',
                'starts',
                'end',
                'branch_id',
            ]);
    }

    public function year()
    {
        return $this->belongsTo(Year::class, 'id_year')->withTrashed();
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'id_bill')->withTrashed();
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_student')->withTrashed();
    }
}
