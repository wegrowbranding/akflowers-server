<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_type',
        'user_id',
        'username',
        'branch_id',
        'action_type',
        'action_description',
        'ip_address',
        'user_agent',
        'request_data',
        'response_status',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'request_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model to automatically set created_at.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?: $model->freshTimestamp();
        });
    }
}
