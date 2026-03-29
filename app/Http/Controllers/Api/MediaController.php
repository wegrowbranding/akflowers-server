<?php

namespace App\Http\Controllers\Api;

use App\Models\Media;
use App\Models\Session as UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends BaseController
{
    /**
     * Upload a new media file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'file_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        if (!$request->hasFile('file')) {
            return $this->sendError('No file provided.', [], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        
        // Generate a unique filename if not provided
        $uniqueFileName = $this->generateString(20) . '.' . $extension;

        // Store file in 'storage/app/public/media'
        $filePath = $file->storeAs('media', $uniqueFileName, 'public');
        $fileUrl = Storage::url($filePath);

        // Get authenticated user ID
        $user = auth()->user();
        $uploadedBy = $user ? $user->id : null;

        $media = Media::create([
            'file_name' => $uniqueFileName,
            'original_name' => $originalName,
            'file_type' => explode('/', $mimeType)[0] ?? 'unknown',
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'extension' => $extension,
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'uploaded_by' => $uploadedBy,
            'status' => 'active',
            'deleted' => 0,
        ]);

        return $this->sendResponse($media, 'Media uploaded successfully.');
    }

    /**
     * View a media file.
     *
     * @param int $id
     * @return mixed
     */
    public function view($id)
    {
        $media = Media::where('id', $id)->where('deleted', 0)->first();

        if (is_null($media) || !Storage::disk('public')->exists($media->file_path)) {
            return $this->sendError('Media not found or deleted.', [], 404);
        }

        $path = Storage::disk('public')->path($media->file_path);
        
        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->original_name . '"'
        ]);
    }

    /**
     * Delete a media file (soft delete).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $media = Media::where('id', $id)->where('deleted', 0)->first();

        if (is_null($media)) {
            return $this->sendError('Media not found or already deleted.');
        }

        $media->update([
            'deleted' => 1
        ]);

        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        return $this->sendResponse([], 'Media deleted successfully.');
    }

    /**
     * Generate a random string.
     *
     * @param int $length
     * @return string
     */
    function generateString($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
