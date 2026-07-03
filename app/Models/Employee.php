<?php

namespace App\Models;

use App\Casts\Json;
use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Notifiable;

    protected $table = 'employee';
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
            'marital_status' => 'boolean',
            'status' => 'boolean',
            'status_employment' => EmployeeStatus::class,
            'status_teacher' => 'boolean',
            'salary_allowance_detail' => Json::class
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function maritalStatusName(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->marital_status) ? __('label.married') : __('label.not_married')
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->status) ? '<span class="badge bg-success">' . strtoupper(__('label.active')) . '</span>' : '<span class="badge bg-danger">' . strtoupper(__('label.not_active')) . '</span>'
        );
    }

    protected function statusEmploymentName(): Attribute
    {
        return Attribute::make(
            get: function () {
                switch ($this->status_employment) {
                    case EmployeeStatus::Honorer:
                        $name = __('label.honorer');
                        break;

                    case EmployeeStatus::Pengabdian:
                        $name = __('label.honorer');
                        break;

                    default:
                        $name = __('label.permanent');
                }

                return $name;
            }
        );
    }

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn() => asset('images/avatar-' . $this->gender->value . '.png')
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
                'id_position',
                'id_user',
                'salary',
                'salary_allowance',
                'salary_allowance_detail',
                'nip',
                'nik',
                'name',
                'phone',
                'email',
                'address',
                'education',
                'marital_status',
                'placement',
                'task_main',
                'task_additional',
                'status',
                'status_employment',
                'status_teacher',
                'branch_id',
                'birth_date',
                'birth_place',
                'religion',
                'ethnicity',
                'nationality',
                'rt',
                'rw',
                'village',
                'district',
                'city',
                'province',
                'postal_code',
                'home_phone',
                'identity_number',
                'npwp_number',
                'bpjs_work_number',
                'bpjs_health_number'
            ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'id_position');
    }

    public function scopeActive($query)
    {
        return $query->whereStatus(true);
    }

    public function member()
    {
        return $this->belongsTo(AttendanceGroupMembers::class, 'id');
    }

    public function familyMembers()
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function educations()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function nonFormalEducations()
    {
        return $this->hasMany(EmployeeNonFormalEducation::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(EmployeeWorkExperience::class);
    }

    public function organizations()
    {
        return $this->hasMany(EmployeeOrganization::class);
    }

    public function awards()
    {
        return $this->hasMany(EmployeeAward::class);
    }

    public function languages()
    {
        return $this->hasMany(EmployeeLanguage::class);
    }

    public function computerSkills()
    {
        return $this->hasMany(EmployeeComputerSkill::class);
    }

    public function healthRecords()
    {
        return $this->hasMany(EmployeeHealthRecord::class);
    }

    public function previousCompanies()
    {
        return $this->hasMany(EmployeePreviousCompany::class);
    }

    public function additionalSkills()
    {
        return $this->hasMany(EmployeeAdditionalSkill::class);
    }

    public function provinceData()
    {
        return $this->belongsTo(Region::class, 'province', 'id');
    }

    public function cityData()
    {
        return $this->belongsTo(Region::class, 'city', 'id');
    }

    public function districtData()
    {
        return $this->belongsTo(Region::class, 'district', 'id');
    }

    public function villageData()
    {
        return $this->belongsTo(Region::class, 'village', 'id');
    }
}
