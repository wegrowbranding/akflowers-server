<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseController
{
    /**
     * Add a Notification
     */
    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|max:50',
            'image_url' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        
        // Use authenticated customer id if customer_id isn't provided
        $user = $request->user('customer_api');
        $customerId = $request->customer_id ?? ($user ? $user->id : null);

        if (!$customerId) {
            return $this->sendError('Customer ID is required.');
        }

        $notification = Notification::create([
            'customer_id' => $customerId,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'reference_id' => $request->reference_id,
            'image_url' => $request->image_url,
            'is_read' => 0,
        ]);

        return $this->sendResponse($notification, 'Notification created successfully.');
    }

    /**
     * Notification List
     */
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $customer = $request->user('customer_api');

        $notifications = Notification::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return $this->sendResponse([
            'total' => $notifications->total(),
            'limit' => $notifications->perPage(),
            'page' => $notifications->currentPage(),
            'data' => $notifications->items()
        ], 'Notifications retrieved successfully.');
    }

    /**
     * Mark Notification as Read
     */
    public function markRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $customer = $request->user('customer_api');
        
        $notification = Notification::where('id', $request->notification_id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$notification) {
            return $this->sendError('Notification not found or unauthorized.', [], 404);
        }

        $notification->update([
            'is_read' => 1,
            'read_at' => now()
        ]);

        return $this->sendResponse([], 'Notification marked as read.');
    }

    /**
     * Get Unread Notifications Count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        $count = Notification::where('customer_id', $customer->id)
            ->where('is_read', 0)
            ->count();

        return $this->sendResponse([
            'unread_count' => $count
        ], 'Unread count retrieved successfully.');
    }
}
