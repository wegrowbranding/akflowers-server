<?php

namespace App\Http\Controllers\Api\DeliveryApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\BranchStaffUser;
use App\Models\DeliveryStaff;
use App\Models\Session as UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    /**
     * Login delivery staff and generate JWT token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        $staffUser = BranchStaffUser::where('email', $request->email)->where('status', 'active')->first();

        if (!$staffUser || !Hash::check($request->password, $staffUser->password_hash)) {
            return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials'], 401);
        }

        // Check if this staff user is a delivery man
        $deliveryStaff = DeliveryStaff::where('staff_id', $staffUser->id)->first();
        if (!$deliveryStaff) {
            return $this->sendError('Unauthorized.', ['error' => 'You are not registered as a delivery staff.'], 403);
        }

        $token = JWTAuth::fromUser($staffUser);

        UserSession::create([
            'user_type' => 'delivery_staff',
            'user_id' => $staffUser->id,
            'session_token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'expiry_time' => now()->addDays(30),
            'is_active' => 1,
        ]);

        $userData = $staffUser->toArray();
        $userData['user_type'] = 'delivery_staff';
        $userData['delivery_details'] = $deliveryStaff->toArray();

        return $this->sendResponse([
            'token' => $token,
            'user' => $userData
        ], 'Delivery staff logged in successfully.');
    }
}
