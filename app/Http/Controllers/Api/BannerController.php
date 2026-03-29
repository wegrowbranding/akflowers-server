<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BannerController extends BaseController
{
    /**
     * List all banners.
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $banners = Banner::with('media')->orderBy('id', 'desc')->get();

        return $this->sendResponse($banners, 'Banners retrieved successfully.');
    }

    /**
     * Add a new banner.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|exists:media,id',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $banner = Banner::create([
            'image' => $request->image,
            'status' => $request->get('status', 1),
        ]);

        return $this->sendResponse($banner->load('media'), 'Banner added successfully.');
    }

    /**
     * Edit an existing banner.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, $id): JsonResponse
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return $this->sendError('Banner not found.');
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|exists:media,id',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $banner->update($request->only(['image', 'status']));

        return $this->sendResponse($banner->load('media'), 'Banner updated successfully.');
    }

    /**
     * Delete a banner.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return $this->sendError('Banner not found.');
        }

        $banner->delete();
        Media::where('id', $banner->image)->delete();

        return $this->sendResponse([], 'Banner deleted successfully.');
    }
}
