<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\CustomerDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DeviceController extends BaseController
{
    /**
     * Add or register a device
     */
    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'device_name' => 'nullable|string',
            'device_type' => 'required|in:android,ios,web',
            'fcm_token' => 'required|string',
            'app_version' => 'nullable|string',
            'os_version' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = $request->user('customer_api');

        // Check if device already exists
        $existingDevice = CustomerDevice::where('customer_id', $customer->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($existingDevice) {
            // Ignore or return message
            return $this->sendResponse($existingDevice, 'Device already registered.');
        }

        // Create new device
        $device = CustomerDevice::create([
            'customer_id' => $customer->id,
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'fcm_token' => $request->fcm_token,
            'app_version' => $request->app_version,
            'os_version' => $request->os_version,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'is_active' => 1,
            'last_login_at' => now(),
            'last_used_at' => now(),
        ]);

        return $this->sendResponse($device, 'Device registered successfully.');
    }

    /**
     * Logout a device (Set is_active to false)
     */
    public function logout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $customer = $request->user('customer_api');

        $device = CustomerDevice::where('customer_id', $customer->id)
            ->where('device_id', $request->device_id)
            ->first();

        if (!$device) {
            return $this->sendError('Device not found.', [], 404);
        }

        $device->update(['is_active' => 0]);

        return $this->sendResponse([], 'Device logged out successfully.');
    }

    /**
     * Update last active status dynamically
     */
    public function updateLastActive(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $customer = $request->user('customer_api');

        $device = CustomerDevice::where('customer_id', $customer->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($device) {
            $device->update(['last_used_at' => now(), 'is_active' => 1]);
            return $this->sendResponse([], 'Device last active updated.');
        }

        return $this->sendError('Device not found.', [], 404);
    }

    /**
     * Get available FCM Tokens (past 30 days only)
     */
    public function getAvailableFcmTokens(Request $request): JsonResponse
    {
        // Get valid devices (last_used_at >= now - 30 days) and is_active = 1
        $thresholdDate = Carbon::now()->subDays(30);

        $query = CustomerDevice::where('is_active', 1)
            ->where('last_used_at', '>=', $thresholdDate)
            ->whereNotNull('fcm_token');

        // Optional filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $tokens = $query->pluck('fcm_token')->unique()->values();

        return $this->sendResponse([
            'count' => $tokens->count(),
            'tokens' => $tokens
        ], 'FCM Tokens retrieved successfully.');
    }
}
