<?php

namespace App\Http\Controllers\Api;

use App\Models\BranchRole;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BranchRoleController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = BranchRole::with(['permission', 'branch'])->where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('role_name', 'LIKE', "%{$search}%")
                  ->orWhere('role_description', 'LIKE', "%{$search}%");
            });
        }

        $roles = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $roles->total(),
            'limit' => $roles->perPage(),
            'page' => $roles->currentPage(),
            'data' => $roles->items()
        ], 'Branch roles retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_id' => 'required|integer|exists:branches,id',
            'role_name' => 'required|string|max:100',
            'role_description' => 'nullable|string',
            'is_default' => 'boolean',
            'created_by' => 'required|integer',
            'status' => 'in:active,inactive',
            'permission' => 'nullable|array',
            'permission.category' => 'nullable|string|max:100',
            'permission.module' => 'required_with:permission|string|max:1000',
            'permission.action' => 'required_with:permission|string|max:100',
            'permission.display_name' => 'nullable|string|max:255',
            'permission.key_name' => 'nullable|string|max:150',
            'permission.description' => 'nullable|string',
            'permission.status' => 'in:active,inactive',
            'permission.is_system' => 'boolean',
            'permission.sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        DB::beginTransaction();
        try {
            $permissionId = null;
            if (isset($input['permission'])) {
                $permissionData = $input['permission'];
                $permissionData['created_by'] = $input['created_by'] ?? null;
                $permission = Permission::create($permissionData);
                $permissionId = $permission->id;
            }

            $input['permission_id'] = $permissionId;
            $role = BranchRole::create($input);

            DB::commit();

            $role->load('permission');

            return $this->sendResponse($role, 'Branch role created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', ['error' => $e->getMessage()], 500);
        }
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $role = BranchRole::where('id', $id)->where('deleted', 0)->first();

        if (is_null($role)) {
            return $this->sendError('Branch role not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_id' => 'integer|exists:branches,id',
            'role_name' => 'string|max:100',
            'role_description' => 'nullable|string',
            'is_default' => 'boolean',
            'status' => 'in:active,inactive',
            'permission' => 'nullable|array',
            'permission.category' => 'nullable|string|max:100',
            'permission.module' => 'required_with:permission|string|max:1000',
            'permission.action' => 'required_with:permission|string|max:100',
            'permission.display_name' => 'nullable|string|max:255',
            'permission.key_name' => 'nullable|string|max:150',
            'permission.description' => 'nullable|string',
            'permission.status' => 'in:active,inactive',
            'permission.is_system' => 'boolean',
            'permission.sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        DB::beginTransaction();
        try {
            if (isset($input['permission'])) {
                $permissionData = $input['permission'];
                
                if ($role->permission_id) {
                    $permission = Permission::find($role->permission_id);
                    if ($permission) {
                        $permission->update($permissionData);
                    } else {
                        $permissionData['created_by'] = $role->created_by;
                        $permission = Permission::create($permissionData);
                        $input['permission_id'] = $permission->id;
                    }
                } else {
                    $permissionData['created_by'] = $role->created_by;
                    $permission = Permission::create($permissionData);
                    $input['permission_id'] = $permission->id;
                }
            }

            $role->update($input);

            DB::commit();

            $role->load('permission');

            return $this->sendResponse($role, 'Branch role updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', ['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        $role = BranchRole::where('id', $id)->where('deleted', 0)->first();

        if (is_null($role)) {
            return $this->sendError('Branch role not found or already deleted.');
        }

        DB::beginTransaction();
        try {
            $permissionId = $role->permission_id;

            $role->update([
                'deleted' => 1
            ]);

            if ($permissionId) {
                Permission::where('id', $permissionId)->delete();
            }

            DB::commit();
            return $this->sendResponse([], 'Branch role & permission deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', ['error' => $e->getMessage()], 500);
        }
    }
}
