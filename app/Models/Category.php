<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'parent_category_id',
        'description',
        'banner_image',
        'logo_image',
        'banner_alt_text',
        'logo_alt_text',
        'display_order',
        'show_in_menu',
        'status',
        'created_by',
        'deleted',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    /**
     * Get the subcategories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_category_id');
    }

    /**
     * Get the products for the category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
