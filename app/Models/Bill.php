<?php

namespace App\Models;

use App\Constants\EducationLevel;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bill extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'bill';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];
    protected $fillable = [];

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function billingDateDay(): Attribute
    {
        return Attribute::make(
            get: fn() => date('j', strtotime($this->billing_date))
        );
    }

    protected function dueDateDay(): Attribute
    {
        return Attribute::make(
            get: fn() => date('j', strtotime($this->due_date))
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->branch_id = Auth::user()->branch_id;
            $model->created_by = Auth::id();

            $report = ReportBill::whereIdYear($model->id_year)->whereIdType($model->id_type)->count();

            if ($report == 0) {
                $classes = EducationLevel::Classes;

                foreach ($classes as $c) {
                    ReportBill::create([
                        'id_year' => $model->id_year,
                        'id_type' => $model->id_type,
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
            $model->deleted_by = Auth::id();

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
                'id_year',
                'id_type',
                'name',
                'nominal',
                'billing_date',
                'due_date',
                'description',
                'start_month',
                'start_year',
                'end_month',
                'end_year',
                'branch_id',
            ]);
    }

    public function year()
    {
        return $this->belongsTo(Year::class, 'id_year')->withTrashed();
    }

    public function type()
    {
        return $this->belongsTo(BillType::class, 'id_type')->withTrashed();
    }
}
