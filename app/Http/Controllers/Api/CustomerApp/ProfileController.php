<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends BaseController
{
    /**
     * Get User Profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        return $this->sendResponse($customer, 'Profile retrieved successfully.');
    }

    /**
     * Edit User Profile
     */
    public function editProfile(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/|unique:customers,phone,' . $customer->id,
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'profile_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $input = $request->only(['full_name', 'phone', 'gender', 'date_of_birth', 'profile_image']);
        
        // Remove nulls to avoid overwriting existing data with empty values if that is preferred,
        // or just apply all provided values. Here we apply all provided in request.
        $customer->update($input);

        return $this->sendResponse($customer, 'Profile updated successfully.');
    }
}
