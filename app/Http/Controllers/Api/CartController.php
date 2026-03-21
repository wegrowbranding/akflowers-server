<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $customerId = $request->get('customer_id');

        if (!$customerId) {
            return $this->sendError('Customer ID is required.');
        }

        $limit = $request->get('limit', 10);
        
        $carts = Cart::with('items')->where('customer_id', $customerId)->paginate($limit);

        return $this->sendResponse([
            'total' => $carts->total(),
            'limit' => $carts->perPage(),
            'page' => $carts->currentPage(),
            'data' => $carts->items()
        ], 'Carts retrieved successfully.');
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

        $cart = Cart::create($input);

        if (isset($input['products']) && is_array($input['products'])) {
            foreach ($input['products'] as $item) {
                if (!empty($item['product_id'])) {
                    $cart->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'] ?? 1
                    ]);
                }
            }
        }

        $cart->load('items');

        return $this->sendResponse($cart, 'Cart created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $cart = Cart::find($id);

        if (is_null($cart)) {
            return $this->sendError('Cart not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'customer_id' => 'integer|exists:customers,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $cart->update($input);

        if (isset($input['products']) && is_array($input['products'])) {
            $cart->items()->delete();
            foreach ($input['products'] as $item) {
                if (!empty($item['product_id'])) {
                    $cart->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'] ?? 1
                    ]);
                }
            }
        }

        $cart->load('items');

        return $this->sendResponse($cart, 'Cart updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $cart = Cart::with('items')->find($id);

        if (is_null($cart)) {
            return $this->sendError('Cart not found.');
        }
        
        $cart->items()->delete();
        $cart->delete();

        return $this->sendResponse([], 'Cart deleted successfully.');
    }
}
