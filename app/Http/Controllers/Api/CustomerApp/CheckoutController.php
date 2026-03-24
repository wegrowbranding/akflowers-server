<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CheckoutController extends BaseController
{
    /**
     * Apply Coupon
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $code = $request->coupon_code;
        $amount = $request->amount;

        $coupon = Coupon::where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$coupon) {
            return $this->sendError('Invalid or inactive coupon code.');
        }

        // Check valid dates
        $now = Carbon::now();
        if ($coupon->valid_from && $now->lt(Carbon::parse($coupon->valid_from))) {
             return $this->sendError('Coupon is not yet valid.');
        }
        if ($coupon->valid_to && $now->gt(Carbon::parse($coupon->valid_to))) {
             return $this->sendError('Coupon has expired.');
        }

        // Check usage limit
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $this->sendError('Coupon usage limit reached.');
        }

        // Check minimum order amount
        if ($coupon->min_order_amount !== null && $amount < $coupon->min_order_amount) {
            return $this->sendError('Minimum order amount not reached for this coupon.', [
                'min_order_amount' => $coupon->min_order_amount
            ]);
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discountAmount = ($amount * $coupon->discount_value) / 100;
        } else {
            // Fixed amount
            $discountAmount = $coupon->discount_value;
        }

        // Ensure discount doesn't exceed total amount
        if ($discountAmount > $amount) {
            $discountAmount = $amount;
        }

        return $this->sendResponse([
            'coupon_code' => $coupon->code,
            'discount_amount' => $discountAmount,
            'final_amount' => $amount - $discountAmount
        ], 'Coupon applied successfully.');
    }

    /**
     * Place an order
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer',
            'coupon_code' => 'nullable|string',
            'payment_method' => 'required|in:cod', // COD only for now
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $customer = JWTAuth::parseToken()->authenticate();

        // Check address
        $address = CustomerAddress::where('id', $request->address_id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$address) {
            return $this->sendError('Invalid delivery address.');
        }

        // Get Cart
        $cart = Cart::where('customer_id', $customer->id)->first();
        if (!$cart) {
            return $this->sendError('Your cart is empty.');
        }

        $cartItems = CartItem::with('product')->where('cart_id', $cart->id)->get();
        if ($cartItems->isEmpty()) {
            return $this->sendError('Your cart is empty.');
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0;

            // Optional: calculate total from items and check stock
            foreach ($cartItems as $item) {
                if (!$item->product || $item->product->status !== 'active' || $item->product->deleted) {
                    throw new \Exception('A product in your cart is no longer available.');
                }
                // if ($item->product->stock_quantity < $item->quantity) {
                //     throw new \Exception("Insufficient stock for product: {$item->product->product_name}");
                // }
                $totalAmount += ($item->product->price * $item->quantity);
            }

            $discountAmount = 0;
            $couponUsed = null;

            // Apply Coupon Logic
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('status', 'active')
                    ->first();
                
                if ($coupon) {
                    $now = Carbon::now();
                    $isValid = true;
                    if ($coupon->valid_from && $now->lt(Carbon::parse($coupon->valid_from))) $isValid = false;
                    if ($coupon->valid_to && $now->gt(Carbon::parse($coupon->valid_to))) $isValid = false;
                    if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) $isValid = false;
                    if ($coupon->min_order_amount !== null && $totalAmount < $coupon->min_order_amount) $isValid = false;

                    if ($isValid) {
                        if ($coupon->discount_type === 'percentage') {
                            $discountAmount = ($totalAmount * $coupon->discount_value) / 100;
                        } else {
                            $discountAmount = $coupon->discount_value;
                        }
                        if ($discountAmount > $totalAmount) {
                            $discountAmount = $totalAmount;
                        }

                        // Increment usage count later if everything succeeds
                        $couponUsed = $coupon;
                    }
                }
            }

            $finalAmount = $totalAmount - $discountAmount;

            // Create Order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->id,
                'branch_id' => 1, // Add proper mapping later if multiple branches exist
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'payment_status' => 'pending', // COD is pending
                'order_status' => 'pending',
                'payment_method' => $request->payment_method, // 'cod'
                'address_id' => $address->id,
            ]);

            // Create Order Items and decrease stock
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'total_price' => $item->product->price * $item->quantity,
                ]);

                // Reduce stock
                $item->product->update([
                    'stock_quantity' => $item->product->stock_quantity - $item->quantity
                ]);
            }

            // After order is created, clean up the cart
            CartItem::where('cart_id', $cart->id)->delete();

            // Update Coupon Usage
            if ($couponUsed) {
                $couponUsed->increment('used_count');
            }

            DB::commit();

            return $this->sendResponse($order, 'Order placed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to place order.', ['error' => $e->getMessage()], 400);
        }
    }
}
