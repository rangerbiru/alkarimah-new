<?php

namespace App\Models;

use App\Casts\Json;
use App\Enums\BillPeriod;
use App\Enums\TransactionDepositStatus;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'transaction';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bills' => Json::class,
            'payment_method' => TransactionMethod::class,
            'status' => TransactionStatus::class,
            'status_deposit' => TransactionDepositStatus::class,
            'status_deposit_code' => TransactionDepositStatus::class,
            'flag' => TransactionFlag::class,
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

    protected function method(): Attribute
    {
        return Attribute::make(
            get: function () {
                switch ($this->payment_method->value) {
                    case TransactionMethod::BSI->value:
                        $method = [
                            'name' => __('label.bank_bsi'),
                            'image' => asset('images/icons/history-bsi.png'),
                            'image_payment' => asset('images/payments/bsi.png'),
                        ];
                        break;

                    case TransactionMethod::BNI->value:
                        $method = [
                            'name' => __('label.bank_bni'),
                            'image' => asset('images/icons/history-bni.png'),
                            'image_payment' => asset('images/payments/bni.png'),
                        ];
                        break;

                    case TransactionMethod::TopupBalance->value:
                        $method = [
                            'name' => __('label.balance_topup'),
                            'image' => asset('images/icons/history-balance.png'),
                            'image_payment' => asset('images/payments/balance.png'),
                        ];
                        break;

                    default:
                        $method = [
                            'name' => __('label.cash'),
                            'image' => asset('images/icons/history-cash.png'),
                            'image_payment' => asset('images/payments/cash.png'),
                        ];
                }

                return (object) $method;
            }
        );
    }

    protected function flagDetail(): Attribute
    {
        return Attribute::make(
            get: function () {
                switch ($this->flag->value) {
                    case TransactionFlag::SetorTabungan->value:
                        $flag = [
                            'name' => __('label.savings_deposit'),
                            'type' => __('label.savings'),
                            'icon' => 'ti ti-wallet',
                            'color' => 'primary'
                        ];
                        break;

                    case TransactionFlag::PengambilanTabungan->value:
                        $flag = [
                            'name' => __('label.savings_withdrawal'),
                            'type' => __('label.savings'),
                            'icon' => 'ti ti-moneybag',
                            'color' => 'danger'
                        ];
                        break;

                    case TransactionFlag::TopupSaldo->value:
                        $flag = [
                            'name' => __('label.topup_balance'),
                            'type' => __('label.balance'),
                            'icon' => 'ti ti-cash-banknote',
                            'color' => 'success'
                        ];
                        break;

                    default:
                        $flag = [
                            'name' => __('label.bill'),
                            'type' => __('label.bill'),
                            'icon' => 'ti ti-credit-card',
                            'color' => 'info'
                        ];
                }

                return (object) $flag;
            }
        );
    }

    protected function getScholarship(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->donation == 0) ? __('label.not') : __('label.yes')
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status->value == TransactionStatus::Paid->value) ? '<span class="badge bg-success text-uppercase"><i class="fa-solid fa-check-circle"></i>&nbsp; ' . __('label.paid_off') . '</span>' : '<span class="badge bg-danger text-uppercase"><i class="fa-solid fa-times-circle"></i>&nbsp; ' . __('label.not_paid') . '</span>'
        );
    }

    protected function bankAccount(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->payment_method->value == TransactionMethod::BNI->value) ? (object) Config::get('ref.bank.bni') : (object) Config::get('ref.bank.bsi')
        );
    }

    protected function billsDetail(): Attribute
    {
        return Attribute::make(
            get: function() {
                $details = [];
                $period_monthly = BillPeriod::Monthly->value;
                $period_semester = BillPeriod::Semiannual->value;

                foreach ($this->bills as $b) {
                    $transaction = TransactionBill::select('id', 'id_bill', 'semester', 'months', 'years', 'subtotal', 'discount', 'total')
                        ->with([
                            'bill' => function ($query) {
                                $query->select('id', 'id_year', 'id_type', 'name')->with([
                                    'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                                    'year' => fn($qy) => $qy->select('id', 'start_year', 'end_year'),
                                ]);
                            }
                        ])
                        ->whereId($b)
                        ->first();

                    $bill_name = $transaction->bill->name;

                    if ($transaction->bill->type->period->value == $period_monthly)
                        $bill_name .= ' - Bulan ' . Common::monthFormat($transaction->months) . ' ' . $transaction->years;
                    else if ($transaction->bill->type->period->value == $period_semester)
                        $bill_name .= ' - Semester {' . $transaction->semester;

                    if ($this->is_paid) {
                        $discount = $transaction->discount;
                        $total = $transaction->subtotal;
                    } else {
                        $discount = 0;
                        $bill_discount = BillDiscount::select('id', 'id_bill', 'applies_to', 'nominal')
                            ->whereIdStudent($this->id_student)
                            ->whereIdBill($transaction->bill->id)
                            ->first();

                        if (!empty($bill_discount)) {
                            if (empty($bill_discount->applies_to))
                                $discount = $bill_discount->nominal;
                            else {
                                $applies = json_decode(json_encode($bill_discount->applies_to), true);

                                if ($transaction->bill->type->is_period_monthly) {
                                    $month = $transaction->years . '-' . Str::padLeft($transaction->months, 2, '0');

                                    if (array_key_exists($month, $applies))
                                        $discount = $bill_discount->nominal;
                                } else {
                                    if (array_key_exists($transaction->semester, $applies))
                                        $discount = $bill_discount->nominal;
                                }
                            }
                        }

                        $total = $transaction->total - $discount;
                    }

                    array_push($details, (object) [
                        'id' => $transaction->id,
                        'name' => $bill_name,
                        'discount' => $discount,
                        'total' => $total,
                        'type' => (object) [
                            'id' => $transaction->bill->type->id,
                            'name' => $transaction->bill->type->name,
                        ],
                        'year' => (object) [
                            'id' => $transaction->bill->year->id,
                            'start' => $transaction->bill->year->start_year,
                            'end' => $transaction->bill->year->end_year,
                        ],
                    ]);
                }

                return (object) $details;
            }
        );
    }

    protected function isGetScholarship(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->donation > 0
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->value == TransactionStatus::Paid->value
        );
    }

    protected function isTagihan(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->flag->value == TransactionFlag::Tagihan->value
        );
    }

    protected function isSetorTabungan(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->flag->value == TransactionFlag::SetorTabungan->value
        );
    }

    protected function isPengambilanTabungan(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->flag->value == TransactionFlag::PengambilanTabungan->value
        );
    }

    protected function isTopupSaldo(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->flag->value == TransactionFlag::TopupSaldo->value
        );
    }

    protected function isMethodCash(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->payment_method->value == TransactionMethod::Cash->value
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->number = self::generateNumber($model->flag->value);
            $model->branch_id = Auth::user()->branch_id;
            $model->created_by = Auth::id();

            if (Auth::user()->role->value == UserRole::OrangTua->value) {
                $model->expired_at = date('Y-m-d H:i:s', strtotime('+2 day'));
                $model->expired_view_at = date('Y-m-d H:i:s', strtotime('+1 day'));
            }

            return $model;
        });

        self::updating(function ($model) {
            $model->updated_by = Auth::id();

            return $model;
        });

        self::deleting(function ($model) {
            $transaction = self::find($model->id);

            if ($transaction->unique_code > 0)
                TransactionPaymentCode::whereCode($transaction->unique_code)->delete();

            $transaction->update(['deleted_by' => Auth::id()]);

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
                'id_parent',
                'id_student',
                'id_donation',
                'number',
                'dates',
                'bills',
                'subtotal',
                'donation',
                'unique_code',
                'total',
                'payment_method',
                'paid_at',
                'paid_by',
                'status',
                'status_deposit',
                'flag',
                'branch_id',
            ]);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_student')->withTrashed();
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'id_parent')->withTrashed();
    }

    public function personResponsible()
    {
        return $this->belongsTo(User::class, 'id_parent')->withTrashed();
    }

    public function donatur()
    {
        return $this->belongsTo(Donation::class, 'id_donation')->withTrashed();
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'paid_by')->withTrashed();
    }

    public function scopeTagihan($query)
    {
        return $query->whereFlag(TransactionFlag::Tagihan->value);
    }

    public function scopeSetorTabungan($query)
    {
        return $query->whereFlag(TransactionFlag::SetorTabungan->value);
    }

    public function scopePengambilanTabungan($query)
    {
        return $query->whereFlag(TransactionFlag::PengambilanTabungan->value);
    }

    public function scopeTabungan($query)
    {
        return $query->where(function($q) {
            $q->whereFlag(TransactionFlag::SetorTabungan->value)
                ->orWhere('flag', TransactionFlag::PengambilanTabungan->value);
        });
    }

    public function scopeTopupSaldo($query)
    {
        return $query->whereFlag(TransactionFlag::TopupSaldo->value);
    }

    public function scopeBni($query)
    {
        return $query->wherePaymentMethod(TransactionMethod::BNI->value);
    }

    public function scopeBsi($query)
    {
        return $query->wherePaymentMethod(TransactionMethod::BSI->value);
    }

    public function scopePaid($query)
    {
        return $query->whereStatus(TransactionStatus::Paid->value);
    }

    public function scopeNotPaid($query)
    {
        return $query->whereStatus(TransactionStatus::NotPaid->value);
    }

    public function scopeNotDeposit($query)
    {
        return $query->whereStatusDeposit(TransactionDepositStatus::NotDeposit->value);
    }

    public function scopeNotDepositCode($query)
    {
        return $query->whereStatusDepositCode(TransactionDepositStatus::NotDeposit->value);
    }

    public static function generateNumber($flag)
    {
        switch ($flag) {
            case TransactionFlag::Tagihan->value:
            $prefix = 'TR';
            break;

            case TransactionFlag::SetorTabungan->value:
            $prefix = 'ST';
            break;

            case TransactionFlag::PengambilanTabungan->value:
            $prefix = 'PT';
            break;

            case TransactionFlag::TopupSaldo->value:
            $prefix = 'TS';
            break;

            default:
            $prefix = 'TR';
        }

        $count = self::whereFlag($flag)->whereMonth('dates', date('n'))->whereYear('dates', date('Y'))->withTrashed()->count();
        $sequence = Str::padLeft($count + 1, 4, '0');

        return $prefix . date('Ym') . $sequence;
    }
}
