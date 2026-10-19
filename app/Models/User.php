<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Constants\Modules;
use App\Constants\EducationLevel as EducationLevelMap;
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
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    private ?array $moduleIdsCache = null;

    // role, branch, and auth tokens are never mass assignable: set them explicitly (forceCreate/forceFill)
    protected $guarded = [
        'id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by',
        'role', 'linked_user_id', 'branch_id', 'token', 'token_expired_at', 'remember_token', 'email_verified_at', 'lastlogin_at',
    ];

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

    // Alias: the old parent account merged into an employee account. It can still log in, but the
    // session always belongs to the employee account (see ResolveAlias)
    public function linkedTo()
    {
        return $this->belongsTo(self::class, 'linked_user_id')->withoutGlobalScopes()->withTrashed();
    }

    public function aliases()
    {
        return $this->hasMany(self::class, 'linked_user_id');
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn () => Crypt::encrypt($this->id)
        );
    }

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn () => asset('images/avatar-'.$this->gender->value.'.png')
        );
    }

    protected function genderName(): Attribute
    {
        return Attribute::make(
            get: fn () => __('label.'.$this->gender->value)
        );
    }

    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn () => Auth::user()->role == UserRole::Admin
        );
    }

    protected function isBendahara(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hasAccess('bendahara')
        );
    }

    protected function isKasir(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hasAccess('kasir')
        );
    }

    protected function isKasirUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hasAccess('kasir-unit')
        );
    }

    protected function isOrangTua(): Attribute
    {
        return Attribute::make(
            get: fn () => Auth::user()->role == UserRole::OrangTua
        );
    }

    // Nonaktif hanya bila employee.status bernilai 0 secara eksplisit. Nilai kosong (null) atau tidak dikenal
    // bukan "nonaktif": kesalahan input data tidak boleh mengubah pegawai menjadi orang tua saja
    protected function isInactiveEmployee(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->employee !== null && $this->employee->status === false
        );
    }

    // Has an employee row (not soft-deleted). Cached per instance: checked on every request by Role
    protected function isEmployee(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->employee()->withoutTrashed()->exists()
        )->shouldCache();
    }

    // Has a parent row (not soft-deleted)
    protected function isParent(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->parent()->withoutTrashed()->exists()
        )->shouldCache();
    }

    // One account that is both an employee and a parent: gets both menus and both route groups.
    // An inactive employee who is also a parent is a parent only (see acts_as_parent_only)
    protected function hasDualRole(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_employee && $this->is_parent && ! $this->is_inactive_employee
        )->shouldCache();
    }

    // Inactive employee (staff role) who is also a parent: treated as a parent, never as staff
    protected function actsAsParentOnly(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role != UserRole::OrangTua && $this->is_inactive_employee && $this->is_parent
        )->shouldCache();
    }

    // Role whose sidebar / dashboard is rendered. A dual-role account registered as a parent
    // gets the employee menu (the parent menu is added on top); everyone else keeps their role
    protected function menuRole(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->acts_as_parent_only
                ? UserRole::OrangTua
                : (($this->role == UserRole::OrangTua && $this->has_dual_role) ? UserRole::Pegawai : $this->role)
        );
    }

    protected function isPegawai(): Attribute
    {
        return Attribute::make(
            get: fn () => Auth::user()->role == UserRole::Pegawai
        );
    }

    protected function isKepalaSekolah(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role == UserRole::KepalaSekolah
        );
    }

    /**
     * Akses setara pimpinan: role kepala sekolah, atau pegawai yang tergabung
     * di grup absensi pimpinan (position 10).
     */
    public function hasPimpinanAccess(): bool
    {
        if ($this->hasAccess('kepala-sekolah')) {
            return true;
        }

        if (! $this->employee) {
            return false;
        }

        return AttendanceGroupMembers::where('employee_id', $this->employee->id)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if (Auth::check()) {
                $model->branch_id = Auth::user()->branch_id;
                $model->created_by = Auth::id();
            } else {
                if (empty($model->branch_id)) {
                    $model->branch_id = 0;
                }

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
            ->setDescriptionForEvent(fn (string $eventName) => "This model has been {$eventName}")
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

    /**
     * Jenjang (TK/SD/SMP/SMA) yang dipegang kepala sekolah.
     */
    public function educationLevels(): HasMany
    {
        return $this->hasMany(UserEducationLevel::class);
    }

    /**
     * Nilai jenjang yang dipegang, misal ['sd', 'smp'] — siap dipakai untuk
     * filter whereIn('level_education', ...) pada data kelas/siswa.
     */
    public function educationLevelValues(?int $moduleId = null): array
    {
        return $this->educationLevels
            ->filter(fn ($e) => $e->module_id === $moduleId)
            ->map(fn ($e) => $e->level_education->value)
            ->values()
            ->all();
    }

    /**
     * Id kelas pada jenjang kepala sekolah: dari akun kepala-sekolah (jenjang milik akun)
     * atau pegawai pemegang modul Kepala Sekolah (jenjang milik modul). Selain itu null.
     */
    public function educationLevelClassIds(): ?array
    {
        if ($this->role == UserRole::KepalaSekolah) {
            $levels = $this->educationLevelValues();
        } elseif ($this->hasAccess('kepala-sekolah')) {
            $levels = $this->educationLevelValues(Modules::KepalaSekolah);
        } else {
            return null;
        }

        return Classroom::whereIn('level_education', $levels)->pluck('id')->all();
    }

    public function allowedClassIds(): ?array
    {
        $ids = null;

        if ($this->hasAccess('kepala-sekolah')) {
            $ids = $this->educationLevelClassIds();
        }

        if ($this->hasAccess('wali-kelas')) {
            $ids = array_values(array_unique(array_merge(
                $ids ?? [],
                Classroom::where('id_wali_kelas', $this->id)->pluck('id')->all()
            )));
        }

        return $ids;
    }

    /**
     * Id kelas yang boleh diakses Kasir Unit (null = tanpa batas).
     *
     * - Kasir / modul Kasir: null (Kasir menang atas Kasir Unit).
     * - Akun kasir-unit lama: jenjang di baris module_id NULL; belum punya baris = null.
     * - Pegawai pemegang modul Kasir Unit: jenjang modulnya; kosong = [] (tolak semua).
     * - Selain itu: null (aturan ini tidak berlaku).
     */
    public function kasirUnitClassIds(): ?array
    {
        if ($this->hasAccess('kasir')) {
            return null;
        }

        if ($this->role === UserRole::KasirUnit) {
            $levels = $this->educationLevelValues();

            if (empty($levels)) {
                return null;
            }
        } elseif ($this->hasAccess('kasir-unit')) {
            $levels = $this->educationLevelValues(Modules::KasirUnit);
        } else {
            return null;
        }

        return Classroom::whereIn('level_education', $levels)->pluck('id')->all();
    }

    /**
     * Kunci laporan (EducationLevel::Classes) untuk kelas yang boleh diakses Kasir Unit
     * (null = tanpa batas).
     */
    public function kasirUnitReportLevels(): ?array
    {
        $classIds = $this->kasirUnitClassIds();

        if ($classIds === null) {
            return null;
        }

        return Classroom::whereIn('id', $classIds)
            ->get(['level_education', 'level_class'])
            ->map(fn ($c) => EducationLevelMap::Classes[$c->level_education->value.'.'.$c->level_class] ?? null)
            ->filter(fn ($l) => $l !== null)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Cek id kelas terhadap daftar id kelas yang diizinkan (null = semua boleh).
     */
    public static function classIdAllowed($classId, ?array $allowedClassIds): bool
    {
        return $allowedClassIds === null
            || in_array((string) $classId, array_map('strval', $allowedClassIds), true);
    }

    /**
     * Ganti seluruh jenjang user dengan daftar yang diberikan (seperti sync).
     */
    public function syncEducationLevels(array $levels, ?int $moduleId = null): void
    {
        $this->educationLevels()->where('module_id', $moduleId)->delete();

        foreach (array_unique($levels) as $level) {
            $this->educationLevels()->create(['level_education' => $level, 'module_id' => $moduleId]);
        }

        $this->unsetRelation('educationLevels');
    }

    /**
     * User yang boleh dipilih sebagai wali kelas: akun wali-kelas, atau pegawai
     * pemegang modul Wali Kelas.
     */
    public function scopeWaliKelasCandidates($query)
    {
        return $query->where(function ($q) {
            $q->where('role', UserRole::WaliKelas->value)
                ->orWhere(function ($q) {
                    $q->where('role', UserRole::Pegawai->value)
                        ->whereIn('id', ModuleRights::select('id_user')->where('id_module', Modules::WaliKelas));
                });
        });
    }

    public function scopePenanggungJawabTabungan($query)
    {
        return $query->whereRole(UserRole::PenanggungJawabTabungan->value);
    }

    /**
     * True bila role user sama dengan $access, atau user berrole pegawai yang
     * memegang modul untuk akses TU tersebut (App\Constants\Modules::ByAccess).
     * Admin hanya lolos untuk akses 'admin'.
     */
    public function hasAccess(string $access): bool
    {
        if ($this->role->value === $access) {
            return true;
        }

        if ($this->role !== UserRole::Pegawai) {
            return false;
        }

        $module = Modules::ByAccess[$access] ?? null;

        return $module !== null && in_array($module, $this->moduleIds(), true);
    }

    /**
     * ID modul (module_rights) yang dipegang user, di-cache per instance.
     *
     * @return array<int, int>
     */
    public function moduleIds(): array
    {
        return $this->moduleIdsCache ??= ModuleRights::where('id_user', $this->id)
            ->pluck('id_module')
            ->map(fn ($id) => (int) $id)
            ->all();
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

    public function hasModuleAccess($moduleId)
    {
        return ModuleRights::where('id_user', $this->id)
            ->where('id_module', $moduleId)
            ->exists();
    }
}
