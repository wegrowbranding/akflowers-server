<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryAssignment;
use App\Models\DeliveryStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class DeliveryAssignmentController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $orderId = $request->get('order_id');

        $query = DeliveryAssignment::with(['order', 'deliveryStaff.staff']);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $assignments = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $assignments->total(),
            'limit' => $assignments->perPage(),
            'page' => $assignments->currentPage(),
            'data' => $assignments->items()
        ], 'Delivery assignments retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'delivery_staff_id' => 'required|exists:delivery_staff,id',
            'assigned_at' => 'nullable|date',
            'status' => 'nullable|in:assigned,accepted,picked_up,out_for_delivery,delivered,rejected',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        $assignment = DeliveryAssignment::updateOrCreate(
            ['order_id' => $request->order_id],
            [
                'delivery_staff_id' => $request->delivery_staff_id,
                'assigned_at' => $request->assigned_at ?? now(),
                'status' => $request->status ?? 'assigned',
            ]
        );

        // Create status history for initial assignment/reassignment
        DeliveryStatusHistory::create([
            'assignment_id' => $assignment->id,
            'status' => $assignment->status ?? 'assigned',
            'remarks' => 'Order assigned to staff (Updated)',
            'created_at' => now(),
        ]);

        return $this->sendResponse($assignment, 'Delivery assignment handled successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $assignment = DeliveryAssignment::find($id);
        if (!$assignment) {
            return $this->sendError('Delivery assignment not found.');
        }

        $oldStatus = $assignment->status;

        $validator = Validator::make($request->all(), [
            'order_id' => 'exists:orders,id',
            'delivery_staff_id' => 'exists:delivery_staff,id',
            'assigned_at' => 'nullable|date',
            'status' => 'nullable|in:assigned,accepted,picked_up,out_for_delivery,delivered,rejected',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        $assignment->update($request->all());

        // Record history if status changed
        if ($oldStatus !== $assignment->status) {
            DeliveryStatusHistory::create([
                'assignment_id' => $assignment->id,
                'status' => $assignment->status,
                'remarks' => 'Status updated via admin panel',
                'created_at' => now(),
            ]);
        }

        return $this->sendResponse($assignment, 'Delivery assignment updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $assignment = DeliveryAssignment::with(['proofs', 'statusHistories', 'tracking'])->find($id);
        if (!$assignment) {
            return $this->sendError('Delivery assignment not found.');
        }

        // Delete cascade manually if needed (although probably handled by constraints if set up)
        $assignment->proofs()->delete();
        $assignment->statusHistories()->delete();
        $assignment->tracking()->delete();
        $assignment->delete();

        return $this->sendResponse([], 'Delivery assignment deleted successfully.');
    }
}
