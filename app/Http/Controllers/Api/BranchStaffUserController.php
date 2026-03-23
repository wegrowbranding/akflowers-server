<?php

namespace App\Http\Controllers\Api;

use App\Models\BranchStaffUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class BranchStaffUserController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = BranchStaffUser::with(['branch', 'role'])->where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $users->total(),
            'limit' => $users->perPage(),
            'page' => $users->currentPage(),
            'data' => $users->items()
        ], 'Branch staff users retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_id' => 'required|integer|exists:branches,id',
            'username' => 'required|string|max:100|unique:branch_staff_users,username',
            'email' => 'required|email|max:255|unique:branch_staff_users,email',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'role_id' => 'required|integer|exists:branch_roles,id',
            'employee_id' => 'nullable|string|max:100',
            'date_of_joining' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'created_by' => 'required|integer',
            'status' => 'in:active,inactive,suspended,resigned'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $input['password_hash'] = Hash::make($request->password);
        unset($input['password']);

        $user = BranchStaffUser::create($input);

        return $this->sendResponse($user, 'Branch staff user created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $user = BranchStaffUser::where('id', $id)->where('deleted', 0)->first();

        if (is_null($user)) {
            return $this->sendError('Branch staff user not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_id' => 'integer|exists:branches,id',
            'username' => 'string|max:100|unique:branch_staff_users,username,' . $user->id,
            'email' => 'email|max:255|unique:branch_staff_users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'full_name' => 'string|max:255',
            'phone' => 'string|max:20|regex:/^[0-9+\-\s()]+$/',
            'role_id' => 'integer|exists:branch_roles,id',
            'employee_id' => 'nullable|string|max:100',
            'date_of_joining' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'status' => 'in:active,inactive,suspended,resigned'
        ]);

        if (!empty($request->password)) {
            $input['password_hash'] = Hash::make($request->password);
        }
        unset($input['password']);

        // Prevent accidental downgrade to plaintext password if passed
        if (empty($request->password) && array_key_exists('password_hash', $input)) {
            unset($input['password_hash']);
        }

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $user->update($input);

        return $this->sendResponse($user, 'Branch staff user updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $user = BranchStaffUser::where('id', $id)->where('deleted', 0)->first();

        if (is_null($user)) {
            return $this->sendError('Branch staff user not found or already deleted.');
        }

        $user->update([
            'deleted' => 1
        ]);

        return $this->sendResponse([], 'Branch staff user deleted successfully.');
    }
}
