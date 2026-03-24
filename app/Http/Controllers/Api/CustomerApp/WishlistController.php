<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends BaseController
{
    /**
     * Get Customer Wishlist
     */
    public function getWishlist(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        $wishlist = Wishlist::firstOrCreate(['customer_id' => $customer->id]);
        
        $items = WishlistItem::with(['product.media'])
            ->where('wishlist_id', $wishlist->id)
            ->get();

        return $this->sendResponse([
            'wishlist_id' => $wishlist->id,
            'items' => $items,
        ], 'Wishlist retrieved successfully.');
    }

    /**
     * Add/Update/Delete Wishlist Item
     */
    public function updateWishlist(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'action' => 'required|in:add,delete',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = $request->user('customer_api');
        $wishlist = Wishlist::firstOrCreate(['customer_id' => $customer->id]);

        $productId = $request->product_id;
        $action = $request->action;

        if ($action === 'add') {
            // make sure it isn't repeatedly added
            WishlistItem::firstOrCreate([
                'wishlist_id' => $wishlist->id, 
                'product_id' => $productId
            ]);
            $msg = 'Product added to wishlist.';
        } else {
            // Delete
            WishlistItem::where('wishlist_id', $wishlist->id)
                ->where('product_id', $productId)
                ->delete();
            $msg = 'Product removed from wishlist.';
        }

        return $this->sendResponse([], $msg);
    }
}
