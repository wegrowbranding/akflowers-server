<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends BaseController
{
    /**
     * List all categories with pagination and search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $search = $request->get('search_term');

        $query = Category::with('parent')->where('deleted', 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('category_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $categories = $query->paginate($limit);

        return $this->sendResponse([
            'total' => $categories->total(),
            'limit' => $categories->perPage(),
            'page' => $categories->currentPage(),
            'data' => $categories->items()
        ], 'Categories retrieved successfully.');
    }

    /**
     * Add a new category.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'category_name' => 'required|string|max:150',
            'parent_category_id' => 'nullable|exists:categories,id',
            'status' => 'in:active,inactive',
            'created_by' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $category = Category::create($input);

        return $this->sendResponse($category, 'Category created successfully.');
    }

    /**
     * Edit an existing category.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, $id): JsonResponse
    {
        $category = Category::where('id', $id)->where('deleted', 0)->first();

        if (is_null($category)) {
            return $this->sendError('Category not found or has been deleted.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'category_name' => 'string|max:150',
            'parent_category_id' => 'nullable|exists:categories,id',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $category->update($input);

        return $this->sendResponse($category, 'Category updated successfully.');
    }

    /**
     * Delete a category.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $category = Category::where('id', $id)->where('deleted', 0)->first();

        if (is_null($category)) {
            return $this->sendError('Category not found or already deleted.');
        }

        $category->update([
            'deleted' => 1
        ]);

        return $this->sendResponse([], 'Category deleted successfully.');
    }
}
