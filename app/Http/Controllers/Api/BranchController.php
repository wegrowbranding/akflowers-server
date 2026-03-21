<?php

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = Branch::where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'LIKE', "%{$search}%")
                  ->orWhere('branch_code', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
            });
        }

        $branches = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $branches->total(),
            'limit' => $branches->perPage(),
            'page' => $branches->currentPage(),
            'data' => $branches->items()
        ], 'Branches retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_code' => 'required|string|max:50|unique:branches,branch_code',
            'branch_name' => 'required|string|max:255',
            'address_line1' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'phone_primary' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'email_primary' => 'required|email|max:255',
            'created_by' => 'required|integer',
            'status' => 'in:active,inactive,suspended,closed'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $branch = Branch::create($input);

        return $this->sendResponse($branch, 'Branch created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $branch = Branch::where('id', $id)->where('deleted', 0)->first();

        if (is_null($branch)) {
            return $this->sendError('Branch not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'branch_code' => 'string|max:50|unique:branches,branch_code,' . $branch->id,
            'branch_name' => 'string|max:255',
            'address_line1' => 'string',
            'city' => 'string|max:100',
            'state' => 'string|max:100',
            'pincode' => 'string|max:20',
            'phone_primary' => 'string|max:20|regex:/^[0-9+\-\s()]+$/',
            'email_primary' => 'email|max:255',
            'status' => 'in:active,inactive,suspended,closed'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $branch->update($input);

        return $this->sendResponse($branch, 'Branch updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $branch = Branch::where('id', $id)->where('deleted', 0)->first();

        if (is_null($branch)) {
            return $this->sendError('Branch not found or already deleted.');
        }

        $branch->update([
            'deleted' => 1
        ]);

        return $this->sendResponse([], 'Branch deleted successfully.');
    }
}
