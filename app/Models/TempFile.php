<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class TempFile extends Model
{
    use HasFactory;

    protected $table = 'temp_files';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            Storage::delete('tmp/' . $model->file);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }
}
