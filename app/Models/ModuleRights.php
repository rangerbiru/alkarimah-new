<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleRights extends Model
{
    use HasFactory;

    protected $table = 'module_rights';
    protected $guarded = ['id'];
    protected $fillable = [];
}
