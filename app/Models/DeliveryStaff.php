<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryStaff extends Model
{
    use HasFactory;

    protected $table = 'delivery_staff';

    protected $fillable = [
        'staff_id',
        'vehicle_type',
        'vehicle_number',
        'is_available',
    ];

    public function staff()
    {
        return $this->belongsTo(BranchStaffUser::class, 'staff_id');
    }

    public function assignments()
    {
        return $this->hasMany(DeliveryAssignment::class, 'delivery_staff_id');
    }
}
