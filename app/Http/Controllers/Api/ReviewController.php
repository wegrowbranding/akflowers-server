<?php

namespace App\Http\Controllers\Api;

use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $reviews = Review::paginate($limit);

        return $this->sendResponse([
            'total' => $reviews->total(),
            'limit' => $reviews->perPage(),
            'page' => $reviews->currentPage(),
            'data' => $reviews->items()
        ], 'Reviews retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'product_id' => 'required|integer|exists:products,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $review = Review::create($input);

        return $this->sendResponse($review, 'Review created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'product_id' => 'integer|exists:products,id',
            'customer_id' => 'integer|exists:customers,id',
            'rating' => 'integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $review->update($input);

        return $this->sendResponse($review, 'Review updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $review->delete();

        return $this->sendResponse([], 'Review deleted successfully.');
    }
}
