<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/review/{branch?}', \App\Livewire\CustomerReviewForm::class)->name('customer.review');

Route::middleware('auth')->group(function () {
    // Basic account access
    Route::get('/profile', \App\Livewire\ProfileSettings::class)->name('profile.edit');

    // System access for Super Admin, Admin, and Cashier only
    Route::middleware('role:super_admin|admin|cashier')->group(function () {
        Route::get('/dashboard', \App\Livewire\DashboardOverview::class)->name('dashboard');

        // Stock Ordering
        Route::get('/stock/orders', \App\Livewire\BranchStockOrdering::class)->name('stock.orders');
        Route::get('/stock/orders/admin', \App\Livewire\BranchStockOrderAdmin::class)->name('stock.orders.admin');

        // User management
        Route::get('/users', \App\Livewire\UserManagement::class)->name('users.index');
        Route::get('/users/create', \App\Livewire\UserManagement::class)->name('users.create');

               // POS Terminal
        Route::get('/pos', \App\Livewire\PosTerminal::class)->name('pos.index');
        Route::get('/pos/orders/{order}/receipt-data', [\App\Http\Controllers\Api\PosReceiptController::class, 'data'])->name('pos.receipt.data');
        // GCash return URL — where GCash redirects the user after payment
        Route::get('/paymongo/return', [\App\Http\Controllers\PayMongoWebhookController::class, 'returnCallback'])->name('paymongo.return');
        
        // Receipt Printing
        Route::get('/receipts/{order}/thermal', [\App\Http\Controllers\ReceiptController::class, 'thermal'])->name('receipts.thermal');
        
        // Order Management
        Route::get('/orders', \App\Livewire\OrderManagement::class)->name('orders.index');
        
        // Notifications
        Route::get('/notifications', \App\Livewire\NotificationHistory::class)->name('notifications.index');
        
        // Sidebar Placeholder Routes
        Route::get('/kds', \App\Livewire\KitchenDisplay::class)->name('kds.index');
        Route::get('/menu', \App\Livewire\MenuManagement::class)->name('menu.index');
        
        // Stock Management (Modular)
        Route::get('/stock', \App\Livewire\StockManagement::class)->name('stock.index');
        Route::get('/stock/adjustment/{id?}', \App\Livewire\StockAdjustment::class)->name('stock.adjustment');
        
        Route::get('/branches', \App\Livewire\BranchManagement::class)->name('branches.index');
        
        Route::get('/reviews', \App\Livewire\CustomerReviewManagement::class)
            ->middleware('role:super_admin|admin')
            ->name('customers.index');
        
        Route::middleware('role:super_admin|admin')->group(function () {
            Route::get('/reports', \App\Livewire\BusinessIntelligence::class)->name('reports.index');
            Route::get('/reports/bi', \App\Livewire\BusinessIntelligence::class)->name('intelligence.index');
            Route::get('/reports/sales', fn() => redirect()->route('reports.index', ['tab' => 'sales']))->name('reports.sales');
        });
    });

    // Specific Restricted Routes
    Route::get('/management/categories', \App\Livewire\CategoryManagement::class)->name('categories.index')->middleware('role:super_admin');
    Route::get('/management/library', \App\Livewire\OptionLibraryManagement::class)->name('library.index')->middleware('role:super_admin|admin');
    Route::get('/settings', \App\Livewire\SystemSettings::class)->name('settings.index')->middleware('role:super_admin');
});

require __DIR__ . '/auth.php';

