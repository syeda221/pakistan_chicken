<?php
namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductBooking extends Model
{
    protected $fillable = [
        'customer', 'reference', 'product', 'product_code', 'brand', 'unit',
        'per_price', 'per_discount', 'qty', 'per_total', 'color',
        'total_amount_Words', 'total_bill_amount', 'total_extradiscount',
        'total_net', 'cash', 'card', 'change', 'total_items',
        'variant_id', 'total_pieces', 'total_meter', 'advance_payment', 'booking_date'
    ];

    public function customer_relation()
    {
        return $this->belongsTo(Customer::class, 'customer', 'id');
    }
     public function productt()
    {
        return $this->belongsTo(Product::class, 'product', 'id');
    }
}

