<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryProof;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryProofController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $orderId = $request->get('order_id');

        $query = DeliveryProof::with('assignment.order', 'assignment.deliveryStaff.staff');

        if ($orderId) {
            $query->whereHas('assignment', function($q) use ($orderId) {
                $q->where('order_id', $orderId);
            });
        }

        $proofs = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $proofs->total(),
            'limit' => $proofs->perPage(),
            'page' => $proofs->currentPage(),
            'data' => $proofs->items()
        ], 'Delivery proofs retrieved successfully.');
    }
}
