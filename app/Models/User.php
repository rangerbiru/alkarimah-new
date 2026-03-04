<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\Gender;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, LogsActivity;

    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'gender' => Gender::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn() => asset('images/avatar-' . $this->gender->value . '.png')
        );
    }

    protected function genderName(): Attribute
    {
        return Attribute::make(
            get: fn() => __('label.' . $this->gender->value)
        );
    }

    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn() => Auth::user()->role == UserRole::Admin
        );
    }

    protected function isBendahara(): Attribute
    {
        return Attribute::make(
            get: fn() => Auth::user()->role == UserRole::Bendahara
        );
    }

    protected function isKasir(): Attribute
    {
        return Attribute::make(
            get: fn() => Auth::user()->role == UserRole::Kasir
        );
    }

    protected function isOrangTua(): Attribute
    {
        return Attribute::make(
            get: fn() => Auth::user()->role == UserRole::OrangTua
        );
    }

    protected function isPegawai(): Attribute
    {
        return Attribute::make(
            get: fn() => Auth::user()->role == UserRole::Pegawai
        );
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if (Auth::check()) {
                $model->branch_id = Auth::user()->branch_id;
                $model->created_by = Auth::id();
            } else {
                if (empty($model->branch_id))
                    $model->branch_id = 0;

                $model->created_by = 0;
            }

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
                'name',
                'email',
                'password',
                'role',
                'phone',
                'gender',
                'branch_id',
            ]);
    }

    public function parent()
    {
        return $this->hasOne(Parents::class, 'id_user', 'id')->withTrashed();
    }

    public function class()
    {
        return $this->hasOne(Classroom::class, 'id_wali_kelas', 'id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'id_user', 'id')->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function scopePenanggungJawabTabungan($query)
    {
        return $query->whereRole(UserRole::PenanggungJawabTabungan->value);
    }

    public function hasRole($role)
    {
        return ($this->role->value == $role) ? true : false;
    }

    public function managedPermitGroups(): HasMany
    {
        return $this->hasMany(StudentPermitGroup::class, 'ustadz_id');
    }

    public function submittedPermits(): HasMany
    {
        return $this->hasMany(StudentPermit::class, 'student_id');
    }

    public function approvedPermits(): HasMany
    {
        return $this->hasMany(StudentPermit::class, 'approved_by');
    }
}
