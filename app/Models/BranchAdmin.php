<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class BranchAdmin extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'branch_admin';

    protected $fillable = [
        'branch_id',
        'username',
        'password_hash',
        'full_name',
        'email',
        'phone',
        'last_login',
        'last_login_ip',
        'password_changed_at',
        'password_expires_at',
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
            'user_type' => 'branch_admin'
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
