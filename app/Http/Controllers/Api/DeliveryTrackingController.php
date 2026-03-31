<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryTracking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryTrackingController extends BaseController
{
    public function get(Request $request, $assignment_id): JsonResponse
    {
        $tracking = DeliveryTracking::where('assignment_id', $assignment_id)
            ->with('assignment.deliveryStaff.staff')
            ->orderBy('recorded_at', 'desc')
            ->get();
            
        return $this->sendResponse($tracking, 'Tracking detail retrieved successfully.');
    }

    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $orderId = $request->get('order_id');

        $query = DeliveryTracking::with('assignment.deliveryStaff.staff')
            ->orderBy('recorded_at', 'desc');

        if ($orderId) {
            $query->whereHas('assignment', function($q) use ($orderId) {
                $q->where('order_id', $orderId);
            });
        }

        $tracking = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $tracking->total(),
            'limit' => $tracking->perPage(),
            'page' => $tracking->currentPage(),
            'data' => $tracking->items()
        ], 'Delivery tracking retrieved successfully.');
    }
}
