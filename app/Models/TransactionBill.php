<?php

namespace App\Models;

use App\Constants\EducationLevel;
use App\Enums\BillStatus;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TransactionBill extends Model
{
    use HasFactory;

    protected $table = 'transaction_bill';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BillStatus::class,
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
            get: fn() => ($this->status->value == BillStatus::Paid->value) ? '<span class="badge bg-success text-uppercase"><i class="fa-solid fa-check-circle"></i>&nbsp; ' . __('label.paid_off') . '</span>' : '<span class="badge bg-danger text-uppercase"><i class="fa-solid fa-times-circle"></i>&nbsp; ' . __('label.not_paid') . '</span>'
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status->value == BillStatus::Paid->value) ? __('label.paid_off') : __('label.not_paid')
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->value == BillStatus::Paid->value
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            if (Auth::check()) {
                $model->branch_id = Auth::user()->branch_id;
                $model->created_by = Auth::id();
            } else {
                $model->created_by = 0;
            }

            return $model;
        });

        self::created(function ($model) {
            $trans_bill =  self::select('id', 'id_bill', 'id_student', 'total', 'status', 'branch_id')
                ->with([
                    'bill' => fn($query) => $query->select('id', 'id_type', 'id_year'),
                    'student' => function ($query) {
                        $query->select('id', 'id_class')
                            ->with(['class' => fn($qc) => $qc->select('id', 'level_education', 'level_class')]);
                    }
                ])
                ->whereId($model->id)
                ->first();

            self::updateReport($trans_bill);

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

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_student')->withTrashed();
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'id_bill')->withTrashed();
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction')->withTrashed();
    }

    public function scopePaid($query)
    {
        return $query->whereStatus(BillStatus::Paid->value);
    }

    public function scopeNotPaid($query)
    {
        return $query->whereStatus(BillStatus::NotPaid->value);
    }

    public static function updateReport($model, $paid_at='')
    {
        $year = $model->bill->id_year;
        $type = $model->bill->id_type;
        $level = EducationLevel::Classes[$model->student->class->level_education->value . '.' . $model->student->class->level_class];

        $report = ReportBill::select('id', 'total', 'paid', 'remaining')
            ->withoutGlobalScope(BranchScope::class)
            ->whereIdYear($year)
            ->whereIdType($type)
            ->whereLevel($level)
            ->whereBranchId($model->branch_id)
            ->first();

        if ($model->status == BillStatus::Paid) {
            $report->paid += $model->total;
            $report->remaining -= $model->total;

            $date = date('Y-m-d', strtotime($paid_at));
            $report_type = ReportBillType::select('id', 'quantity', 'total')
                ->whereIdYear($year)
                ->whereDates($date)
                ->whereIdType($type)
                ->whereLevel($level)
                ->first();

            if (empty($report_type)) {
                ReportBillType::create([
                    'id_year' => $year,
                    'id_type' => $type,
                    'dates' => $date,
                    'level' => $level,
                    'quantity' => 1,
                    'total' => $model->total,
                    'branch_id' => $model->branch_id,
                ]);
            } else {
                $report_type->quantity += 1;
                $report_type->total += $model->total;
                $report_type->save();
            }
        } else {
            $report->total += $model->total;
            $report->remaining += $model->total;
        }

        $report->save();
    }
}
