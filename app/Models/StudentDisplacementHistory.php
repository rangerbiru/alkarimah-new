<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class StudentDisplacementHistory extends Model
{
    use HasFactory;

    protected $table = 'student_displacement_history';
    protected $guarded = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by'];
    protected $fillable = [];

    protected function encryptedId(): Attribute
    {
        return Attribute::make(
            get: fn() => Crypt::encrypt($this->id)
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
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereId(Crypt::decrypt($value))->firstOrFail();
    }

    public function classBefore()
    {
        return $this->belongsTo(Classroom::class, 'before_class_id')->withTrashed();
    }

    public function classAfter()
    {
        return $this->belongsTo(Classroom::class, 'after_class_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
