<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Customer;
use App\Models\Session as UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use App\Helpers\MailHelper;
use App\Mail\SendOtpMail;
use App\Mail\CustomerRegisteredMail;
use App\Mail\AdminNewCustomerMail;
use App\Mail\PasswordChangedMail;
use App\Services\NotificationService;

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

        // Use a long TTL (30 days) for customer login from the app
        $token = JWTAuth::setTTL(43200)->fromUser($customer);

        UserSession::create([
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'session_token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'expiry_time' => now()->addDays(30),
            'is_active' => 1,
        ]);

        $userData = $customer->toArray();
        $userData['user_type'] = 'customer';

        MailHelper::send($customer->email, new CustomerRegisteredMail($customer));
        $adminEmails = explode(',', env('ADMIN_EMAILS'));
        foreach ($adminEmails as $email) {
            MailHelper::send(trim($email), new AdminNewCustomerMail($customer));
        }

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

        // Use a long TTL (30 days) for customer login from the app
        $token = JWTAuth::setTTL(43200)->fromUser($customer);

        UserSession::create([
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'session_token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'expiry_time' => now()->addDays(30),
            'is_active' => 1,
        ]);

        $userData = $customer->toArray();
        $userData['user_type'] = 'customer';

        $ipDetails = $this->getIpDetails($request);
        // Login notification
        NotificationService::sendToCustomer(
            $customer->id,
            'New Login detected! 🔒',
            "Your account was just logged in from " . $ipDetails['city'] . ", " . $ipDetails['country'] . ".",
            'login_alert'
        );

        return $this->sendResponse([
            'token' => $token,
            'user' => $userData
        ], 'Customer logged in successfully.');
    }

    /**
     * Check customer email exists if exists then send OTP to that email and return OTP in response.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function checkCustomerEmailExists(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->sendError(
                $validator->errors()->first(),
                $validator->errors()->toArray(),
                400
            );
        }

        $customer = Customer::where('email', $request->email)
            ->where('deleted', 0)
            ->first();

        if (!$customer) {
            return $this->sendResponse([
                'exists' => false
            ], 'Customer email does not exist.');
        }

        $otp = rand(100000, 999999);

        try {
            MailHelper::send($request->email, new SendOtpMail($otp));
        } catch (\Exception $e) {
            return $this->sendError('Failed to send OTP email', [], 500);
        }

        return $this->sendResponse([
            'exists' => true,
            'otp' => $otp
        ], 'OTP sent successfully.');
    }

    /**
     * Change Password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendError(
                $validator->errors()->first(),
                $validator->errors()->toArray(),
                400
            );
        }

        $customer = Customer::where('email', $request->email)
            ->where('deleted', 0)
            ->first();

        if (!$customer) {
            return $this->sendError('Customer email does not exist.', [], 404);
        }

        $customer->password_hash = Hash::make($request->password);
        $customer->save();

        MailHelper::send($customer->email, new PasswordChangedMail($customer));

        // Password change notification
        NotificationService::sendToCustomer(
            $customer->id,
            'Password Changed! 🔐',
            "Your password has been updated successfully.",
            'password_change'
        );

        return $this->sendResponse([
            'success' => true
        ], 'Password changed successfully.');
    }

    /**
     * Get IP Details from https://ipwho.is/
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getIpDetails(Request $request): array
    {
        $ip = $request->ip();

        $response = Http::get("https://ipwho.is/{$ip}");

        return $response->json();
    }
}
