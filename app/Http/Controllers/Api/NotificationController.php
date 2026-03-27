<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseController
{
    /**
     * Send a custom notification to all active users
     */
    public function sendCustomNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image_url' => 'nullable|string',
            'type' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        NotificationService::sendToAll(
            $request->title,
            $request->message,
            $request->type ?? 'admin_custom',
            null,
            $request->image_url
        );

        return $this->sendResponse([], 'Custom notification sent successfully to all active users.');
    }
}
