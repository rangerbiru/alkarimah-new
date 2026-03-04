<?php

namespace App\Models;

use App\Casts\Json;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payroll';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'allowance_detail' => Json::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function allowanceDetails(): Attribute
    {
        return Attribute::make(
            get: function() {
                $allowance = [
                    'structural' => [],
                    'liability' => [],
                    'performance' => [],
                ];

                if (!empty($this->allowance_detail->structural)) {
                    foreach ($this->allowance_detail->structural as $s) {
                        $allo = Allowance::select('name')->whereId($s->id)->first();

                        array_push($allowance['structural'], [
                            'id' => $s->id,
                            'name' => $allo->name,
                            'nominal' => $s->nominal,
                        ]);
                    }
                }

                if (!empty($this->allowance_detail->liability)) {
                    foreach ($this->allowance_detail->liability as $l) {
                        $allo = Allowance::select('name')->whereId($l->id)->first();

                        array_push($allowance['liability'], [
                            'id' => $l->id,
                            'name' => $allo->name,
                            'nominal' => $l->nominal,
                        ]);
                    }
                }

                if (!empty($this->allowance_detail->performance)) {
                    foreach ($this->allowance_detail->performance as $p) {
                        $allo = Allowance::select('name')->whereId($p->id)->first();

                        array_push($allowance['performance'], [
                            'id' => $p->id,
                            'name' => $allo->name,
                            'nominal' => $p->nominal,
                        ]);
                    }
                }

                return json_decode(json_encode($allowance));
            }
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }
}
