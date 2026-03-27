<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\CustomerDevice;
use App\Helpers\FirebaseHelper;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send notification to a specific customer
     */
    public static function sendToCustomer($customerId, $title, $message, $type = 'general', $referenceId = null, $imageUrl = null)
    {
        // 1. Store in Database
        $notification = Notification::create([
            'customer_id' => $customerId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'reference_id' => $referenceId,
            'image_url' => $imageUrl,
            'is_read' => 0,
        ]);

        // 2. Get active FCM tokens for this customer
        $thresholdDate = Carbon::now()->subDays(30);
        $tokens = CustomerDevice::where('customer_id', $customerId)
            ->where('is_active', 1)
            ->where('last_used_at', '>=', $thresholdDate)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->unique()
            ->toArray();

        // 3. Send to FCM
        foreach ($tokens as $token) {
            FirebaseHelper::sendNotification($token, $title, $message, [
                'type' => $type,
                'reference_id' => (string) $referenceId,
                'notification_id' => (string) $notification->id
            ]);
        }

        return $notification;
    }

    /**
     * Send notification to all active users
     */
    public static function sendToAll($title, $message, $type = 'general', $referenceId = null, $imageUrl = null)
    {
        // For "Send to All", we might not want to create a DB entry for EVERY user if there are thousands,
        // but typically for small/medium apps, we do.
        // If we want it to show in their notification list, we must create entries.
        
        // Get all active customer IDs who have been active in the last 30 days
        $thresholdDate = Carbon::now()->subDays(30);
        $customerIds = CustomerDevice::where('is_active', 1)
            ->where('last_used_at', '>=', $thresholdDate)
            ->pluck('customer_id')
            ->unique()
            ->toArray();

        foreach ($customerIds as $customerId) {
            self::sendToCustomer($customerId, $title, $message, $type, $referenceId, $imageUrl);
        }

        return true;
    }
}
