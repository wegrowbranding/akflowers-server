<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'branch_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'payment_status',
        'order_status',
        'payment_method',
        'address_id',
    ];

    const CREATED_AT = 'placed_at';
    const UPDATED_AT = null;

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
