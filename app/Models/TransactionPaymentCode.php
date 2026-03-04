<?php

namespace App\Models;

use App\Enums\PaymentCodeStatus;
use App\Enums\TransactionFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TransactionPaymentCode extends Model
{
    use HasFactory;

    protected $table = 'transaction_payment_code';
    protected $guarded = ['id'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentCodeStatus::class,
            'flag' => TransactionFlag::class,
        ];
    }

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->expired_at = date('Y-m-d H:i:s', strtotime('+1 day'));
            $model->created_by = Auth::id();

            return $model;
        });
    }

    public function scopeNotUsed($query)
    {
        return $query->whereStatus(0);
    }

    public static function generate($flag)
    {
        $paycode = self::select('code')
            ->whereCreatedBy(Auth::id())
            ->whereFlag($flag)
            ->notUsed()
            ->first();

        if (!empty($paycode))
            return $paycode->code;

        $code = rand(100, 999);
        $count = self::whereCode($code)->count();

        if ($count > 0) {
            $code = rand(100, 999);
            $count = self::whereCode($code)->count();

            if ($count > 0) {
                $code = rand(100, 999);
                $count = self::whereCode($code)->count();
            }
        }

        self::create([
            'code' => $code,
            'flag' => $flag
        ]);

        return $code;
    }
}
