<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRole extends Model
{
    use HasFactory;

    protected $table = 'branch_roles';

    protected $fillable = [
        'branch_id',
        'role_name',
        'role_description',
        'permission_id',
        'is_default',
        'status',
        'created_by',
        'deleted',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
