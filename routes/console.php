<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Cart;
use App\Models\Wishlist;
use App\Services\NotificationService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // 1. Wishlist item price drop notifications (Simplified)
    // In a real app, you'd compare with price_at_addition or a price_history
    $wishlists = Wishlist::with('items.product')->get();
    foreach ($wishlists as $wishlist) {
        foreach ($wishlist->items as $item) {
            if ($item->product) {
                 NotificationService::sendToCustomer(
                    $wishlist->customer_id,
                    "Special Price for your wishlist item! ✨",
                    "The price of {$item->product->product_name} is currently {$item->product->price}. Check it out!",
                    'wishlist_price_drop',
                    $item->product_id
                );
            }
        }
    }
})->dailyAt('10:00');

Schedule::call(function () {
    // 2. Abandoned Cart Reminders
    // Remind users if they have items in cart and haven't checked out in 24h
    $threshold = Carbon::now()->subHours(24);
    
    // For this example, we assume we want to remind everyone with a non-empty cart
    // In a real app, you'd check 'updated_at' >= $threshold
    $carts = Cart::with('items')->whereHas('items')->get();
    
    foreach ($carts as $cart) {
        $count = $cart->items->count();
        if ($count > 0) {
            NotificationService::sendToCustomer(
                $cart->customer_id,
                "Don't forget your flowers! 🌸",
                "You have {$count} item(s) in your cart. Complete your purchase now!",
                'abandoned_cart'
            );
        }
    }
})->dailyAt('18:00');
