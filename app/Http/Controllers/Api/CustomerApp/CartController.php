<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CartController extends BaseController
{
    /**
     * Get Customer Cart
     */
    public function getCart(Request $request): JsonResponse
    {
        $customer = JWTAuth::parseToken()->authenticate();

        $cart = Cart::firstOrCreate(['customer_id' => $customer->id]);
        $items = CartItem::with(['product' => function ($query) {
            $query->with('media');
        }])->where('cart_id', $cart->id)->get();

        $total = 0;
        foreach ($items as $item) {
            if ($item->product) {
                $total += $item->quantity * $item->product->price;
            }
        }

        return $this->sendResponse([
            'cart_id' => $cart->id,
            'items' => $items,
            'total_amount' => $total
        ], 'Cart retrieved successfully.');
    }

    /**
     * Add or Update Cart
     */
    public function updateCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0', // 0 to remove
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = JWTAuth::parseToken()->authenticate();
        $cart = Cart::firstOrCreate(['customer_id' => $customer->id]);

        $productId = $request->product_id;
        $quantity = $request->quantity;

        // Check if product exists and active
        $product = Product::where('id', $productId)->where('deleted', 0)->where('status', 'active')->first();
        if (!$product && $quantity > 0) {
            return $this->sendError('Product not available.');
        }

        if ($quantity > 0) {
            // if ($product->stock_quantity < $quantity) {
            //     return $this->sendError('Insufficient stock available.');
            // }

            CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $productId],
                ['quantity' => $quantity]
            );
        } else {
            // Remove item
            CartItem::where('cart_id', $cart->id)->where('product_id', $productId)->delete();
        }

        return $this->sendResponse([], 'Cart updated successfully.');
    }
}
