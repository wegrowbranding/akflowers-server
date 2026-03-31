<?php

namespace App\Http\Controllers\Api\DeliveryApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\DeliveryTracking;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrackingController extends BaseController
{
    /**
     * Update tracking location
     */
    public function updateTracking(Request $request): JsonResponse
    {
        $user = $request->user();
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:delivery_assignments,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $assignment = DeliveryAssignment::where('id', $request->assignment_id)
            ->where('delivery_staff_id', $deliveryStaff->id)
            ->first();

        if (!$assignment) {
            return $this->sendError('Assignment not found.');
        }

        $tracking = DeliveryTracking::create([
            'assignment_id' => $assignment->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => now(),
        ]);

        return $this->sendResponse($tracking, 'Tracking location updated successfully.');
    }
}
