<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchStaffUser extends Model
{
    use HasFactory;

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
}
