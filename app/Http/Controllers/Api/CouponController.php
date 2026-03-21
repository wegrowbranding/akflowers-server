<?php

namespace App\Http\Controllers\Api;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');
        
        $query = Coupon::query();

        if ($search) {
            $query->where('code', 'LIKE', "%{$search}%");
        }
        
        $coupons = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $coupons->total(),
            'limit' => $coupons->perPage(),
            'page' => $coupons->currentPage(),
            'data' => $coupons->items()
        ], 'Coupons retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'code' => 'required|string|max:50|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric',
            'min_order_amount' => 'nullable|numeric',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'usage_limit' => 'nullable|integer',
            'used_count' => 'nullable|integer',
            'status' => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $coupon = Coupon::create($input);

        return $this->sendResponse($coupon, 'Coupon created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (is_null($coupon)) {
            return $this->sendError('Coupon not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'code' => 'string|max:50|unique:coupons,code,' . $id,
            'discount_type' => 'in:percentage,fixed',
            'discount_value' => 'numeric',
            'min_order_amount' => 'nullable|numeric',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'usage_limit' => 'nullable|integer',
            'status' => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $coupon->update($input);

        return $this->sendResponse($coupon, 'Coupon updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (is_null($coupon)) {
            return $this->sendError('Coupon not found.');
        }

        $coupon->delete();

        return $this->sendResponse([], 'Coupon deleted successfully.');
    }
}
