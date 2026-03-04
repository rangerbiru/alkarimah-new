<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportStudent extends Model
{
    use HasFactory;

    protected $table = 'report_student';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];
}
