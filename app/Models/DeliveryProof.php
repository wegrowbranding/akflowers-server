<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryProof extends Model
{
    use HasFactory;

    protected $table = 'delivery_proofs';
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'proof_type',
        'otp_code',
        'image_path',
        'verified',
        'created_at',
    ];

    public function assignment()
    {
        return $this->belongsTo(DeliveryAssignment::class, 'assignment_id');
    }
}
