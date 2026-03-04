<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportStudentLevel extends Model
{
    use HasFactory;

    protected $table = 'report_student_level';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);
    }
}
