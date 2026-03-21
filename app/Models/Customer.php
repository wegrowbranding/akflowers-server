<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'full_name',
        'email',
        'phone',
        'password_hash',
        'profile_image',
        'gender',
        'date_of_birth',
        'status',
        'deleted',
    ];

    protected $hidden = [
        'password_hash',
    ];
}
