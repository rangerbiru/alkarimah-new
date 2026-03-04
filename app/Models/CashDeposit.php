<?php

namespace App\Models;

use App\Casts\Json;
use App\Enums\DepositStatus;
use App\Enums\TransactionFlag;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CashDeposit extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'cash_deposit';
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
            'transactions' => Json::class,
            'status' => DepositStatus::class,
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

    protected function startDate(): Attribute
    {
        return Attribute::make(
            set: fn($value) => date('Y-m-d', strtotime($value))
        );
    }

    protected function endDate(): Attribute
    {
        return Attribute::make(
            set: fn($value) => date('Y-m-d', strtotime($value))
        );
    }

    protected function transactionDetail(): Attribute
    {
        return Attribute::make(
            get: function() {
                $transactions = [];

                foreach ($this->transactions as $t) {
                    $trans = Transaction::select('id', 'id_student', 'id_parent', 'number', 'dates', 'subtotal', 'donation', 'unique_code',
                            'total', 'payment_method', 'flag', 'paid_at', 'bills', 'status')
                        ->with([
                            'student' => fn($query) => $query->select('id', 'nis', 'name'),
                            'parent' => fn($query) => $query->select('id', 'name', 'phone'),
                        ])
                        ->whereId($t)
                        ->first();

                    $student = ['nis' => '', 'name' => ''];
                    $parent = ['name' => '', 'phone' => ''];

                    if ($trans->flag->value == TransactionFlag::TopupSaldo->value)
                        $parent = ['name' => $trans->parent->name, 'phone' => $trans->parent->phone];
                    else
                        $student = ['nis' => $trans->student->nis, 'name' => $trans->student->name];

                    array_push($transactions, (object) [
                        'number' => $trans->number,
                        'dates' => $trans->dates,
                        'subtotal' => $trans->subtotal,
                        'donation' => $trans->donation,
                        'unique_code' => $trans->unique_code,
                        'total' => $trans->total,
                        'method' => $trans->method->name,
                        'method_id' => $trans->payment_method->value,
                        'flag_detail' => $trans->flag_detail,
                        'paid_at' => $trans->paid_at,
                        'parent' => (object) $parent,
                        'student' => (object) $student,
                        'detail' => $trans->bills_detail,
                    ]);
                }

                return (object) $transactions;
            }
        );
    }

    protected function isRejected(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->value == DepositStatus::Rejected->value
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
                'number',
                'dates',
                'transactions',
                'total',
                'status',
                'branch_id',
            ]);
    }

    public function verificator()
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function scopeWaiting($query)
    {
        return $query->whereStatus(DepositStatus::Waiting->value);
    }

    private static function generateNumber()
    {
        $count = self::whereMonth('dates', date('n'))->whereYear('dates', date('Y'))->count();
        $sequence = Str::padLeft($count + 1, 4, '0');

        return 'SK' . date('Ym') . $sequence;
    }
}
