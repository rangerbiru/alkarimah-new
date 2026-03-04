<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItems extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    protected $fillable = [
        'asset_id',
        'inventory_code',
        'name',
        'category',
        'brand',
        'specification',
        'location',
        'unit',
        'responsible_person',
        'acquisition_date',
        'source_funding',
        'acquisition_price',
        'quantity',
        'total_acquisition_value',
        'residual_value',
        'useful_life_years',
        'depreciation_method',
        'depreciation_amount_per_year',
        'depreciation_amount_per_month',
        'used_until_date',
        'accumulated_depreciation',
        'book_value',
        'condition',
        'status',
        'serial_number',
        'documents',
        'description',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_price' => 'decimal:2',
        'total_acquisition_value' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'depreciation_amount_per_year' => 'decimal:2',
        'depreciation_amount_per_month' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'quantity' => 'integer',
        'useful_life_years' => 'integer',
    ];

    public function location()
    {
        return $this->belongsTo(LocationMaster::class, 'location');
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category');
    }

    public function getUsedUntilDateAttribute($value)
    {
        return $value;
    }

    public function setUsedUntilDateAttribute($value)
    {
        $this->attributes['used_until_date'] = str_replace(' Bulan', '', $value);
    }
}
