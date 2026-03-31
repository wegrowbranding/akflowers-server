<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTracking extends Model
{
    use HasFactory;

    protected $table = 'delivery_tracking';
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'latitude',
        'longitude',
        'recorded_at',
    ];

    public function assignment()
    {
        return $this->belongsTo(DeliveryAssignment::class, 'assignment_id');
    }
}
