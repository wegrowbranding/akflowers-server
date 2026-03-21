<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
    ];

    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }
}
