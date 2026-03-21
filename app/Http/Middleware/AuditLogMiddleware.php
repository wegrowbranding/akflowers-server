<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            // We only want to log CRUD operations. Typically these are not GET requests.
            // Exclude GET, OPTIONS, HEAD requests
            $method = $request->method();
            if (in_array($method, ['GET', 'OPTIONS', 'HEAD'])) {
                return;
            }

            // Exclude some routes if necessary (e.g. login, register)
            $path = $request->path();
            if (Str::contains($path, ['auth/login', 'auth/register', 'auth/forgot-password'])) {
                return;
            }

            // Attempt to get the authenticated user
            $user = $request->user();

            if (!$user) {
                // If not authenticated, we may not want to log or we log as anonymous / system. 
                // But the Audit Log table structure requires user_type, user_id, username. 
                // Let's rely only on logged-in users for CRUD operations.
                return;
            }

            // Determine User Type and associated properties
            $userType = 'super_admin';
            $branchId = null;
            
            // Assuming username field exists, fallback to email or name if missing
            $username = $user->username ?? $user->email ?? $user->name ?? 'unknown';

            if ($user instanceof \App\Models\SuperAdminUser) {
                $userType = 'super_admin';
                $branchId = null;
            } elseif ($user instanceof \App\Models\BranchAdmin) {
                $userType = 'branch_admin';
                $branchId = $user->branch_id ?? null;
            } elseif ($user instanceof \App\Models\BranchStaffUser) {
                $userType = 'branch_staff';
                $branchId = $user->branch_id ?? null;
            } else {
                // Default fallback
                $userType = 'super_admin';
            }

            // Determine Action Type
            $actionType = 'OTHER';
            if (Str::endsWith($path, 'add') || $method === 'POST') {
                $actionType = 'CREATE';
            } elseif (Str::endsWith($path, 'edit') || $method === 'PUT' || $method === 'PATCH') {
                $actionType = 'UPDATE';
            } elseif (Str::endsWith($path, 'delete') || $method === 'DELETE') {
                $actionType = 'DELETE';
            }

            // Format Action Description
            $segments = $request->segments();
            // Typically: api, v1, {resource}, add|edit|delete
            // We can guess the resource name from URL
            $resource = count($segments) > 2 ? $segments[2] : 'unknown_resource';
            $actionDesc = ucfirst(strtolower($actionType)) . " operation performed on '{$resource}'";

            $requestData = $request->except(['password', 'password_hash', 'password_confirmation', 'token']);
            $requestDataJson = !empty($requestData) ? json_encode($requestData) : json_encode([]);

            AuditLog::create([
                'user_type' => $userType,
                'user_id' => $user->id,
                'username' => $username,
                'branch_id' => $branchId,
                'action_type' => $actionType,
                'action_description' => $actionDesc,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $requestDataJson,
                'response_status' => $response->getStatusCode(),
            ]);

        } catch (\Exception $e) {
            // Catch silently so we don't break the response if DB fails, but log it
            Log::error('AuditLogMiddleware failed: ' . $e->getMessage());
        }
    }
}
