<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_code',
        'branch_name',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'country',
        'phone_primary',
        'phone_secondary',
        'email_primary',
        'email_secondary',
        'gst_number',
        'license_number',
        'opening_date',
        'timezone',
        'currency',
        'status',
        'created_by',
        'deleted',
    ];
}
