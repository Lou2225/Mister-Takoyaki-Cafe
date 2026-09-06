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
use App\Http\Controllers\Api\DirectionsController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\ThermalPrintController;
use App\Http\Controllers\Api\OtpController;


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
    Route::post('/sync', [SyncController::class, 'sync']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/google-register', [AuthController::class, 'googleRegister']); 
    Route::post('/check-email', [AuthController::class, 'checkEmailExists']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/google-check', [AuthController::class, 'googleCheck']);
    Route::post('/auth/google-login', [AuthController::class, 'googleLogin']);
    Route::middleware('throttle:otp-send')->post('/auth/otp/send',[OtpController::class, 'send']);
    Route::middleware('throttle:otp-verify')->post('/auth/otp/verify',[OtpController::class, 'verify']);
   

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
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
    Route::delete('/user/delete', [UserController::class, 'deleteAccount']);

    // Orders
    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::post('/orders', [OrderApiController::class, 'store']);
    Route::get('/orders/{id}', [OrderApiController::class, 'show']);

    // ─── Rider Routes ────────────────────────────────────────────────────────
    Route::get('/rider/orders',              [RiderOrderController::class, 'index']);
    Route::post('/rider/orders/{id}/accept', [RiderOrderController::class, 'acceptOrder']);
    Route::post('/rider/orders/{id}/deliver',[RiderOrderController::class, 'deliverOrder']);
    Route::post('/rider/orders/{id}/location', [RiderOrderController::class, 'updateLocation']);
    Route::get('/directions', [DirectionsController::class, 'route']);

    // ── User Addresses ───────────────────────────────────────────────────────
    Route::get('/user/addresses',                [UserController::class, 'getUserAddresses']); // fetch all
    Route::post('/user/address',                  [UserController::class, 'saveAddress']);       // save new
    Route::put('/user/address/{id}',              [UserController::class, 'updateAddress']);     // update existing
    Route::patch('/user/address/{id}/default',    [UserController::class, 'setDefaultAddress']);  // set as default
    Route::delete('/user/address/{id}',           [UserController::class, 'deleteAddress']);     // delete by id
     // Status update — called from the website dashboard
    // Protect with a role check in the controller or add 'role:admin,staff' middleware
    Route::patch('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);
    
     // ──── Thermal Printer Routes ────────────────────────────────────────────
    Route::post('/thermal-print', [ThermalPrintController::class, 'print']);
    Route::post('/thermal-print/test', [ThermalPrintController::class, 'test']);
    Route::get('/thermal-print/config', [ThermalPrintController::class, 'getConfig']);
    Route::get('/thermal-print/available-printers', [ThermalPrintController::class, 'getAvailablePrinters']);
});
