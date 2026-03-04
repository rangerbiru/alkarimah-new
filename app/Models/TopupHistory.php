<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class TopupHistory extends Model
{
    use HasFactory;

    protected $table = 'topup_history';
    protected $guarded = ['id', 'created_at', 'created_by'];
    protected $fillable = [];

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

        self::creating(function ($model) {
            $model->created_at = date('Y-m-d H:i:s');
            $model->created_by = (Auth::check()) ? Auth::id() : 0;

            return $model;
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'id_parent')->withTrashed();
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction')->withTrashed();
    }
}
