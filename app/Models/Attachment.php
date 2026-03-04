<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachment';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            Storage::delete($attachment->path . $attachment->filename_hashed);
        });
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function fileIcon(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->extension == 'pdf') ? 'fa-regular fa-file-pdf' : 'fa-regular fa-file-image'
        );
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }
}
