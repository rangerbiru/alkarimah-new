<?php

namespace App\Models;

use App\Enums\SavingsMutationFlag;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class SavingsMutation extends Model
{
    use HasFactory;

    protected $table = 'savings_mutation';
    protected $guarded = ['id', 'updated_at', 'updated_by'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag' => SavingsMutationFlag::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function flagName(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->flag->value == SavingsMutationFlag::Deposit->value) ? __('label.savings_deposit') : __('label.savings_withdrawal')
        );
    }

    protected function isDeposit(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->flag->value == SavingsMutationFlag::Deposit->value
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::updating(function ($model) {
            $model->updated_by = Auth::id();

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

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction')->withTrashed();
    }

    public function withdrawal()
    {
        return $this->belongsTo(SavingsWithdrawal::class, 'id_transaction')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
