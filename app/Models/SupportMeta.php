<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMeta extends Model
{
    use HasFactory;

    protected $table = 'support_meta';
    
    public $timestamps = false;

    protected $fillable = [
        'meta_key',
        'meta_value',
    ];
}
