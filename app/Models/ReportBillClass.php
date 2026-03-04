<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportBillClass extends Model
{
    use HasFactory;

    protected $table = 'report_bill_class';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);
    }

    public function class()
    {
        return $this->belongsTo(Classroom::class, 'id_class')->withTrashed();
    }

    public function scopeToday($query)
    {
        return $query->whereDates(date('Y-m-d'));
    }
}
