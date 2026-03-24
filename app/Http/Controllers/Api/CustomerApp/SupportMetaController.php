<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\SupportMeta;
use Illuminate\Http\JsonResponse;

class SupportMetaController extends BaseController
{
    /**
     * Get a support meta value by its key.
     *
     * @param string $meta_key
     * @return JsonResponse
     */
    public function getMeta($meta_key): JsonResponse
    {
        $meta = SupportMeta::where('meta_key', $meta_key)->first();

        if (!$meta) {
            return $this->sendError('Information not found.', [], 404);
        }

        return $this->sendResponse([
            'meta_key' => $meta->meta_key,
            'meta_value' => $meta->meta_value
        ], 'Support info retrieved successfully.');
    }
}
