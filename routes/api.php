<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchAdminController;
use App\Http\Controllers\Api\BranchRoleController;
use App\Http\Controllers\Api\BranchStaffUserController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
Route::prefix('v1')->group(function () {
    Route::get('/status', [StatusController::class, 'index']);
    Route::get('/health', [StatusController::class, 'health']);

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.jwt');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // Category Routes
    Route::prefix('categories')->group(function () {
        Route::get('/list', [CategoryController::class, 'list']);
        
        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [CategoryController::class, 'add']);
            Route::put('/{id}/edit', [CategoryController::class, 'edit']);
            Route::delete('/{id}/delete', [CategoryController::class, 'delete']);
        });
    });

    // Product Routes
    Route::prefix('products')->group(function () {
        Route::get('/list', [ProductController::class, 'list']);

        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [ProductController::class, 'add']);
            Route::put('/{id}/edit', [ProductController::class, 'edit']);
            Route::delete('/{id}/delete', [ProductController::class, 'delete']);
        });
    });

    // Media Routes
    Route::prefix('media')->group(function () {
        Route::get('/{id}/view', [MediaController::class, 'view']);
        
        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/upload', [MediaController::class, 'upload']);
            Route::delete('/{id}/delete', [MediaController::class, 'delete']);
        });
    });

    // Branch Routes
    Route::prefix('branches')->group(function () {
        Route::get('/list', [BranchController::class, 'list']);
        
        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [BranchController::class, 'add']);
            Route::put('/{id}/edit', [BranchController::class, 'edit']);
            Route::delete('/{id}/delete', [BranchController::class, 'delete']);
        });
    });

    // Branch Admin Routes
    Route::prefix('branch-admins')->group(function () {
        Route::get('/list', [BranchAdminController::class, 'list']);
        
        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [BranchAdminController::class, 'add']);
            Route::put('/{id}/edit', [BranchAdminController::class, 'edit']);
            Route::delete('/{id}/delete', [BranchAdminController::class, 'delete']);
        });
    });

    // Branch Role Routes
    Route::prefix('branch-roles')->group(function () {
        Route::get('/list', [BranchRoleController::class, 'list']);
        
        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [BranchRoleController::class, 'add']);
            Route::put('/{id}/edit', [BranchRoleController::class, 'edit']);
            Route::delete('/{id}/delete', [BranchRoleController::class, 'delete']);
        });
    });

    // Branch Staff User Routes
    Route::prefix('branch-staff-users')->group(function () {
        Route::get('/list', [BranchStaffUserController::class, 'list']);
        
        // Protected Routes
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [BranchStaffUserController::class, 'add']);
            Route::put('/{id}/edit', [BranchStaffUserController::class, 'edit']);
            Route::delete('/{id}/delete', [BranchStaffUserController::class, 'delete']);
        });
    });

    // Cart Routes
    Route::prefix('carts')->group(function () {
        Route::get('/list', [CartController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [CartController::class, 'add']);
            Route::put('/{id}/edit', [CartController::class, 'edit']);
            Route::delete('/{id}/delete', [CartController::class, 'delete']);
        });
    });

    // Coupon Routes
    Route::prefix('coupons')->group(function () {
        Route::get('/list', [CouponController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [CouponController::class, 'add']);
            Route::put('/{id}/edit', [CouponController::class, 'edit']);
            Route::delete('/{id}/delete', [CouponController::class, 'delete']);
        });
    });

    // Customer Routes
    Route::prefix('customers')->group(function () {
        Route::get('/list', [CustomerController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [CustomerController::class, 'add']);
            Route::put('/{id}/edit', [CustomerController::class, 'edit']);
            Route::delete('/{id}/delete', [CustomerController::class, 'delete']);
        });
    });

    // Customer Address Routes
    Route::prefix('customer-addresses')->group(function () {
        Route::get('/list', [CustomerAddressController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [CustomerAddressController::class, 'add']);
            Route::put('/{id}/edit', [CustomerAddressController::class, 'edit']);
            Route::delete('/{id}/delete', [CustomerAddressController::class, 'delete']);
        });
    });

    // Order Routes
    Route::prefix('orders')->group(function () {
        Route::get('/list', [OrderController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [OrderController::class, 'add']);
            Route::put('/{id}/edit', [OrderController::class, 'edit']);
            Route::delete('/{id}/delete', [OrderController::class, 'delete']);
        });
    });

    // Payment Routes
    Route::prefix('payments')->group(function () {
        Route::get('/list', [PaymentController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [PaymentController::class, 'add']);
            Route::put('/{id}/edit', [PaymentController::class, 'edit']);
            Route::delete('/{id}/delete', [PaymentController::class, 'delete']);
        });
    });

    // Review Routes
    Route::prefix('reviews')->group(function () {
        Route::get('/list', [ReviewController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [ReviewController::class, 'add']);
            Route::put('/{id}/edit', [ReviewController::class, 'edit']);
            Route::delete('/{id}/delete', [ReviewController::class, 'delete']);
        });
    });

    // Wishlist Routes
    Route::prefix('wishlists')->group(function () {
        Route::get('/list', [WishlistController::class, 'list']);
        Route::middleware('auth.jwt')->group(function () {
            Route::post('/add', [WishlistController::class, 'add']);
            Route::put('/{id}/edit', [WishlistController::class, 'edit']);
            Route::delete('/{id}/delete', [WishlistController::class, 'delete']);
        });
    });

    Route::middleware('auth.jwt')->get('/user', function (Request $request) {
        return $request->user();
    });
});
