<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class DeliveryStaffController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = DeliveryStaff::with('staff');

        if ($search) {
            $query->whereHas('staff', function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $staff = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $staff->total(),
            'limit' => $staff->perPage(),
            'page' => $staff->currentPage(),
            'data' => $staff->items()
        ], 'Delivery staff retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:branch_staff_users,id',
            'vehicle_type' => 'required|in:bike,cycle,car',
            'vehicle_number' => 'nullable|string|max:50',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        $staff = DeliveryStaff::create($request->all());
        return $this->sendResponse($staff, 'Delivery staff added successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $staff = DeliveryStaff::find($id);
        if (!$staff) {
            return $this->sendError('Delivery staff not found.');
        }

        $validator = Validator::make($request->all(), [
            'staff_id' => 'exists:branch_staff_users,id',
            'vehicle_type' => 'in:bike,cycle,car',
            'vehicle_number' => 'nullable|string|max:50',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        $staff->update($request->all());
        return $this->sendResponse($staff, 'Delivery staff updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $staff = DeliveryStaff::find($id);
        if (!$staff) {
            return $this->sendError('Delivery staff not found.');
        }

        $staff->delete();
        return $this->sendResponse([], 'Delivery staff deleted successfully.');
    }
}
