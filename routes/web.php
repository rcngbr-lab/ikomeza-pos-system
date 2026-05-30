<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockRequisitionController;
use App\Http\Controllers\StoreManagementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified'
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'products',
        ProductController::class
    )->middleware('admin.manager');

    Route::get(
        '/products/{product}/adjust',
        [ProductController::class, 'adjust']
    )->name('products.adjust')
    ->middleware('admin.manager');

    Route::post(
        '/products/{product}/adjust',
        [ProductController::class, 'adjustStock']
    )->name('products.adjust.stock')
    ->middleware('admin.manager');

    /*
    |--------------------------------------------------------------------------
    | CATEGORIES
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'categories',
        CategoryController::class
    )->middleware('admin.manager');

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'users',
        UserController::class
    )->except(['show', 'destroy'])
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER');

    /*
    |--------------------------------------------------------------------------
    | ROLES
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'roles',
        RoleController::class
    )->middleware('operational.role:ADMIN,ADMINISTRATOR');

    Route::get(
        '/roles/{id}/permissions',
        [RoleController::class, 'permissions']
    )->name('roles.permissions')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR');

    Route::post(
        '/roles/{id}/permissions',
        [RoleController::class, 'updatePermissions']
    )->name('roles.permissions.update')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR');

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'permissions',
        PermissionController::class
    )->middleware('operational.role:ADMIN,ADMINISTRATOR');

    /*
    |--------------------------------------------------------------------------
    | POS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pos',
        [PosController::class, 'index']
    )->name('pos.index')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::post(
        '/pos/add-to-cart',
        [PosController::class, 'addToCart']
    )->name('pos.add')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::post(
        '/pos/remove-cart-item',
        [PosController::class, 'removeCartItem']
    )->name('pos.remove')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::post(
        '/pos/increase-cart-item',
        [PosController::class, 'increaseCart']
    )->name('pos.increase')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::post(
        '/pos/decrease-cart-item',
        [PosController::class, 'decreaseCart']
    )->name('pos.decrease')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::post(
        '/pos/clear',
        [PosController::class, 'clearCart']
    )->name('pos.clear')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::get(
        '/pos/checkout',
        fn () => redirect()
            ->route('pos.index')
            ->with('error', 'Use the Complete Sale button to checkout.')
    )->name('pos.checkout.get')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::post(
        '/pos/checkout',
        [PosController::class, 'checkout']
    )->name('pos.checkout')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    Route::get(
        '/pos/receipt/{id}',
        [PosController::class, 'receipt']
    )->name('pos.receipt')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER');

    /*
    |--------------------------------------------------------------------------
    | CART ROUTE ALIASES
    |--------------------------------------------------------------------------
    |
    | Older Blade screens still reference cart.* names. Keep these aliases pointed
    | at the POS workflow so bookmarked cashier links do not break.
    |
    */

    Route::middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,CASHIER,WAITER,SERVER')->group(function () {
        Route::get('/cart', [PosController::class, 'index'])->name('cart.index');
        Route::post('/cart/add', [PosController::class, 'addToCart'])->name('cart.add');
        Route::post('/cart/update', [PosController::class, 'updateCart'])->name('cart.update');
        Route::post('/cart/remove/{product_id?}', [PosController::class, 'removeCartItem'])->name('cart.remove');
        Route::post('/cart/clear', [PosController::class, 'clearCart'])->name('cart.clear');
        Route::get('/cart/checkout', fn () => redirect()->route('pos.index')->with('error', 'Use the Complete Sale button to checkout.'))->name('cart.checkout.get');
        Route::post('/cart/checkout', [PosController::class, 'checkout'])->name('cart.checkout');
    });

    /*
    |--------------------------------------------------------------------------
    | INVENTORY
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/inventory',
        [InventoryController::class, 'index']
    )->name('inventory.index')
    ->middleware('admin.manager');

    Route::post(
        '/inventory/stock-in',
        [InventoryController::class, 'stockIn']
    )->name('inventory.stockin')
    ->middleware('admin.manager');

    Route::post(
        '/inventory/damage',
        [InventoryController::class, 'damage']
    )->name('inventory.damage')
    ->middleware('admin.manager');

    Route::get(
        '/inventory/print-history',
        [InventoryController::class, 'printHistory']
    )->name('inventory.print')
    ->middleware('admin.manager');

    /*
    |--------------------------------------------------------------------------
    | STORE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::prefix('store')
        ->name('store.')
        ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER')
        ->group(function () {
            Route::get('/', [StoreManagementController::class, 'dashboard'])->name('dashboard');
            Route::get('/suppliers', [StoreManagementController::class, 'suppliers'])->name('suppliers');
            Route::post('/suppliers', [StoreManagementController::class, 'storeSupplier'])->name('suppliers.store');
            Route::get('/purchases', [StoreManagementController::class, 'purchases'])->name('purchases');
            Route::post('/purchases', [StoreManagementController::class, 'storePurchase'])->name('purchases.store');
            Route::post('/purchases/{purchase}/approve', [StoreManagementController::class, 'approvePurchase'])->name('purchases.approve');
            Route::post('/purchases/{purchase}/receive', [StoreManagementController::class, 'receivePurchase'])->name('purchases.receive');
            Route::get('/issues', [StoreManagementController::class, 'issues'])->name('issues');
            Route::post('/issues', [StoreManagementController::class, 'storeIssue'])->name('issues.store');
            Route::post('/issues/{issue}/approve', [StoreManagementController::class, 'approveIssue'])->name('issues.approve');
            Route::get('/damages', [StoreManagementController::class, 'damages'])->name('damages');
            Route::post('/damages', [StoreManagementController::class, 'storeDamage'])->name('damages.store');
            Route::post('/damages/{damage}/approve', [StoreManagementController::class, 'approveDamage'])->name('damages.approve');
            Route::get('/returns', [StoreManagementController::class, 'returns'])->name('returns');
            Route::post('/returns', [StoreManagementController::class, 'storeReturn'])->name('returns.store');
            Route::post('/returns/{return}/approve', [StoreManagementController::class, 'approveReturn'])->name('returns.approve');
            Route::get('/movements', [StoreManagementController::class, 'movements'])->name('movements');
        });

    /*
    |--------------------------------------------------------------------------
    | REQUISITIONS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/requisitions',
        [StockRequisitionController::class, 'index']
    )->name('requisitions.index')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::post(
        '/requisitions',
        [StockRequisitionController::class, 'store']
    )->name('requisitions.store')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::post(
        '/requisitions/{requisition}/approve',
        [StockRequisitionController::class, 'approve']
    )->name('requisitions.approve')
    ->middleware('admin.manager');

    Route::post(
        '/requisitions/{requisition}/reject',
        [StockRequisitionController::class, 'reject']
    )->name('requisitions.reject')
    ->middleware('admin.manager');

    /*
    |--------------------------------------------------------------------------
    | SALES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/sales',
        [SaleController::class, 'index']
    )->name('sales.index')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/sales/{sale}/receipt',
        [SaleController::class, 'receipt']
    )->name('sales.receipt')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/sales/{sale}/print',
        [SaleController::class, 'print']
    )->name('sales.print')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::post(
        '/sales/{sale}/refund',
        [SaleController::class, 'refund']
    )
    ->name('sales.refund')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER');

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reports',
        [ReportController::class, 'index']
    )->name('reports.index')
    ->middleware('admin.manager');

    Route::get(
        '/my-report',
        [ReportController::class, 'myReport']
    )->name('reports.my')
    ->middleware('operational.role:CASHIER,WAITER,SERVER');

    /*
    |--------------------------------------------------------------------------
    | REFUNDS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/refunds',
        [RefundController::class, 'index']
    )->name('refunds.index')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER');

    /*
    |--------------------------------------------------------------------------
    | SHIFTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/shifts/open',
        [ShiftController::class, 'openForm']
    )->name('shifts.open.form')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::post(
        '/shifts/open',
        [ShiftController::class, 'open']
    )->name('shifts.open')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/shifts/current',
        [ShiftController::class, 'current']
    )->name('shifts.current')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::post(
        '/shifts/close',
        [ShiftController::class, 'close']
    )->name('shifts.close')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/shifts/history',
        [ShiftController::class, 'history']
    )->name('shifts.history')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/shifts/history/print',
        [ShiftController::class, 'printHistory']
    )->name('shifts.history.print')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/shifts/{shift}/print',
        [ShiftController::class, 'print']
    )->name('shifts.print')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    /*
    |--------------------------------------------------------------------------
    | STOCK MOVEMENTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/stock-movements',
        [StockMovementController::class, 'index']
    )->name('stock.movements')
    ->middleware('admin.manager');

    /*
    |--------------------------------------------------------------------------
    | AUDIT LOGS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/audit-logs',
        [AuditLogController::class, 'index']
    )->name('audit.logs')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

    Route::get(
        '/audit-logs/print',
        [AuditLogController::class, 'print']
    )->name('audit.logs.print')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER');

    Route::get(
        '/audit-logs/export/{format}',
        [AuditLogController::class, 'export']
    )->name('audit.logs.export')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER');

    Route::get(
        '/audit-logs/{auditLog}',
        [AuditLogController::class, 'show']
    )->name('audit.logs.show')
    ->middleware('operational.role:ADMIN,ADMINISTRATOR,MANAGER,STORE_KEEPER,KITCHEN_MANAGER,KITCHEN_CHIEF,BAR_MANAGER,BAR_CHIEF,BARTENDER,CASHIER,WAITER,SERVER');

});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
