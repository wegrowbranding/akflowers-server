<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class BranchStaffUser extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'branch_staff_users';

    protected $fillable = [
        'branch_id',
        'username',
        'email',
        'password_hash',
        'full_name',
        'phone',
        'profile_image',
        'role_id',
        'employee_id',
        'date_of_joining',
        'date_of_birth',
        'address',
        'last_login',
        'last_login_ip',
        'password_changed_at',
        'login_attempts',
        'locked_until',
        'status',
        'created_by',
        'deleted',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'user_type' => 'branch_staff' // I'll also add a relation later if needed
        ];
    }

    public function role()
    {
        return $this->belongsTo(BranchRole::class, 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
