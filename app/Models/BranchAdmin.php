<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchAdmin extends Model
{
    use HasFactory;

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
}
