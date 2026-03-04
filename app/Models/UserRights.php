<?php

namespace App\Models;

use App\Casts\Json;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class UserRights extends Model
{
    use HasFactory;

    protected $table = 'user_rights';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'actions' => Json::class,
            'is_parent' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->created_by = (Auth::check()) ? Auth::id() : 0;

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

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }
}
