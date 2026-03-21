<?php

namespace App\Http\Controllers\Api;

use App\Models\SuperAdminUser;
use App\Models\Session as UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    /**
     * Register a new super admin user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:super_admin_users',
            'email' => 'required|string|email|max:150|unique:super_admin_users',
            'password' => 'required|string|min:6',
            'full_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $user = SuperAdminUser::create([
            'username' => $request->username,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'role_id' => 1, // Default role
            'status' => 'active',
        ]);

        return $this->sendResponse($user, 'User registered successfully.');
    }

    /**
     * Login and generate JWT token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:username|email',
            'username' => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $credentials = $request->only(['email', 'username', 'password']);
        
        // JWTAuth usually looks for 'password' field, but we have 'password_hash'
        // We'll manualy authenticate
        $user = SuperAdminUser::where('email', $request->email)
                ->orWhere('username', $request->username)
                ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials'], 401);
        }

        $token = JWTAuth::fromUser($user);

        // Save session in sessions table
        UserSession::create([
            'user_type' => 'super_admin',
            'user_id' => $user->id,
            'session_token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'expiry_time' => now()->addMinutes(config('jwt.ttl')),
            'is_active' => 1,
        ]);

        // Update user last login
        $user->update([
            'last_login' => now(),
            'last_login_ip' => $request->ip()
        ]);

        return $this->sendResponse([
            'token' => $token,
            'user' => $user
        ], 'User logged in successfully.');
    }

    /**
     * Logout and invalidate session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $token = JWTAuth::getToken();
        
        if ($token) {
            UserSession::where('session_token', $token)->update([
                'is_active' => 0,
                'logout_time' => now()
            ]);
            JWTAuth::invalidate($token);
        }

        return $this->sendResponse([], 'Successfully logged out.');
    }

    /**
     * Forgot Password - Send reset link/token
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:super_admin_users,email',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        // Generate a random token for resetting
        $token = Str::random(64);

        // In a real app, save this token to a password_resets table and send an email
        // For this demo, we'll just return the token
        return $this->sendResponse(['reset_token' => $token], 'Password reset link would be sent to your email. (Token returned for demo purposes)');
    }
}
