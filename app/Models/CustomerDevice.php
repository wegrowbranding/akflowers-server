<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDevice extends Model
{
    use HasFactory;

    protected $table = 'customer_devices';

    protected $fillable = [
        'customer_id',
        'device_id',
        'device_name',
        'device_type',
        'fcm_token',
        'app_version',
        'os_version',
        'ip_address',
        'user_agent',
        'is_active',
        'last_login_at',
        'last_used_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
