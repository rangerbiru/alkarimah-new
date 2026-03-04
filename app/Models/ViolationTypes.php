<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationTypes extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'violation_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'group',
        'impact_level',
        'description',
        'points',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'points' => 0,
        'status' => 'Active',
    ];

    /**
     * Get the route key for the model (use 'code' in URLs).
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /*
     * Scopes
     */

    /**
     * Scope a query to only include active violation types.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to only include inactive violation types.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'Inactive');
    }

    /**
     * Scope a query by impact level.
     */
    public function scopeByImpactLevel($query, string $level)
    {
        return $query->where('impact_level', $level);
    }

    /**
     * Scope a query by group.
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Helper: Calculate total points from a collection.
     */
    public static function calculateTotalPoints($violations): int
    {
        return $violations->sum('points');
    }
}
