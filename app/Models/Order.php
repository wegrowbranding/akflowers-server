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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function deliveryAssignments()
    {
        return $this->hasMany(DeliveryAssignment::class, 'order_id');
    }

    public function history()
    {
        return $this->hasManyThrough(DeliveryStatusHistory::class, DeliveryAssignment::class, 'order_id', 'assignment_id')
            ->orderBy('id', 'desc');
    }
}
