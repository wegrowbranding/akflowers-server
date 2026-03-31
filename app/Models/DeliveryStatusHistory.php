<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'delivery_status_histories';
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'status',
        'remarks',
        'created_at',
    ];

    public function assignment()
    {
        return $this->belongsTo(DeliveryAssignment::class, 'assignment_id');
    }
}
