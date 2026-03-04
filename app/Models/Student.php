<?php

namespace App\Models;

use App\Casts\Json;
use App\Enums\UserRole;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'student';
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
            'religion' => Religion::class,
            'beasiswa' => 'boolean',
            'exculs' => Json::class,
            'status' => 'boolean',
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function birthdate(): Attribute
    {
        return Attribute::make(
            set: fn($value) => date('Y-m-d', strtotime($value))
        );
    }

    protected function entryDate(): Attribute
    {
        return Attribute::make(
            set: fn($value) => date('Y-m-d', strtotime($value))
        );
    }

    protected function genderName(): Attribute
    {
        return Attribute::make(
            get: fn() => __('label.' . $this->gender->value)
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status) ? '<span class="badge bg-success">' . __('label.active') . '</span>' : '<span class="badge bg-danger">' . __('label.not_active') . '</span>'
        );
    }

    protected function exculList(): Attribute
    {
        return Attribute::make(
            get: function() {
                $list = [];

                foreach ($this->exculs as $e) {
                    $excul = Excul::select('name')->whereId($e)->first();

                    array_push($list, (object) [
                        'id' => $e,
                        'name' => $excul->name
                    ]);
                }

                return $list;
            }
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (empty($this->file_photo))
                    $url = ($this->gender == Gender::Male) ? asset('images/avatar-male.png') : asset('images/avatar-female.png');
                else
                    $url = route('attachment.get', $this->photo->encrypted_id);

                return $url;
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
                'id_parent', 'id_class', 'id_asrama', 'id_halaqah', 'nis', 'nis_local', 'name', 'gender',
                'nisn', 'nik', 'birthdate', 'religion', 'address', 'school_from', 'child', 'card_number',
                'entry_date', 'spp', 'location', 'file_photo', 'bills', 'balance_savings', 'beasiswa',
                'status', 'branch_id',
            ]);
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'id_parent')->withTrashed();
    }

    public function class()
    {
        return $this->belongsTo(Classroom::class, 'id_class')->withTrashed();
    }

    public function asrama()
    {
        return $this->belongsTo(Asrama::class, 'id_asrama')->withTrashed();
    }

    public function halaqah()
    {
        return $this->belongsTo(Halaqah::class, 'id_halaqah')->withTrashed();
    }

    public function photo()
    {
        return $this->belongsTo(Attachment::class, 'file_photo');
    }

    public function scopeActive($query)
    {
        return $query->whereStatus(true);
    }
}
