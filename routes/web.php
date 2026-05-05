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

Route::get('/review/{branch?}', \App\Http\Livewire\CustomerReviewForm::class)->name('customer.review');

Route::middleware('auth')->group(function () {
    // Basic account access
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // System access for Super Admin, Admin, and Cashier only
    Route::middleware('role:super_admin|admin|cashier')->group(function () {
        Route::get('/dashboard', \App\Http\Livewire\DashboardOverview::class)->name('dashboard');

        // Stock Ordering
        Route::get('/stock/orders', \App\Http\Livewire\BranchStockOrdering::class)->name('stock.orders');
        Route::get('/stock/orders/admin', \App\Http\Livewire\BranchStockOrderAdmin::class)->name('stock.orders.admin');

        // User management
        Route::get('/users', \App\Http\Livewire\UserManagement::class)->name('users.index');
        Route::get('/users/create', \App\Http\Livewire\UserManagement::class)->name('users.create');

        // POS Terminal
        Route::get('/pos', \App\Http\Livewire\PosTerminal::class)->name('pos.index');
        
        // Receipt Printing
        Route::get('/receipts/{order}/thermal', [\App\Http\Controllers\ReceiptController::class, 'thermal'])->name('receipts.thermal');
        
        // Order Management
        Route::get('/orders', \App\Http\Livewire\OrderManagement::class)->name('orders.index');
        
        // Sidebar Placeholder Routes
        Route::get('/kds', \App\Http\Livewire\KitchenDisplay::class)->name('kds.index');
        Route::get('/menu', \App\Http\Livewire\MenuManagement::class)->name('menu.index');
        
        // Stock Management (Modular)
        Route::get('/stock', \App\Http\Livewire\StockManagement::class)->name('stock.index');
        Route::get('/stock/adjustment', \App\Http\Livewire\StockAdjustment::class)->name('stock.adjustment');
        Route::get('/stock/expiry', \App\Http\Livewire\StockExpiry::class)->name('stock.expiry');
        
        Route::get('/branches', \App\Http\Livewire\BranchManagement::class)->name('branches.index');
        
        Route::get('/reviews', \App\Http\Livewire\CustomerReviewManagement::class)
            ->middleware('role:super_admin|admin')
            ->name('customers.index');
        
        Route::get('/reports', \App\Http\Livewire\BusinessIntelligence::class)->name('reports.index');
        Route::get('/reports/bi', \App\Http\Livewire\BusinessIntelligence::class)->name('intelligence.index');
        Route::get('/reports/sales', fn() => redirect()->route('reports.index', ['tab' => 'sales']))->name('reports.sales');
    });

    // Specific Restricted Routes
    Route::get('/management/categories', \App\Http\Livewire\CategoryManagement::class)->name('categories.index')->middleware('role:super_admin');
    Route::get('/management/library', \App\Http\Livewire\OptionLibraryManagement::class)->name('library.index')->middleware('role:super_admin|admin');
    Route::get('/settings', \App\Http\Livewire\SystemSettings::class)->name('settings.index')->middleware('role:super_admin');
});

require __DIR__ . '/auth.php';

