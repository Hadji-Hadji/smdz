<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes or routes that don't require authentication initially
// Example: Route::post("/register", [AuthController::class, "register"]);
// Example: Route::post("/login", [AuthController::class, "login"]);

Route::middleware("auth:api")->group(function () {
    // Admin APIs
    Route::prefix("admin")->middleware("admin.check")->group(function () {
        // Admins Management
        Route::get("admins", [AdminController::class, "indexAdmins"]);
        Route::post("admins", [AdminController::class, "storeAdmin"]);
        Route::put("admins/{admin_id}", [AdminController::class, "updateAdmin"]);
        Route::delete("admins/{admin_id}", [AdminController::class, "destroyAdmin"]);

        // Packages Management
        Route::get("packages", [AdminController::class, "indexPackages"]);
        Route::post("packages", [AdminController::class, "storePackage"]);
        Route::put("packages/{package_id}", [AdminController::class, "updatePackage"]);
        Route::delete("packages/{package_id}", [AdminController::class, "destroyPackage"]);

        // Payments Management
        Route::get("payments/pending", [AdminController::class, "indexPendingPayments"]);
        Route::get("payments/{payment_id}", [AdminController::class, "showPayment"]);
        Route::put("payments/{payment_id}/approve", [AdminController::class, "approvePayment"]);
        Route::put("payments/{payment_id}/reject", [AdminController::class, "rejectPayment"]);

        // Subscriptions Management
        Route::get("subscriptions", [AdminController::class, "indexSubscriptions"]);
        Route::put("subscriptions/{subscription_id}/status", [AdminController::class, "updateSubscriptionStatus"]);
    });

    // User APIs
    Route::prefix("user")->group(function () {
        // Packages
        Route::get("packages", [UserController::class, "indexPackages"]);
        Route::get("packages/{package_id}", [UserController::class, "showPackage"]);

        // Subscriptions & Payments
        Route::post("subscribe", [UserController::class, "subscribeToPackage"]);
        Route::post("payments/{payment_id}/upload-receipt", [UserController::class, "uploadPaymentReceipt"]);
        Route::get("subscription/status", [UserController::class, "getSubscriptionStatus"]);
        Route::get("subscriptions", [UserController::class, "indexMySubscriptions"]);
        Route::get("payments", [UserController::class, "indexMyPayments"]);
        Route::get("payments/{payment_id}", [UserController::class, "showMyPayment"]);
    });
});

// You might have existing routes here, ensure they are merged correctly.
// Example: Route::apiResource("apartments", ApartmentController::class);
// Example: Route::apiResource("favorites", FavoriteController::class);
// Example: Route::apiResource("verification-documents", VerificationDocumentController::class);