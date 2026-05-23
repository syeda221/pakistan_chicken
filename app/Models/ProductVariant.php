<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'size_value'      => 'float',
        'price'           => 'float',
        'wholesale_price' => 'float',
        'cost_price'      => 'float',
        'stock_qty'       => 'float',
        'is_default'      => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Convert kg to grams automatically
     * If size_unit is "kg", return grams equivalent
     */
    public function getGramsAttribute()
    {
        if ($this->size_unit === 'kg') {
            return $this->size_value * 1000;
        }
        return $this->size_value;
    }

    /**
     * Get display label automatically
     * e.g. "1 Pound - Rs 1000" or "500g - Rs 800"
     */
    public function getDisplayLabelAttribute()
    {
        $label = $this->size_label ?? $this->variant_name;
        return $label . ' - Rs ' . number_format($this->price);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'variant_id');
    }
}
