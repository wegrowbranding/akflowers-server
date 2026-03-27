<?php

namespace App\Http\Controllers\Api\CustomerApp;

use App\Http\Controllers\Api\BaseController;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends BaseController
{
    /**
     * Get User Profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        return $this->sendResponse($customer, 'Profile retrieved successfully.');
    }

    /**
     * Edit User Profile
     */
    public function editProfile(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/|unique:customers,phone,' . $customer->id,
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'profile_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $input = $request->only(['full_name', 'phone', 'gender', 'date_of_birth', 'profile_image']);
        
        // Remove nulls to avoid overwriting existing data with empty values if that is preferred,
        // or just apply all provided values. Here we apply all provided in request.
        $customer->update($input);

        return $this->sendResponse($customer, 'Profile updated successfully.');
    }

    /**
     * Update Profile Photo
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors()->toArray(), 400);
        }

        $base64Image = $request->profile_image;

        // Delete old media if exists
        if ($customer->profile_image) {
            $oldMedia = Media::find($customer->profile_image);

            if ($oldMedia) {
                // Delete file
                if ($oldMedia->file_path && Storage::disk('public')->exists($oldMedia->file_path)) {
                    Storage::disk('public')->delete($oldMedia->file_path);
                }

                // Delete DB record (or soft delete if needed)
                $oldMedia->delete();
            }
        }

        // Handle base64 image
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $type = 'jpg';
        }

        $imageData = base64_decode($base64Image);

        if ($imageData === false) {
            return $this->sendError('Invalid image data.', [], 400);
        }

        $fileName = Str::random(20) . '.' . $type;
        $filePath = 'media/' . $fileName;

        // Save to storage
        Storage::disk('public')->put($filePath, $imageData);
        $fileUrl = Storage::url($filePath);

        // Get mime type
        $f = finfo_open();
        $mimeType = finfo_buffer($f, $imageData, FILEINFO_MIME_TYPE);
        finfo_close($f);

        $fileSize = strlen($imageData);

        // Create media
        $media = Media::create([
            'file_name' => $fileName,
            'original_name' => 'profile_photo_' . $customer->id . '.' . $type,
            'file_type' => 'image',
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'extension' => $type,
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'uploaded_by' => null,
            'status' => 'active',
            'deleted' => 0,
        ]);

        // Update customer
        $customer->update(['profile_image' => $media->id]);

        return $this->sendResponse($customer, 'Profile photo updated successfully.');
    }

    /**
     * Remove Profile Photo
     */
    public function removeProfilePhoto(Request $request): JsonResponse
    {
        $customer = $request->user('customer_api');

        if (!$customer->profile_image) {
            return $this->sendError('No profile image found.', [], 404);
        }

        // Get media record
        $media = Media::find($customer->profile_image);

        if ($media) {

            // Delete file from storage
            if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }

            // Option 1: Hard delete
            $media->delete();

            // Option 2 (Recommended): Soft delete
            // $media->update(['deleted' => 1, 'status' => 'inactive']);
        }

        // Remove reference from customer
        $customer->update(['profile_image' => null]);

        return $this->sendResponse($customer, 'Profile photo removed successfully.');
    }
}
