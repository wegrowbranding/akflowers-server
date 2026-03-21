<?php

namespace App\Http\Controllers\Api;

use App\Models\BranchAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchAdminController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = BranchAdmin::where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $admins = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $admins->total(),
            'limit' => $admins->perPage(),
            'page' => $admins->currentPage(),
            'data' => $admins->items()
        ], 'Branch admins retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_id' => 'required|integer|exists:branches,id',
            'username' => 'required|string|max:100|unique:branch_admin,username',
            'password_hash' => 'required|string',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:branch_admin,email',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'created_by' => 'required|integer',
            'status' => 'in:active,inactive,suspended,password_reset_required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $admin = BranchAdmin::create($input);

        return $this->sendResponse($admin, 'Branch admin created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $admin = BranchAdmin::where('id', $id)->where('deleted', 0)->first();

        if (is_null($admin)) {
            return $this->sendError('Branch admin not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_id' => 'integer|exists:branches,id',
            'username' => 'string|max:100|unique:branch_admin,username,' . $admin->id,
            'password_hash' => 'string',
            'full_name' => 'string|max:255',
            'email' => 'email|max:255|unique:branch_admin,email,' . $admin->id,
            'phone' => 'string|max:20|regex:/^[0-9+\-\s()]+$/',
            'status' => 'in:active,inactive,suspended,password_reset_required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $admin->update($input);

        return $this->sendResponse($admin, 'Branch admin updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $admin = BranchAdmin::where('id', $id)->where('deleted', 0)->first();

        if (is_null($admin)) {
            return $this->sendError('Branch admin not found or already deleted.');
        }

        $admin->update([
            'deleted' => 1
        ]);

        return $this->sendResponse([], 'Branch admin deleted successfully.');
    }
}
