<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');
        
        $query = Payment::query();

        if ($search) {
            $query->where('transaction_id', 'LIKE', "%{$search}%");
        }
        
        $payments = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $payments->total(),
            'limit' => $payments->perPage(),
            'page' => $payments->currentPage(),
            'data' => $payments->items()
        ], 'Payments retrieved successfully.');
    }

    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'order_id' => 'required|integer|exists:orders,id',
            'transaction_id' => 'nullable|string|max:255',
            'payment_gateway' => 'nullable|string|max:100',
            'amount' => 'required|numeric',
            'status' => 'in:pending,success,failed',
            'paid_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $payment = Payment::create($input);

        return $this->sendResponse($payment, 'Payment created successfully.');
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'order_id' => 'integer|exists:orders,id',
            'transaction_id' => 'nullable|string|max:255',
            'payment_gateway' => 'nullable|string|max:100',
            'amount' => 'numeric',
            'status' => 'in:pending,success,failed',
            'paid_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $payment->update($input);

        return $this->sendResponse($payment, 'Payment updated successfully.');
    }

    public function delete($id): JsonResponse
    {
        $payment = Payment::find($id);

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        $payment->delete();

        return $this->sendResponse([], 'Payment deleted successfully.');
    }
}
