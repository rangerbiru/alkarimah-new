<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MootaLog extends Model
{
    use HasFactory;

    protected $table = 'moota_log';
    protected $guarded = ['id', 'created_at'];
    protected $fillable = [];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->created_at = date('Y-m-d H:i:s');

            return $model;
        });
    }
}
