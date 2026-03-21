<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');
        
        $query = Customer::where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%");
            });
        }
        
        $customers = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $customers->total(),
            'limit' => $customers->perPage(),
            'page' => $customers->currentPage(),
            'data' => $customers->items()
        ], 'Customers retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|unique:customers,phone|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'password_hash' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'status' => 'in:active,inactive,blocked'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        // if there's no code provided, standard practice might be to generate one, but we assume it's created or auto handled
        $customer = Customer::create($input);

        return $this->sendResponse($customer, 'Customer created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $customer = Customer::where('id', $id)->where('deleted', 0)->first();

        if (is_null($customer)) {
            return $this->sendError('Customer not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code,' . $id,
            'full_name' => 'string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'phone' => 'nullable|unique:customers,phone,' . $id . '|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'password_hash' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'status' => 'in:active,inactive,blocked'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer->update($input);

        return $this->sendResponse($customer, 'Customer updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $customer = Customer::where('id', $id)->where('deleted', 0)->first();

        if (is_null($customer)) {
            return $this->sendError('Customer not found or already deleted.');
        }

        $customer->update([
            'deleted' => 1
        ]);

        return $this->sendResponse([], 'Customer deleted successfully.');
    }
}
