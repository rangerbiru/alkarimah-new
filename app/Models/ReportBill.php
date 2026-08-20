<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportBill extends Model
{
    use HasFactory;

    protected $table = 'report_bill';

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

    protected function levelName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $levels = [
                    1 => 'TK A',
                    2 => 'TK B',
                    3 => 'Kelas 1',
                    4 => 'Kelas 2',
                    5 => 'Kelas 3',
                    6 => 'Kelas 4',
                    7 => 'Kelas 5',
                    8 => 'Kelas 6',
                    9 => 'Kelas 7',
                    10 => 'Kelas 8',
                    11 => 'Kelas 9',
                    12 => 'Kelas 10',
                    13 => 'Kelas 11',
                    14 => 'Kelas 12',
                ];

                return $levels[$this->level] ?? 'Kelas '.$this->level;
            }
        );
    }
}
