<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AddressController extends BaseController
{
    /**
     * Fetch addresses for the logged-in customer.
     *
     * @return JsonResponse
     */
    public function getAddresses(Request $request): JsonResponse
    {
        $customer = JWTAuth::parseToken()->authenticate();

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return $this->sendResponse($addresses, 'Addresses retrieved successfully.');
    }

    /**
     * Add a new address.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'address_line1' => 'required|string',
            'address_line2' => 'nullable|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = JWTAuth::parseToken()->authenticate();
        $input = $request->all();
        $input['customer_id'] = $customer->id;

        // Make other addresses non-default if this one is default
        if ($request->get('is_default', false)) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => 0]);
        }
        
        // If it's the customer's first address, make it default automatically
        $count = CustomerAddress::where('customer_id', $customer->id)->count();
        if ($count === 0) {
            $input['is_default'] = 1;
        }

        $address = CustomerAddress::create($input);

        return $this->sendResponse($address, 'Address added successfully.');
    }

    /**
     * Update an existing address.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateAddress(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = $request->user('customer_api');
        
        $address = CustomerAddress::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$address) {
            return $this->sendError('Address not found or unauthorized.', [], 404);
        }

        $input = $request->all();

        if ($request->has('is_default') && $request->is_default) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => 0]);
            $input['is_default'] = 1;
        }

        $address->update($input);

        return $this->sendResponse($address, 'Address updated successfully.');
    }

    /**
     * Delete an existing address.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function deleteAddress(Request $request, $id): JsonResponse
    {
        $customer = $request->user('customer_api');
        
        $address = CustomerAddress::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$address) {
            return $this->sendError('Address not found or unauthorized.', [], 404);
        }

        $isDefault = $address->is_default;
        $address->delete();

        // If it was the default address, map a new default address proactively
        if ($isDefault) {
            $nextAddress = CustomerAddress::where('customer_id', $customer->id)->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => 1]);
            }
        }

        return $this->sendResponse([], 'Address deleted successfully.');
    }
}
