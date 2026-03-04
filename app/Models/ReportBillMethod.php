<?php

namespace App\Models;

use App\Enums\TransactionMethod;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportBillMethod extends Model
{
    use HasFactory;

    protected $table = 'report_bill_method';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => TransactionMethod::class,
        ];
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);
    }

    public function scopeToday($query)
    {
        return $query->whereDates(date('Y-m-d'));
    }

    public function scopeCash($query)
    {
        return $query->whereMethod(TransactionMethod::Cash->value);
    }

    public function scopeTopupBalance($query)
    {
        return $query->whereMethod(TransactionMethod::TopupBalance->value);
    }

    public function scopeBni($query)
    {
        return $query->whereMethod(TransactionMethod::BNI->value);
    }

    public function scopeBsi($query)
    {
        return $query->whereMethod(TransactionMethod::BSI->value);
    }
}
