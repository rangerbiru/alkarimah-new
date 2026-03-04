<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportBillType extends Model
{
    use HasFactory;

    protected $table = 'report_bill_type';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);
    }

    public function type()
    {
        return $this->belongsTo(BillType::class, 'id_type')->withTrashed();
    }

    public function scopeToday($query)
    {
        return $query->whereDates(date('Y-m-d'));
    }
}
