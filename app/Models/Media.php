<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'file_name',
        'original_name',
        'file_type',
        'mime_type',
        'file_size',
        'extension',
        'file_path',
        'file_url',
        'uploaded_by',
        'status',
        'deleted',
    ];

    /**
     * Get the user who uploaded the media.
     */
    public function user()
    {
        return $this->belongsTo(SuperAdminUser::class, 'uploaded_by');
    }
}
