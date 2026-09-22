<?php

namespace App\Models;

use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Model;

class UserEducationLevel extends Model
{
    protected $fillable = ['user_id', 'level_education'];

    protected function casts(): array
    {
        return [
            'level_education' => EducationLevel::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
