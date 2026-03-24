<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    /**
     * Get list of orders for customer
     */
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $customer = $request->user('customer_api');

        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('placed_at', 'desc')
            ->paginate($limit);

        return $this->sendResponse([
            'total' => $orders->total(),
            'limit' => $orders->perPage(),
            'page' => $orders->currentPage(),
            'data' => $orders->items()
        ], 'Orders retrieved successfully.');
    }

    /**
     * Get order details
     */
    public function details($id, Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        // Fetch order with related items and the specific products mapped
        $order = Order::with(['items.product.media'])
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$order) {
            return $this->sendError('Order not found or unauthorized.', [], 404);
        }

        return $this->sendResponse($order, 'Order details retrieved successfully.');
    }
}
