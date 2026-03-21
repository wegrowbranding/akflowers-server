<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'category',
        'module',
        'action',
        'display_name',
        'key_name',
        'description',
        'status',
        'is_system',
        'created_by',
        'sort_order',
    ];
}
