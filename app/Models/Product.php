<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'product_name',
        'category_id',
        'price',
        'cost_price',
        'stock_quantity',
        'unit',
        'description',
        'status',
        'created_by',
        'deleted',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function media()   
    {
        return $this->belongsToMany(Media::class, 'product_images', 'product_id', 'media_id')
                    ->withPivot('id', 'is_primary');
    }
}
