<?php

namespace App\Http\Controllers\Api\DeliveryApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Order;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    /**
     * Get order details
     */
    public function details(Request $request, $order_id): JsonResponse
    {
        $user = $request->user();
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        $order = Order::with(['items', 'customer', 'customerAddress', 'branch'])
            ->where('id', $order_id)
            ->first();

        if (!$order) {
            return $this->sendError('Order not found.');
        }

        $assignment = DeliveryAssignment::where('order_id', $order_id)
            ->where('delivery_staff_id', $deliveryStaff->id)
            ->where('status', '!=', 'rejected')
            ->first();

        if (!$assignment) {
            return $this->sendError('Order not assigned to you.');
        }

        return $this->sendResponse([
            'order' => $order,
            'assignment' => $assignment->toArray(),
        ], 'Order details retrieved successfully.');
    }

    /**
     * Order History
     */
    public function orderHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get delivery staff
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();

        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        // Optional status filter (delivered / failed / all)
        $status = $request->get('status');

        $assignmentsQuery = DeliveryAssignment::with([
            'order.items',
            'order.customer',
            'order.customerAddress',
            'order.branch'
        ])
        ->where('delivery_staff_id', $deliveryStaff->id);

        // Apply filter if provided
        if ($status && in_array($status, ['delivered', 'failed'])) {
            $assignmentsQuery->where('status', $status);
        } else {
            // Exclude rejected by default for overall history
            $assignmentsQuery->where('status', '!=', 'rejected');
        }

        // Latest first
        $assignments = $assignmentsQuery
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return $this->sendResponse($assignments, 'Order history retrieved successfully.');
    }
}
