<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parents extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'parent';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function genderName(): Attribute
    {
        return Attribute::make(
            get: fn() => __('label.' . $this->gender->value)
        );
    }

    protected function addressFull(): Attribute
    {
        return Attribute::make(
            get: function() {
                $address = $this->address;

                if (!empty($this->id_village)) {
                    $address .= ' Desa ' . $this->village->name;
                    $address .= ', Kec. ' . $this->village->parent->name;
                    $address .= ', ' . $this->village->parent->parent->name;
                    $address .= ', ' . $this->village->parent->parent->parent->name;
                }

                return (empty($address)) ? '-' : $address;
            }
        );
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope);

        self::creating(function ($model) {
            $model->branch_id = Auth::user()->branch_id;
            $model->created_by = Auth::id();

            return $model;
        });

        self::updating(function ($model) {
            $model->updated_by = Auth::id();

            return $model;
        });

        self::deleting(function ($model) {
            self::whereId($model->id)->update(['deleted_by' => Auth::id()]);

            return $model;
        });
    }


    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}")
            ->logOnly([
                'id_user',
                'id_village',
                'name',
                'phone',
                'gender',
                'address',
                'work',
                'income',
                'balance',
                'branch_id',
            ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function village()
    {
        return $this->belongsTo(Region::class, 'id_village');
    }

    public function relation()
    {
        return $this->belongsTo(self::class, 'id_relation');
    }
}
