<?php

namespace App\Models;

use App\Casts\Json;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class DonationHistory extends Model
{
    use HasFactory;

    protected $table = 'donation_history';
    protected $guarded = ['id', 'created_at'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'description' => Json::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->branch_id = Auth::user()->branch_id;
            $model->created_at = date('Y-m-d H:i:s');
            $model->created_by = Auth::id();

            return $model;
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function donation()
    {
        return $this->belongsTo(Donation::class, 'id_donation');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_student');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction');
    }
}
