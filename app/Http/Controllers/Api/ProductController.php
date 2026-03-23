<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends BaseController
{
    /**
     * List all products with pagination and search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = Product::with(['category', 'media'])->where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                  ->orWhere('product_code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $products->total(),
            'limit' => $products->perPage(),
            'page' => $products->currentPage(),
            'data' => $products->items()
        ], 'Products retrieved successfully.');
    }

    /**
     * Add a new product.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'product_code' => 'required|string|max:100|unique:products,product_code',
            'product_name' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'price' => 'numeric|min:0',
            'cost_price' => 'numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'status' => 'in:active,inactive',
            'created_by' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $product = Product::create($input);

        // Handle media attachments
        if ($request->has('media_ids') && is_array($request->media_ids)) {
            $primaryId = $request->get('primary_media_id');
            foreach ($request->media_ids as $mediaId) {
                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'media_id' => $mediaId,
                    'is_primary' => ($primaryId == $mediaId) ? 1 : 0
                ]);
            }
        }
        $product->load('media');

        return $this->sendResponse($product, 'Product created successfully.');
    }

    /**
     * Edit an existing product.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('deleted', 0)->first();

        if (is_null($product)) {
            return $this->sendError('Product not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'product_code' => 'string|max:100|unique:products,product_code,'.$id,
            'product_name' => 'string|max:200',
            'category_id' => 'exists:categories,id',
            'price' => 'numeric|min:0',
            'cost_price' => 'numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $product->update($input);

        // Handle media attachments updates
        if ($request->has('media_ids') && is_array($request->media_ids)) {
            \App\Models\ProductImage::where('product_id', $product->id)->delete();
            $primaryId = $request->get('primary_media_id');
            foreach ($request->media_ids as $mediaId) {
                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'media_id' => $mediaId,
                    'is_primary' => ($primaryId == $mediaId) ? 1 : 0
                ]);
            }
        }
        $product->load('media');

        return $this->sendResponse($product, 'Product updated successfully.');
    }

    /**
     * Delete a product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $product = Product::where('id', $id)->where('deleted', 0)->first();

        if (is_null($product)) {
            return $this->sendError('Product not found or already deleted.');
        }

        $product->update([
            'deleted' => 1
        ]);

        return $this->sendResponse([], 'Product deleted successfully.');
    }
}
