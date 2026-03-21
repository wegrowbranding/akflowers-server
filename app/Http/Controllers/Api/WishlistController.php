<?php

namespace App\Http\Controllers\Api;

use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $customerId = $request->get('customer_id');

        if (!$customerId) {
            return $this->sendError('Customer ID is required.');
        }

        $limit = $request->get('limit', 10);
        
        $wishlists = Wishlist::with('items')->where('customer_id', $customerId)->paginate($limit);

        return $this->sendResponse([
            'total' => $wishlists->total(),
            'limit' => $wishlists->perPage(),
            'page' => $wishlists->currentPage(),
            'data' => $wishlists->items()
        ], 'Wishlists retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_id' => 'required|integer|exists:customers,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $wishlist = Wishlist::create($input);

        if (isset($input['products']) && is_array($input['products'])) {
            foreach ($input['products'] as $item) {
                if (!empty($item['product_id'])) {
                    $wishlist->items()->create([
                        'product_id' => $item['product_id']
                    ]);
                }
            }
        }

        $wishlist->load('items');

        return $this->sendResponse($wishlist, 'Wishlist created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $wishlist = Wishlist::find($id);

        if (is_null($wishlist)) {
            return $this->sendError('Wishlist not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_id' => 'integer|exists:customers,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $wishlist->update($input);

        if (isset($input['products']) && is_array($input['products'])) {
            $wishlist->items()->delete();
            foreach ($input['products'] as $item) {
                if (!empty($item['product_id'])) {
                    $wishlist->items()->create([
                        'product_id' => $item['product_id']
                    ]);
                }
            }
        }

        $wishlist->load('items');

        return $this->sendResponse($wishlist, 'Wishlist updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $wishlist = Wishlist::with('items')->find($id);

        if (is_null($wishlist)) {
            return $this->sendError('Wishlist not found.');
        }
        
        $wishlist->items()->delete();
        $wishlist->delete();

        return $this->sendResponse([], 'Wishlist deleted successfully.');
    }
}
