<?php

namespace App\Http\Controllers\Api\DeliveryApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryStaff;
use App\Models\DeliveryStatusHistory;
use App\Models\Media;
use App\Models\DeliveryProof;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HomeController extends BaseController
{
    /**
     * Dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        $assignedCount = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id)->where('status', 'assigned')->count();
        $acceptedCount = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id)->where('status', 'accepted')->count();
        $pickedUpCount = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id)->where('status', 'picked_up')->count();
        $outForDeliveryCount = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id)->where('status', 'out_for_delivery')->count();
        $rejectedCount = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id)->where('status', 'rejected')->count();
        $deliveredTodayCount = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id)
            ->where('status', 'delivered')
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        $baseQuery = DeliveryAssignment::where('delivery_staff_id', $deliveryStaff->id);

        $newOrders = (clone $baseQuery)
            ->where('status', 'assigned')
            ->with(['order.items', 'order.customer'])
            ->orderByDesc('created_at')
            ->get();

        $acceptedOrders = (clone $baseQuery)
            ->where('status', 'accepted')
            ->with(['order.items', 'order.customer'])
            ->orderByDesc('updated_at')
            ->get();

        $pickedUpOrders = (clone $baseQuery)
            ->where('status', 'picked_up')
            ->with(['order.items', 'order.customer'])
            ->orderByDesc('updated_at')
            ->get();

        $outForDeliveryOrders = (clone $baseQuery)
            ->where('status', 'out_for_delivery')
            ->with(['order.items', 'order.customer'])
            ->orderByDesc('updated_at')
            ->get();

        return $this->sendResponse([
            'counts' => [
                'assigned' => $assignedCount,
                'accepted' => $acceptedCount,
                'picked_up' =>$pickedUpCount,
                'out_for_delivery' => $outForDeliveryCount,
                'rejected' => $rejectedCount,
                'delivered_today' => $deliveredTodayCount,
            ],
            'listings' => [
                'new_orders' => $newOrders,
                'accepted' => $acceptedOrders,
                'picked_up' => $pickedUpOrders,
                'out_for_delivery' => $outForDeliveryOrders,
            ],
            'is_available' => $deliveryStaff->is_available,
        ], 'Dashboard data retrieved successfully.');
    }

    /**
     * Update availability
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $user = $request->user();
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        $validator = Validator::make($request->all(), [
            'is_available' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $deliveryStaff->update(['is_available' => $request->is_available]);

        return $this->sendResponse(['is_available' => $deliveryStaff->is_available], 'Availability updated successfully.');
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:delivery_assignments,id',
            'status' => 'required|in:accepted,picked_up,out_for_delivery,delivered,rejected',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $assignment = DeliveryAssignment::where('id', $request->assignment_id)
            ->where('delivery_staff_id', $deliveryStaff->id)
            ->where('status', '!=', 'rejected')
            ->first();

        if (!$assignment) {
            return $this->sendError('Assignment not found.');
        }

        $assignment->update(['status' => $request->status]);

        // Sync with master order status
        $orderStatusMap = [
            'picked_up'        => 'shipped',
            'out_for_delivery' => 'shipped',
            'delivered'        => 'delivered',
            'rejected'           => 'shipped', // or keep as shipped
        ];

        if (isset($orderStatusMap[$request->status])) {
            $assignment->order()->update(['order_status' => $orderStatusMap[$request->status]]);
        }

        DeliveryStatusHistory::create([
            'assignment_id' => $assignment->id,
            'status' => $request->status,
            'remarks' => $request->remarks ?? 'Status updated via delivery app',
            'created_at' => now(),
        ]);

        return $this->sendResponse($assignment, 'Order status updated successfully.');
    }

    /**
     * Confirm delivery with photo proof
     */
    public function confirmDeliveryWithPhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $deliveryStaff = DeliveryStaff::where('staff_id', $user->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Delivery staff not found.');
        }

        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:delivery_assignments,id',
            'photo_base64' => 'required|string',
            'remarks' => 'nullable|string',
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

        // Handle base64 image
        $base64Image = $request->photo_base64;
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $type = 'jpg';
        }

        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            return $this->sendError('Invalid image data.', [], 400);
        }

        $fileName = 'proof_' . $assignment->id . '_' . Str::random(10) . '.' . $type;
        $filePath = 'delivery_proofs/' . $fileName;

        // Save to storage
        Storage::disk('public')->put($filePath, $imageData);
        $fileUrl = Storage::url($filePath);

        // Get mime type
        $f = finfo_open();
        $mimeType = finfo_buffer($f, $imageData, FILEINFO_MIME_TYPE);
        finfo_close($f);

        $fileSize = strlen($imageData);
        if ($fileSize > 1024 * 1024) {
            return $this->sendError('The delivery proof photo exceeds the 1MB size limit.', [], 400);
        }

        // Update status to delivered
        $assignment->update(['status' => 'delivered']);
        $assignment->order()->update(['order_status' => 'delivered']);

        DeliveryStatusHistory::create([
            'assignment_id' => $assignment->id,
            'status' => 'delivered',
            'remarks' => $request->remarks ?? 'Delivered with photo proof',
            'created_at' => now(),
        ]);

        // Create proof record
        DeliveryProof::create([
            'assignment_id' => $assignment->id,
            'proof_type' => 'image',
            'image_path' => $fileUrl,
            'verified' => 1,
            'created_at' => now(),
        ]);

        return $this->sendResponse($assignment, 'Delivery confirmed successfully.');
    }
}
