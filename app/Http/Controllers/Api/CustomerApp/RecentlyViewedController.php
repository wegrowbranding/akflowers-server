<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\CustomerRecentView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentlyViewedController extends BaseController
{
    /**
     * Get Recently Viewed Products
     */
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $customer = $request->user('customer_api');

        $recentViews = CustomerRecentView::with(['product.media', 'product.category'])
            ->where('customer_id', $customer->id)
            ->whereHas('product', function ($query) {
                $query->where('deleted', 0)->where('status', 'active');
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($limit);

        return $this->sendResponse([
            'total' => $recentViews->total(),
            'limit' => $recentViews->perPage(),
            'page' => $recentViews->currentPage(),
            'data' => $recentViews->items()
        ], 'Recently viewed products retrieved successfully.');
    }

    /**
     * Mark Product as Recently Viewed (Called dynamically when visiting Product Details)
     */
    public function add($productId, Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        // Insert or update updated_at timestamp
        $view = CustomerRecentView::updateOrCreate(
            ['customer_id' => $customer->id, 'product_id' => $productId],
            ['updated_at' => now()]
        );

        return $this->sendResponse([], 'Recent view recorded.');
    }
}
