<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    protected $table = 'sessions';
    public $timestamps = false; // Using custom timestamp fields in schema

    protected $fillable = [
        'user_type',
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'login_time',
        'last_activity',
        'expiry_time',
        'is_active',
        'logout_time'
    ];

    /**
     * Get the user for the session.
     */
    public function user()
    {
        if ($this->user_type === 'super_admin') {
            return $this->belongsTo(SuperAdminUser::class, 'user_id');
        }
        // Handle other types later
        return null;
    }
}
