<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

    /**
     * Request to cancel order, order only will be cancelled withing 15 mins of order placed
     */

    public function cancel($id, Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        $order = Order::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$order) {
            return $this->sendError('Order not found or unauthorized.', [], 404);
        }

        // Already cancelled
        if ($order->order_status === 'cancelled') {
            return $this->sendError('Order already cancelled.', [], 400);
        }

        // 15 minutes check
        $createdAt = Carbon::parse($order->placed_at);
        $now = Carbon::now();

        if ($createdAt->diffInMinutes($now) > 15) {
            return $this->sendError('Order can only be cancelled within 15 minutes.', [], 400);
        }

        // Cancel order
        $order->order_status = 'cancelled';
        $order->save();

        return $this->sendResponse($order, 'Order cancelled successfully.');
    }
}
