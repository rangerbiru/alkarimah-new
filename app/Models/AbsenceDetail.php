<?php

namespace App\Models;

use App\Enums\AbsenceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AbsenceDetail extends Model
{
    use HasFactory;

    protected $table = 'absence_detail';
    protected $guarded = ['id'];
    protected $fillable = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AbsenceStatus::class,
        ];
    }

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: function() {
                switch ($this->status) {
                    case AbsenceStatus::Absen:
                    $badge = '<span class="badge bg-danger"><i class="ti ti-circle-x-filled"></i> ' . __('label.absent') . '</span>';
                    break;

                    case AbsenceStatus::Izin:
                    $badge = '<span class="badge bg-info"><i class="ti ti-briefcase-filled"></i> ' . __('label.permit') . '</span>';
                    break;

                    case AbsenceStatus::Sakit:
                    $badge = '<span class="badge bg-warning"><i class="ti ti-heartbeat"></i> ' . __('label.sick') . '</span>';
                    break;

                    default:
                    $badge = '<span class="badge bg-success"><i class="ti ti-circle-check-filled"></i> ' . __('label.present') . '</span>';
                }

                return $badge;
            }
        );
    }

    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: function() {
                switch ($this->status) {
                    case AbsenceStatus::Absen:
                    $name = __('label.absent');
                    break;

                    case AbsenceStatus::Izin:
                    $name = __('label.permit');
                    break;

                    case AbsenceStatus::Sakit:
                    $name = __('label.sick');
                    break;

                    default:
                    $name = __('label.present');
                }

                return $name;
            }
        );
    }

    public $timestamps = false;

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function absence()
    {
        return $this->belongsTo(Absence::class, 'id_absence');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_student')->withTrashed();
    }
}
