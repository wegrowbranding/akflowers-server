<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Review;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends BaseController
{
    /**
     * Add Review & Rating to a product
     */
    public function addReview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|numeric|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = $request->user('customer_api');

        // Verify if the customer actually ordered this product before allowing them to review?
        // (Optional business rule: uncomment if required)
        /*
        $hasPurchased = OrderItem::where('product_id', $request->product_id)
            ->whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)->where('order_status', 'completed');
            })->exists();
        
        if (!$hasPurchased) {
            return $this->sendError('You can only review products that you have purchased.', [], 403);
        }
        */

        // Create or update their review for this particular product
        $review = Review::updateOrCreate(
            ['customer_id' => $customer->id, 'product_id' => $request->product_id],
            [
                'rating' => $request->rating,
                'review' => $request->review,
            ]
        );

        return $this->sendResponse($review, 'Review submitted successfully.');
    }

    /**
     * View Reviews (can also be requested directly alongside Product details)
     */
    public function viewReviews($productId, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $reviews = Review::with(['customer' => function($q) {
            $q->select('id', 'full_name', 'profile_image'); // only expose public details
        }])
        ->where('product_id', $productId)
        ->orderBy('id', 'desc')
        ->paginate($limit);

        return $this->sendResponse([
            'total' => $reviews->total(),
            'limit' => $reviews->perPage(),
            'page' => $reviews->currentPage(),
            'data' => $reviews->items()
        ], 'Reviews retrieved successfully.');
    }
}
