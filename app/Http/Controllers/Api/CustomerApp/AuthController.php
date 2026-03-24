<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Customer;
use App\Models\Session as UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    /**
     * Register a new customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/|unique:customers',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }
        
        $customerCode = 'CUST-' . strtoupper(Str::random(6));

        $customer = Customer::create([
            'customer_code' => $customerCode,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password_hash' => Hash::make($request->password),
            'status' => 'active',
            'deleted' => 0,
        ]);

        $token = JWTAuth::fromUser($customer);

        UserSession::create([
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'session_token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'expiry_time' => now()->addMinutes(config('jwt.ttl', 60)),
            'is_active' => 1,
        ]);

        $userData = $customer->toArray();
        $userData['user_type'] = 'customer';

        return $this->sendResponse([
            'token' => $token,
            'user' => $userData
        ], 'Customer registered successfully.');
    }

    /**
     * Login customer and generate JWT token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|email',
            'phone' => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }
        
        $customer = null;

        if ($request->has('email') && $request->email) {
            $customer = Customer::where('email', $request->email)->where('deleted', 0)->first();
        } else if ($request->has('phone') && $request->phone) {
            $customer = Customer::where('phone', $request->phone)->where('deleted', 0)->first();
        }

        if (!$customer || !Hash::check($request->password, $customer->password_hash)) {
            return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials'], 401);
        }
        
        if ($customer->status !== 'active') {
             return $this->sendError('Unauthorized.', ['error' => 'Account is disabled.'], 401);
        }

        $token = JWTAuth::fromUser($customer);

        UserSession::create([
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'session_token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'expiry_time' => now()->addMinutes(config('jwt.ttl', 60)),
            'is_active' => 1,
        ]);

        $userData = $customer->toArray();
        $userData['user_type'] = 'customer';

        return $this->sendResponse([
            'token' => $token,
            'user' => $userData
        ], 'Customer logged in successfully.');
    }
}
