<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryStatusHistoryController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $orderId = $request->get('order_id');

        $query = DeliveryStatusHistory::with('assignment.order', 'assignment.deliveryStaff.staff');

        if ($orderId) {
            $query->whereHas('assignment', function($q) use ($orderId) {
                $q->where('order_id', $orderId);
            });
        }

        $histories = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $histories->total(),
            'limit' => $histories->perPage(),
            'page' => $histories->currentPage(),
            'data' => $histories->items()
        ], 'Delivery status histories retrieved successfully.');
    }
}
