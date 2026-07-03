<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingWaKey extends Model
{
    protected $fillable = ['name', 'key', 'phone', 'description'];
}
