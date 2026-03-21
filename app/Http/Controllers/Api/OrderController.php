<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');
        
        $query = Order::with('items');

        if ($search) {
            $query->where('order_number', 'LIKE', "%{$search}%");
        }
        
        $orders = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $orders->total(),
            'limit' => $orders->perPage(),
            'page' => $orders->currentPage(),
            'data' => $orders->items()
        ], 'Orders retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'order_number' => 'nullable|string|max:100|unique:orders,order_number',
            'customer_id' => 'required|integer|exists:customers,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'total_amount' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'final_amount' => 'required|numeric',
            'payment_status' => 'in:pending,paid,failed,refunded',
            'order_status' => 'in:pending,confirmed,packed,shipped,delivered,cancelled,returned',
            'payment_method' => 'nullable|string|max:50',
            'address_id' => 'nullable|integer|exists:customer_addresses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $order = Order::create($input);

        if (isset($input['products']) && is_array($input['products'])) {
            foreach ($input['products'] as $item) {
                if (!empty($item['product_id'])) {
                    $order->items()->create([
                        'product_id'   => $item['product_id'],
                        'quantity'     => $item['quantity'] ?? 1,
                        'price'        => $item['price'] ?? 0,
                        'total_price'  => $item['total_price'] ?? 0
                    ]);
                }
            }
        }

        $order->load('items');

        return $this->sendResponse($order, 'Order created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $order = Order::find($id);

        if (is_null($order)) {
            return $this->sendError('Order not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'order_number' => 'nullable|string|max:100|unique:orders,order_number,' . $id,
            'customer_id' => 'integer|exists:customers,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'total_amount' => 'numeric',
            'discount_amount' => 'nullable|numeric',
            'final_amount' => 'numeric',
            'payment_status' => 'in:pending,paid,failed,refunded',
            'order_status' => 'in:pending,confirmed,packed,shipped,delivered,cancelled,returned',
            'payment_method' => 'nullable|string|max:50',
            'address_id' => 'nullable|integer|exists:customer_addresses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $order->update($input);

        if (isset($input['products']) && is_array($input['products'])) {
            $order->items()->delete();
            foreach ($input['products'] as $item) {
                if (!empty($item['product_id'])) {
                    $order->items()->create([
                        'product_id'   => $item['product_id'],
                        'quantity'     => $item['quantity'] ?? 1,
                        'price'        => $item['price'] ?? 0,
                        'total_price'  => $item['total_price'] ?? 0
                    ]);
                }
            }
        }

        $order->load('items');

        return $this->sendResponse($order, 'Order updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (is_null($order)) {
            return $this->sendError('Order not found.');
        }

        $order->items()->delete();
        $order->delete();

        return $this->sendResponse([], 'Order deleted successfully.');
    }
}
