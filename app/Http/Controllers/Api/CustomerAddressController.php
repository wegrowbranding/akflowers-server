<?php

namespace App\Http\Controllers\Api;

use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerAddressController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');
        
        $query = CustomerAddress::with('customer');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhere('state', 'LIKE', "%{$search}%");
            });
        }
        
        $addresses = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $addresses->total(),
            'limit' => $addresses->perPage(),
            'page' => $addresses->currentPage(),
            'data' => $addresses->items()
        ], 'Customer addresses retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_id' => 'required|integer|exists:customers,id',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'address_line1' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $address = CustomerAddress::create($input);

        return $this->sendResponse($address, 'Customer address created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $address = CustomerAddress::find($id);

        if (is_null($address)) {
            return $this->sendError('Customer address not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_id' => 'integer|exists:customers,id',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'address_line1' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $address->update($input);

        return $this->sendResponse($address, 'Customer address updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $address = CustomerAddress::find($id);

        if (is_null($address)) {
            return $this->sendError('Customer address not found.');
        }

        $address->delete();

        return $this->sendResponse([], 'Customer address deleted successfully.');
    }
}
