<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    /**
     * Product Listing (Category / All Products) with Search, Filters & Sorting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'newest'); // default sorting

        $query = Product::with(['category', 'media'])->where('deleted', 0)->where('status', 'active');

        // Search Query
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('product_code', 'LIKE', "%{$search}%");
            });
        }

        // Category Filter
        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        // Price Range Filters
        if (!empty($minPrice)) {
            $query->where('price', '>=', $minPrice);
        }
        if (!empty($maxPrice)) {
            $query->where('price', '<=', $maxPrice);
        }

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $products->total(),
            'limit' => $products->perPage(),
            'page' => $products->currentPage(),
            'data' => $products->items()
        ], 'Products retrieved successfully.');
    }

    /**
     * Product Details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function details($id): JsonResponse
    {
        $product = Product::with(['category', 'media'])
            ->where('id', $id)
            ->where('deleted', 0)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return $this->sendError('Product not found or inactive.', [], 404);
        }

        // Find related products (e.g., same category)
        $relatedProducts = Product::with(['media'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('deleted', 0)
            ->where('status', 'active')
            ->take(10)
            ->get();

        return $this->sendResponse([
            'product' => $product,
            'related_products' => $relatedProducts
        ], 'Product details retrieved successfully.');
    }
}
