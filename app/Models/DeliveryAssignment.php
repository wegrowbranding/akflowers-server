<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAssignment extends Model
{
    use HasFactory;

    protected $table = 'delivery_assignments';

    protected $fillable = [
        'order_id',
        'delivery_staff_id',
        'assigned_at',
        'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function deliveryStaff()
    {
        return $this->belongsTo(DeliveryStaff::class, 'delivery_staff_id');
    }

    public function proofs()
    {
        return $this->hasMany(DeliveryProof::class, 'assignment_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(DeliveryStatusHistory::class, 'assignment_id');
    }

    public function tracking()
    {
        return $this->hasMany(DeliveryTracking::class, 'assignment_id');
    }
}
