<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends BaseController
{
    /**
     * Get home dashboard data.
     * Includes categories and featured/recent products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Get top-level categories (featured or to display on home)
        // Adjust condition according to database specifics e.g., show_in_menu
        $categories = Category::where('deleted', 0)
            ->where('status', 'active')
            ->orderBy('display_order', 'asc')
            ->get();

        // Get new/recent products
        $recentProducts = Product::with(['category', 'media'])
            ->where('deleted', 0)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get products by some special condition (e.g. random or featured) 
        // We'll use random order here to act as "Featured" or "Popular"
        $featuredProducts = Product::with(['category', 'media'])
            ->where('deleted', 0)
            ->where('status', 'active')
            ->inRandomOrder()
            ->take(10)
            ->get();

        return $this->sendResponse([
            'banners' => [], // Add banner logic if you create a banners table
            'categories' => $categories,
            'recent_products' => $recentProducts,
            'featured_products' => $featuredProducts
        ], 'Home dashboard retrieved successfully.');
    }
}
