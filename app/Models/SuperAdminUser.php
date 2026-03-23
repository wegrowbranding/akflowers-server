<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class SuperAdminUser extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'super_admin_users';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'full_name',
        'phone',
        'profile_image',
        'role_id',
        'status',
        'created_by',
        'deleted',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_type' => 'super_admin'
        ];
    }
}
