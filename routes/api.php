<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\BranchApiController;
use App\Http\Controllers\Api\RiderOrderController;
use App\Http\Controllers\Api\UserController;
use App\Models\User;

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

// Public authentication routes (no middleware required)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/check-email', [AuthController::class, 'checkEmailExists']);

// PayMongo GCash Webhook — must be public & CSRF-exempt (POST from PayMongo servers)
Route::post('/paymongo/webhook', [\App\Http\Controllers\PayMongoWebhookController::class, 'handle'])->name('paymongo.webhook');

// Branches & menu are public (customers browse before logging in)
Route::get('/branches', [BranchApiController::class, 'index']);
Route::get('/branches/{id}', [BranchApiController::class, 'show']);
Route::get('/menu', [BranchApiController::class, 'menu']);   // ?branch_id=3

// Products
Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/products/{id}', [ProductApiController::class, 'show']);
Route::get('/products/{id}/customizations', [ProductApiController::class, 'customizations']);

// Protected routes (require auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Orders
    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::post('/orders', [OrderApiController::class, 'store']);
    Route::get('/orders/{id}', [OrderApiController::class, 'show']);

    Route::get('/rider/orders', [RiderOrderController::class, 'getOrders']);

    // User Profile
    Route::post('/user/address', [UserController::class, 'saveAddress']);

     // Status update — called from the website dashboard
    // Protect with a role check in the controller or add 'role:admin,staff' middleware
    Route::patch('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);
});
